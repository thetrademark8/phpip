<?php

namespace App\Http\Controllers;

use App\Models\TemplateMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TemplateMemberController extends Controller
{
    public $languages = ['fr' => 'Français',
        'en' => 'English',
        'de' => 'Deutsch'];

    public function index(Request $request)
    {
        $Summary = $request->summary;
        $Style = $request->style;
        $Language = $request->language;
        $Class = $request->class;
        $Format = $request->format;
        $Category = $request->category;
        $template_members = TemplateMember::query();
        if (! is_null($Summary)) {
            $template_members = $template_members->where('summary', 'LIKE', "%$Summary%");
        }
        if (! is_null($Category)) {
            $template_members = $template_members->where('category', 'LIKE', "$Category%");
        }
        if (! is_null($Language)) {
            $template_members = $template_members->where('language', 'LIKE', "$Language%");
        }
        if (! is_null($Class)) {
            $template_members = $template_members->whereHas('class', function ($query) use ($Class) {
                $query->where('name', 'LIKE', "$Class%");
            });
        }
        if (! is_null($Format)) {
            $template_members = $template_members->where('format', 'like', $Format.'%');
        }
        if (! is_null($Style)) {
            $template_members = $template_members->where('style', 'LIKE', "$Style%");
        }

        $query = $template_members->orderBy('summary');

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        return Inertia::render('TemplateMember/Index', [
            'template_members' => $query->with('class')->paginate(15),
            'filters' => $request->only(['summary', 'style', 'language', 'class', 'format', 'category']),
            'sort' => 'summary',
            'direction' => 'asc',
            'languages' => $this->languages
        ]);
    }

    public function create(Request $request)
    {
        $table = new TemplateMember;
        $tableComments = $table->getTableComments();
        $languages = $this->languages;

        if ($request->wantsJson()) {
            return response()->json([
                'tableComments' => $tableComments,
                'languages' => $languages
            ]);
        }

        return view('template-members.create', compact('tableComments', 'languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required',
            'language' => 'required',
        ]);
        $request->merge(['creator' => Auth::user()->login]);
        $templateMember = TemplateMember::create($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('template-member.index')
                ->with('success', 'Template member created successfully.');
        }

        return $templateMember;
    }

    public function show(TemplateMember $templateMember, Request $request)
    {
        $tableComments = $templateMember->getTableComments();
        $templateMember->load(['class']);
        $languages = $this->languages;

        if ($request->ajax()) {
            return response()->json([
                'templateMember' => $templateMember,
                'tableComments' => $tableComments,
                'languages' => $languages
            ]);
        }

        return view('template-members.show', compact('templateMember', 'languages', 'tableComments'));
    }

    public function edit(TemplateMember $templateMember)
    {
        //
    }

    public function update(Request $request, TemplateMember $templateMember)
    {
        $request->merge(['updater' => Auth::user()->login]);
        $templateMember->update($request->except(['_token', '_method']));

        return $templateMember;
    }

    public function destroy(TemplateMember $templateMember)
    {
        $templateMember->delete();

        return response()->json(['success' => 'Template deleted']);
    }
}
