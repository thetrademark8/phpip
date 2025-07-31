<?php

namespace App\Http\Controllers;

use App\Models\EventClassLnk;
use App\Models\EventName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class EventNameController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('except_client');
        
        // Get filters
        $code = $request->input('Code');
        $name = $request->input('Name');
        $sort = $request->input('sort', 'code');
        $direction = $request->input('direction', 'asc');
        
        // Base query with relationships
        $query = EventName::query()->with([
            'countryInfo:iso,name', 
            'categoryInfo:code,category', 
            'default_responsibleInfo:id,name'
        ]);
        
        // Apply filters
        if (!is_null($code)) {
            $query->whereLike('code', $code.'%');
        }

        if (!is_null($name)) {
            $query->whereJsonLike('name', $name);
        }

        // Apply sorting
        $locale = app()->getLocale();
        $baseLocale = substr($locale, 0, 2);
        
        $sortableColumns = [
            'code' => 'event_name.code',
            'name' => "JSON_UNQUOTE(JSON_EXTRACT(event_name.name, '$.\"{$baseLocale}\"'))",
            'is_task' => 'event_name.is_task',
            'status_event' => 'event_name.status_event',
            'country' => 'event_name.country',
            'category' => 'event_name.category',
        ];

        if (array_key_exists($sort, $sortableColumns)) {
            $query->orderByRaw("{$sortableColumns[$sort]} {$direction}");
        } else {
            // Default sorting by code
            $query->orderBy('code', $direction);
        }

        // Return JSON for AJAX requests (autocomplete, etc.)
        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        // Paginate results
        $eventNames = $query->paginate(21);
        $eventNames->appends($request->input())->links();

        // Return Inertia response for Vue.js frontend
        return Inertia::render('EventName/Index', [
            'eventNames' => $eventNames,
            'filters' => $request->only(['Code', 'Name']),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        Gate::authorize('except_client');
        
        $table = new EventName;
        $tableComments = $table->getTableComments();

        // Return JSON for modal dialog usage
        return response()->json([
            'comments' => $tableComments,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('except_client');
        
        $request->validate([
            'code' => 'required|unique:event_name|max:5',
            'name' => 'required|max:45',
            'notes' => 'max:160',
            'is_task' => 'boolean',
            'status_event' => 'boolean',
            'killer' => 'boolean',
            'use_matter_resp' => 'boolean',
            'country' => 'nullable|string|max:5',
            'category' => 'nullable|string|max:5',
            'default_responsible' => 'nullable|exists:users,id',
        ]);
        
        $request->merge(['creator' => Auth::user()->login]);
        EventName::create($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('eventname.index')
                ->with('success', 'Event name created successfully');
        }

        return response()->json(['redirect' => route('eventname.index')]);
    }

    public function show(EventName $eventname)
    {
        Gate::authorize('except_client');
        
        $tableComments = $eventname->getTableComments();
        $eventname->load(['countryInfo:iso,name', 'categoryInfo:code,category', 'default_responsibleInfo:id,name']);
        $links = EventClassLnk::where('event_name_code', '=', $eventname->code)
            ->with('templateClass:code,name')
            ->get();

        // Return JSON for modal/detail panel usage
        return response()->json([
            'eventName' => $eventname,
            'comments' => $tableComments,
            'templateLinks' => $links,
        ]);
    }

    public function update(Request $request, EventName $eventname)
    {
        Gate::authorize('except_client');
        
        $request->validate([
            'code' => 'required|unique:event_name,code,'.$eventname->code.',code|max:5',
            'name' => 'required|max:45',
            'notes' => 'max:160',
            'is_task' => 'boolean',
            'status_event' => 'boolean',
            'killer' => 'boolean',
            'use_matter_resp' => 'boolean',
            'country' => 'nullable|string|max:5',
            'category' => 'nullable|string|max:5',
            'default_responsible' => 'nullable|exists:users,id',
        ]);
        
        $request->merge(['updater' => Auth::user()->login]);
        $eventname->update($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('eventname.index')
                ->with('success', 'Event name updated successfully');
        }

        return $eventname;
    }

    public function destroy(EventName $eventname)
    {
        Gate::authorize('except_client');
        
        $eventname->delete();

        if (request()->header('X-Inertia')) {
            return redirect()->route('eventname.index')
                ->with('success', 'Event name deleted successfully');
        }

        return $eventname;
    }
}
