<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ActorController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('readonly');
        $query = Actor::query();

        // Name search
        if ($request->filled('Name')) {
            $query->where('name', 'like', $request->Name.'%');
        }

        // First name search
        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', $request->first_name.'%');
        }

        // Display name search
        if ($request->filled('display_name')) {
            $query->where('display_name', 'like', $request->display_name.'%');
        }

        // Email search
        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }

        // Phone search
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%'.$request->phone.'%');
        }

        // Country filter
        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        // Default role filter
        if ($request->filled('default_role')) {
            $query->where('default_role', $request->default_role);
        }

        // Company/Employer search
        if ($request->filled('company')) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->company.'%');
            });
        }

        // Type selector (original logic)
        switch ($request->selector) {
            case 'phy_p':
                $query->where('phy_person', 1);
                break;
            case 'leg_p':
                $query->where('phy_person', 0);
                break;
            case 'warn':
                $query->where('warn', 1);
                break;
        }

        // Boolean filters
        if ($request->filled('phy_person') && $request->phy_person) {
            $query->where('phy_person', 1);
        }

        if ($request->filled('warn') && $request->warn) {
            $query->where('warn', 1);
        }

        if ($request->filled('has_login') && $request->has_login) {
            $query->whereNotNull('login');
        }

        // Handle sorting
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        
        // Map frontend column names to database columns
        $sortableColumns = [
            'name' => 'name',
            'first_name' => 'first_name',
            'display_name' => 'display_name',
            'company.name' => 'company_id',
            'phy_person' => 'phy_person',
        ];
        
        // Load relationships
        $query->with(['company:id,name', 'droleInfo:name', 'countryInfo:iso,name']);
        
        // Apply sorting
        if (isset($sortableColumns[$sortField])) {
            $column = $sortableColumns[$sortField];
            
            // Special handling for company.name
            if ($sortField === 'company.name') {
                $query->leftJoin('actor as company_actor', 'actor.company_id', '=', 'company_actor.id')
                    ->orderBy('company_actor.name', $sortDirection)
                    ->select('actor.*');
            } else {
                $query->orderBy($column, $sortDirection);
            }
        } else {
            // Default to name if invalid sort field
            $query->orderBy('name', 'asc');
        }

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $actorslist = $query->paginate(15);
        $actorslist->appends($request->input())->links();

        return Inertia::render('Actor/Index', [
            'actors' => $actorslist,
            'filters' => $request->only([
                'Name', 'first_name', 'display_name', 'company', 'email', 'phone', 
                'country', 'default_role', 'selector', 'phy_person', 'warn', 'has_login'
            ]),
            'sort' => $sortField,
            'direction' => $sortDirection,
        ]);
    }

    public function create()
    {
        Gate::authorize('readwrite');
        $actor = new Actor;
        $actorComments = $actor->getTableComments();

        return Inertia::render('Actor/Create', [
            'comments' => $actorComments,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('readwrite');
        $request->validate([
            'name' => 'required|max:100',
            'email' => 'email|nullable',
        ]);
        $request->merge(['creator' => Auth::user()->login]);

        return Actor::create($request->except(['_token', '_method']));
    }

    public function show(Actor $actor)
    {
        Gate::authorize('readonly');
        $actorInfo = $actor->load(['company:id,name', 'parent:id,name', 'site:id,name', 'droleInfo', 'countryInfo:iso,name', 'country_mailingInfo:iso,name', 'country_billingInfo:iso,name', 'nationalityInfo:iso,name']);
        $actorComments = $actor->getTableComments();

        if (request()->wantsJson()) {
            return response()->json([
                'actor' => $actorInfo,
                'comments' => $actorComments,
            ]);
        }

        return Inertia::render('Actor/Show', [
            'actor' => $actorInfo,
            'comments' => $actorComments,
        ]);
    }

    public function edit(Actor $actor)
    {
        //
    }

    public function update(Request $request, Actor $actor)
    {
        Gate::authorize('readwrite');
        $request->validate([
            'email' => 'email|nullable',
            'ren_discount' => 'numeric',
        ]);
        $request->merge(['updater' => Auth::user()->login]);
        $actor->update($request->except(['_token', '_method']));

        return $actor;
    }

    public function destroy(Actor $actor)
    {
        Gate::authorize('readwrite');
        $actor->delete();

        return $actor;
    }
}
