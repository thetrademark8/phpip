<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Policies\ActorPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ActorController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Use ActorPolicy for authorization
        Gate::authorize('viewAny', Actor::class);
        
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

        // Filter actors for CLI users to only show actors related to their matters
        if ($user->default_role === 'CLI' || empty($user->default_role)) {
            $userMatterIds = $user->matters()->pluck('id')->toArray();
            if (!empty($userMatterIds)) {
                $query->whereHas('matters', function($q) use ($userMatterIds) {
                    $q->whereIn('matter.id', $userMatterIds);
                });
            } else {
                // CLI user with no matters can't see any actors
                $query->whereRaw('1 = 0');
            }
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
        $user = Auth::user();
        
        // Use ActorPolicy for create authorization
        Gate::authorize('create', Actor::class);
        
        $actor = new Actor;
        $actorComments = $actor->getTableComments();

        return Inertia::render('Actor/Create', [
            'comments' => $actorComments,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Use ActorPolicy for create authorization
        Gate::forUser($user)->authorize('create', Actor::class);

        // Simple validation rules
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'first_name' => 'nullable|string|max:100',
            'display_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'nationality' => 'nullable|string|max:2',
            'language' => 'nullable|string|max:5',
            'function' => 'nullable|string|max:100',
            'company_id' => 'nullable|exists:actor,id',
            'phy_person' => 'boolean',
            'small_entity' => 'boolean',
            'warn' => 'boolean',
        ]);

        $actorData = $validatedData;

        // Add system fields
        $actorData['creator'] = $user->login;

        // Log field-level permission denials for security monitoring
        $deniedFields = collect($request->except(['_token', '_method']))->keys()
            ->filter(function($field) use ($editableFields) {
                return !in_array($field, $editableFields) && !in_array($field, ['_token', '_method']);
            });

        if ($deniedFields->isNotEmpty()) {
            Log::warning('Actor creation attempted with restricted fields', [
                'user_id' => $user->id,
                'user_role' => $user->default_role,
                'denied_fields' => $deniedFields->toArray(),
                'ip' => $request->ip(),
            ]);
        }

        try {
            $actor = Actor::create($actorData);

            // Log successful creation
            Log::info('Actor created successfully', [
                'actor_id' => $actor->id,
                'user_id' => $user->id,
                'user_role' => $user->default_role,
                'fields_used' => array_keys($actorData),
            ]);

            if ($request->header('X-Inertia')) {
                return redirect()->route('actor.show', $actor)
                    ->with('success', __('actor.messages.created_successfully'));
            }

            return response()->json([
                'actor' => $actor,
                'message' => __('actor.messages.created_successfully'),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Actor creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $actorData,
            ]);

            if ($request->header('X-Inertia')) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('actor.messages.creation_failed'));
            }

            return response()->json([
                'message' => __('actor.messages.creation_failed'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Actor $actor)
    {
        $user = Auth::user();
        
        // Use ActorPolicy for view authorization
        Gate::forUser($user)->authorize('view', $actor);
        
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
        $user = Auth::user();
        
        // Use ActorPolicy for update authorization
        Gate::forUser($user)->authorize('update', $actor);
        
        $actorInfo = $actor->load(['company:id,name', 'parent:id,name', 'site:id,name', 'droleInfo', 'countryInfo:iso,name', 'country_mailingInfo:iso,name', 'country_billingInfo:iso,name', 'nationalityInfo:iso,name']);
        $actorComments = $actor->getTableComments();

        // Filter actor data based on user permissions
        $filteredActor = $this->filterActorData($actorInfo, $user);

        return Inertia::render('Actor/Edit', [
            'actor' => $filteredActor,
            'comments' => $actorComments,
        ]);
    }

    public function update(Request $request, Actor $actor)
    {
        $user = Auth::user();
        
        // Use ActorPolicy for update authorization
        Gate::forUser($user)->authorize('update', $actor);

        // Simple validation rules
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'first_name' => 'nullable|string|max:100',
            'display_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'nationality' => 'nullable|string|max:2',
            'language' => 'nullable|string|max:5',
            'function' => 'nullable|string|max:100',
            'company_id' => 'nullable|exists:actor,id',
            'phy_person' => 'boolean',
            'small_entity' => 'boolean',
            'warn' => 'boolean',
        ]);

        $updateData = $validatedData;

        // Add system fields
        $updateData['updater'] = $user->login;

        try {
            // Store original values for logging
            $originalValues = $actor->only(array_keys($updateData));
            
            $actor->update($updateData);

            // Log successful update
            Log::info('Actor updated successfully', [
                'actor_id' => $actor->id,
                'user_id' => $user->id,
                'user_role' => $user->default_role,
                'updated_fields' => array_keys($updateData),
                'original_values' => $originalValues,
                'new_values' => $updateData,
            ]);

            if ($request->header('X-Inertia')) {
                return redirect()->back()
                    ->with('success', __('actor.messages.updated_successfully'));
            }

            return response()->json([
                'actor' => $this->filterActorData($actor->fresh(), $user),
                'message' => __('actor.messages.updated_successfully'),
            ]);

        } catch (ValidationException $e) {
            // Re-throw validation exceptions with field-specific errors
            throw $e;
        } catch (\Exception $e) {
            Log::error('Actor update failed', [
                'actor_id' => $actor->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $updateData,
            ]);

            if ($request->header('X-Inertia')) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('actor.messages.update_failed'));
            }

            return response()->json([
                'message' => __('actor.messages.update_failed'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Actor $actor)
    {
        $user = Auth::user();

        // Use ActorPolicy for delete authorization
        Gate::authorize('delete', $actor);

        try {
            // Log deletion attempt
            Log::info('Actor deletion attempted', [
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'user_id' => $user->id,
                'user_role' => $user->default_role,
            ]);

            $actor->delete();

            // Log successful deletion
            Log::info('Actor deleted successfully', [
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'user_id' => $user->id,
                'user_role' => $user->default_role,
            ]);

            if (request()->header('X-Inertia')) {
                return redirect()->route('actor.index')
                    ->with('success', __('actor.messages.deleted_successfully'));
            }

            return response()->json([
                'success' => true,
                'message' => __('actor.messages.deleted_successfully'),
            ]);

        } catch (\Exception $e) {
            Log::error('Actor deletion failed', [
                'actor_id' => $actor->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            if (request()->header('X-Inertia')) {
                return redirect()->back()
                    ->with('error', __('actor.messages.deletion_failed'));
            }

            return response()->json([
                'success' => false,
                'message' => __('actor.messages.deletion_failed'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
