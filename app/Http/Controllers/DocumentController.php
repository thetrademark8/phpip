<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Models\Event;
use App\Models\Matter;
use App\Models\MatterActors;
use App\Models\Task;
use App\Models\TemplateClass;
use App\Models\TemplateMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $Notes = $request->input('Notes');
        $Name = $request->input('Name');
        $template_classes = TemplateClass::query();
        if (! is_null($Name)) {
            $template_classes = $template_classes->whereLike('name', $Name.'%');
        }
        if (! is_null($Notes)) {
            $template_classes = $template_classes->whereLike('notes', $Notes.'%');
        }

        $query = $template_classes->orderby('name');

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        return Inertia::render('Document/Index', [
            'template_classes' => $query->paginate(15),
            'filters' => $request->only(['Name', 'Notes']),
            'sort' => 'name',
            'direction' => 'asc'
        ]);
    }

    public function create()
    {
        $table = new TemplateClass;
        $tableComments = $table->getTableComments();

        return response()->json([
            'tableComments' => $tableComments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:55',
        ]);
        $request->merge(['creator' => Auth::user()->login]);
        
        $templateClass = TemplateClass::create($request->except(['_token', '_method']));
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('document.index')
                ->with('success', 'Template class created successfully');
        }

        return $templateClass;
    }

    public function show(TemplateClass $class)
    {
        $class->load(['role', 'eventNames']);
        $tableComments = $class->getTableComments();

        return response()->json([
            'templateClass' => $class,
            'tableComments' => $tableComments
        ]);
    }

    public function update(Request $request, TemplateClass $class)
    {
        $request->merge(['updater' => Auth::user()->login]);
        $class->update($request->except(['_token', '_method']));

        return response()->json(['success' => 'Template class updated']);
    }

    public function destroy(TemplateClass $class)
    {
        $class->delete();

        return response()->json(['success' => 'Template class deleted']);
    }

    public function select(Matter $matter, Request $request)
    {
        $template_id = $request->input('template_id');
        // limit to actors with email
        $contacts = MatterActors::where([['matter_id', $matter->id], ['role_code', 'CNT']])->whereNotNull('email');
        if ($contacts->count() === 0) {
            $contacts = MatterActors::select('actor_id', 'name', 'display_name', 'first_name')
                ->where([['matter_id', $matter->id]])->whereNotNull('email')->distinct();
        }
        $contacts = $contacts->get();
        $filters = $request->except(['page']);
        $members = new TemplateMember;
        $tableComments = $members->getTableComments();
        $oldfilters = [];
        $view = 'documents.select';
        $event = null;
        $task = null;
        if (! empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($value != '') {
                    switch ($key) {
                        case 'Category':
                            $members = $members->whereLike('category', "{$value}%");
                            $oldfilters['Category'] = $value;
                            break;
                        case 'Language':
                            $members = $members->whereLike('language', "{$value}%");
                            $oldfilters['Language'] = $value;
                            break;
                        case 'Name':
                            $members = $members->whereHas('class', function ($query) use ($value) {
                                $query->whereLike('name', "{$value}%");
                            });
                            $oldfilters['Name'] = $value;
                            break;
                        case 'Summary':
                            $members = $members->whereLike('summary', "{$value}%");
                            $oldfilters['Name'] = $value;
                            break;
                        case 'Style':
                            $members = $members->whereLike('style', "{$value}%");
                            $oldfilters['Style'] = $value;
                            break;
                        case 'EventName':
                            $members = $members->whereHas('class', function ($query) use ($value) {
                                $query->whereHas('eventNames', function ($q2) use ($value) {
                                    $q2->where('event_name_code', "$value");
                                });
                            });
                            $oldfilters['EventName'] = $value;
                            // specific view for within event window
                            $view = 'documents.select2';
                            break;
                        case 'Event':
                            $event = Event::where('id', "$value")->first();
                            break;
                        case 'Task':
                            $task = Task::where('id', "$value")->first();
                            $event = $task->trigger;
                            break;
                    }
                }
            }
        }
        if ($view == 'documents.select') {
            //  We exclude members linked to any of event or task
            $members = $members->whereHas('class', function ($query) {
                $query->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('event_class_lnk')
                        ->whereRaw('template_classes.id = event_class_lnk.template_class_id');
                });
            });
        }
        $members = $members->orderBy('summary')->get();

        return view($view, compact('matter', 'members', 'contacts', 'tableComments', 'oldfilters', 'event', 'task'));
    }

    /*
      Prepare a mailto: href with template and data from the matter
      *
      * @param  \Illuminate\Http\Request  $request
      * @param  \App\Models\TemplateMember $member
      * @return \Illuminate\Http\Response
    */
    public function mailto(TemplateMember $member, Request $request)
    {
        // Todo Add field for maually add an address
        $data = [];
        $subject = Blade::compileString($member->subject);
        $blade = Blade::compileString($member->body);

        // Get contacts list
        $sendto_ids = [];
        $cc_ids = [];
        foreach ($request->except(['page']) as $attribute => $value) {
            if (str_starts_with($attribute, 'sendto')) {
                $sendto_ids[] = substr($attribute, 7);
            }
            if (str_starts_with($attribute, 'ccto')) {
                $cc_ids[] = substr($attribute, 5);
            }
        }
        if (count($sendto_ids) != 0) {
            $mailto = 'mailto:'.implode(',', Actor::whereIn('id', $sendto_ids)->pluck('email')->all());
            $sep = '?';
            $matter = Matter::find($request->matter_id);
            $event = Event::find($request->event_id);
            $task = Task::find($request->task_id);
            $description = implode("\n", $matter->getDescription($member->language));
            if (count($cc_ids) != 0) {
                $mailto .= $sep.'cc='.implode(',', Actor::whereIn('id', $cc_ids)->pluck('email')->all());
                $sep = '&';
            }
            if ($member->subject != '') {
                $content = $this->renderTemplate($subject, compact('description', 'matter', 'event', 'task'));
                if (is_array($content)) {
                    if (array_key_exists('error', $content)) {
                        return $content;
                    }
                } else {
                    $mailto .= $sep.'subject='.rawurlencode($content);
                    $sep = '&';
                }
            }
            $content = $this->renderTemplate($blade, compact('description', 'matter', 'event', 'task'));
            if (is_array($content)) {
                if (array_key_exists('error', $content)) {
                    return $content;
                }
            } else {
                if ($member->format == 'HTML') {
                    $mailto .= $sep.'html-body='.rawurlencode($content);
                } else {
                    $mailto .= $sep.'body='.rawurlencode($content);
                }

                return json_encode(['mailto' => $mailto]);
            }
        } else {
            return json_encode(['message' => 'You need to select at least one contact.']);
        }
    }

    private function renderTemplate(string $template, array $data)
    {
        try {
            return Blade::render($template, $data);
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
