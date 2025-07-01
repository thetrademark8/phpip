<?php

namespace App\Http\Controllers;

use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RuleController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('readonly');
        $Task = $request->input('Task');
        $Trigger = $request->input('Trigger');
        $Country = $request->input('Country');
        $Origin = $request->input('Origin');
        $Detail = $request->input('Detail');
        $Type = $request->input('Type');
        $Category = $request->input('Category');
        $sort = $request->input('sort', 'task');
        $direction = $request->input('direction', 'asc');
        
        $rule = new Rule;
        $locale = app()->getLocale();
        // Normalize to the base locale (e.g., 'en' from 'en_US')
        $baseLocale = substr($locale, 0, 2);

        if (! is_null($Task)) {
            $rule = $rule->whereHas('taskInfo', function ($q) use ($Task) {
                $q->whereJsonLike('name', $Task);
            });
        }
        if (! is_null($Trigger)) {
            $rule = $rule->whereHas('trigger', function ($q) use ($Trigger) {
                $q->whereJsonLike('name', $Trigger);
            });
        }
        if (! is_null($Country)) {
            $rule = $rule->whereLike('for_country', $Country.'%');
        }
        if (! is_null($Category)) {
            $rule = $rule->whereHas('category', function ($q) use ($Category) {
                $q->whereJsonLike('category', $Category);
            });
        }

        if (! is_null($Detail)) {
            $rule = $rule->whereJsonLike('detail', $Detail);
        }

        if (! is_null($Type)) {
            $rule = $rule->whereHas('type', function ($q) use ($Type) {
                $q->whereJsonLike('type', $Type);
            });
        }
        if (! is_null($Origin)) {
            $rule = $rule->whereLike('for_origin', "{$Origin}%");
        }

        $query = $rule->with(['country:iso,name', 'trigger:code,name', 'category:code,category', 'origin:iso,name', 'type:code,type', 'taskInfo:code,name'])
            ->select('task_rules.*')
            ->join('event_name AS t', 't.code', '=', 'task_rules.task');

        // Apply sorting
        $sortableColumns = [
            'task' => "JSON_UNQUOTE(JSON_EXTRACT(t.name, '$.\"{$baseLocale}\"'))",
            'detail' => "JSON_UNQUOTE(JSON_EXTRACT(task_rules.detail, '$.\"{$baseLocale}\"'))",
            'trigger_event' => 'task_rules.trigger_event',
            'for_category' => 'task_rules.for_category',
            'for_country' => 'task_rules.for_country',
            'for_origin' => 'task_rules.for_origin',
            'for_type' => 'task_rules.for_type',
        ];

        if (array_key_exists($sort, $sortableColumns)) {
            $query = $query->orderByRaw("{$sortableColumns[$sort]} {$direction}");
        } else {
            // Default sorting
            $query = $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(t.name, '$.\"{$baseLocale}\"')) {$direction}");
        }

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $ruleslist = $query->paginate(15);
        $ruleslist->appends($request->input())->links();

        // Return Inertia response for Vue.js frontend
        return Inertia::render('Rule/Index', [
            'rules' => $ruleslist,
            'filters' => $request->only(['Task', 'Detail', 'Trigger', 'Category', 'Country', 'Origin', 'Type']),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(Rule $rule)
    {
        Gate::authorize('readonly');
        $ruleInfo = $rule->load([
            'trigger:code,name',
            'country:iso,name',
            'category:code,category',
            'origin:iso,name',
            'type:code,type',
            'taskInfo:code,name',
            'condition_eventInfo:code,name',
            'abort_onInfo:code,name',
            'responsibleInfo:login,name',
        ]);

        $ruleComments = $rule->getTableComments();

        // Always return JSON since we use modal dialogs via AJAX
        return response()->json([
            'rule' => $ruleInfo,
            'comments' => $ruleComments,
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');
        $rule = new Rule;
        $ruleComments = $rule->getTableComments();

        // Return JSON for modal dialog usage
        return response()->json([
            'comments' => $ruleComments,
        ]);
    }

    public function update(Request $request, Rule $rule)
    {
        Gate::authorize('admin');
        $this->validate($request, [
            'task' => 'sometimes|required',
            'trigger_event' => 'sometimes|required',
            'for_category' => 'sometimes|required',
            'cost' => 'nullable|numeric',
            'years' => 'nullable|numeric',
            'months' => 'nullable|numeric',
            'days' => 'nullable|numeric',
            'fee' => 'nullable|numeric',
            'use_before' => 'nullable|date',
            'use_after' => 'nullable|date',
        ]);
        $request->merge(['updater' => Auth::user()->login]);
        $rule->update($request->except(['_token', '_method']));

        return $rule;
    }

    public function store(Request $request)
    {
        Gate::authorize('admin');
        $this->validate($request, [
            'task' => 'required',
            'trigger_event' => 'required',
            'for_category' => 'required',
            'cost' => 'nullable|numeric',
            'years' => 'numeric',
            'months' => 'numeric',
            'days' => 'numeric',
            'fee' => 'nullable|numeric',
            'use_before' => 'nullable|date',
            'use_after' => 'nullable|date',
        ]);
        $request->merge(['creator' => Auth::user()->login]);
        $rule = Rule::create($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('rule.index')
                ->with('success', 'Rule created successfully');
        }

        return response()->json(['redirect' => route('rule.index')]);
    }

    public function destroy(Rule $rule)
    {
        Gate::authorize('admin');
        $rule->delete();

        if (request()->header('X-Inertia')) {
            return redirect()->route('rule.index')
                ->with('success', 'Rule deleted successfully');
        }

        return $rule;
    }
}
