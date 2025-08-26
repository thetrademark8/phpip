<?php

/**
 * Example usage of ActorPolicy in controllers and middleware
 * 
 * This file demonstrates how to integrate the ActorPolicy with existing
 * Laravel authorization patterns in the application.
 */

// Example 1: Using the policy in ActorController methods
class ActorControllerUpdated extends Controller
{
    public function index(Request $request)
    {
        // Replace the generic 'readonly' gate with specific actor policy
        Gate::authorize('viewAny', Actor::class);
        
        $query = Actor::query();
        
        // Apply user-specific filtering for CLI users
        $user = Auth::user();
        if (PermissionHelper::isClient($user)) {
            // Filter actors to only those related to user's matters
            $userMatterIds = $user->matters()->pluck('id')->toArray();
            if (!empty($userMatterIds)) {
                $query->whereHas('matters', function ($q) use ($userMatterIds) {
                    $q->whereIn('matter.id', $userMatterIds);
                });
            } else {
                // If no matters, return empty result
                $query->whereRaw('1 = 0');
            }
        }
        
        // Continue with existing filtering logic...
        $actorslist = $query->paginate(15);
        
        // Filter viewable fields based on user permissions
        $actorslist->getCollection()->transform(function ($actor) use ($user) {
            $viewableFields = app(ActorPolicy::class)->getViewableFields($user, $actor);
            
            if (in_array('*', $viewableFields)) {
                return $actor; // DBA sees all fields
            }
            
            return $actor->only(array_merge(['id'], $viewableFields));
        });

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

    public function show(Actor $actor)
    {
        // Use specific policy method instead of generic gate
        Gate::authorize('view', $actor);
        
        $user = Auth::user();
        $actorInfo = $actor->load(['company:id,name', 'parent:id,name', 'site:id,name', 'droleInfo', 'countryInfo:iso,name', 'country_mailingInfo:iso,name', 'country_billingInfo:iso,name', 'nationalityInfo:iso,name']);
        
        // Filter viewable fields
        $policy = app(ActorPolicy::class);
        $viewableFields = $policy->getViewableFields($user, $actor);
        
        if (!in_array('*', $viewableFields)) {
            $filteredActor = $actorInfo->only(array_merge(['id'], $viewableFields));
            // Handle relationships
            $allowedRelations = array_intersect(['company', 'parent', 'site', 'droleInfo', 'countryInfo'], $viewableFields);
            foreach ($allowedRelations as $relation) {
                if ($actorInfo->relationLoaded($relation)) {
                    $filteredActor[$relation] = $actorInfo->$relation;
                }
            }
            $actorInfo = $filteredActor;
        }
        
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
            'editable_fields' => $policy->getEditableFields($user, $actor),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Actor::class);
        
        $request->validate([
            'name' => 'required|max:100',
            'email' => 'email|nullable',
        ]);
        
        $request->merge(['creator' => Auth::user()->login]);
        $actor = Actor::create($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('actor.index')
                ->with('success', 'Actor created successfully');
        }

        return $actor;
    }

    public function update(Request $request, Actor $actor)
    {
        Gate::authorize('update', $actor);
        
        $user = Auth::user();
        $policy = app(ActorPolicy::class);
        
        // Validate field-level permissions
        $inputData = $request->except(['_token', '_method']);
        $editableFields = $policy->getEditableFields($user, $actor);
        
        // Filter out fields user cannot edit
        $allowedData = array_intersect_key($inputData, array_flip($editableFields));
        
        // Provide detailed error for unauthorized fields
        $unauthorizedFields = array_diff(array_keys($inputData), $editableFields);
        if (!empty($unauthorizedFields)) {
            return back()->withErrors([
                'authorization' => 'You do not have permission to edit the following fields: ' . implode(', ', $unauthorizedFields)
            ]);
        }
        
        $request->validate([
            'email' => 'email|nullable',
            'ren_discount' => 'numeric',
        ]);
        
        $allowedData['updater'] = $user->login;
        $actor->update($allowedData);

        return redirect()->back()
            ->with('success', 'Actor updated successfully');
    }

    public function destroy(Actor $actor)
    {
        Gate::authorize('delete', $actor);
        
        $actor->delete();

        if (request()->header('X-Inertia')) {
            return redirect()->route('actor.index')
                ->with('success', 'Actor deleted successfully');
        }

        return response()->json(['success' => true]);
    }
    
    /**
     * AJAX endpoint for field-specific updates with granular permissions
     */
    public function updateField(Request $request, Actor $actor, string $field)
    {
        $user = Auth::user();
        $policy = app(ActorPolicy::class);
        
        // Check field-specific permission
        if (!$policy->editField($user, $actor, $field)) {
            $details = $policy->getFieldPermissionDetails($user, $actor, $field);
            return response()->json([
                'error' => 'Permission denied',
                'message' => $details['reason'],
                'field' => $field,
                'field_category' => $details['field_category']
            ], 403);
        }
        
        $value = $request->input('value');
        
        // Additional validation based on field type
        if ($field === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email format'], 422);
        }
        
        if (in_array($field, ['ren_discount']) && !is_numeric($value)) {
            return response()->json(['error' => 'Field must be numeric'], 422);
        }
        
        $actor->update([
            $field => $value,
            'updater' => $user->login
        ]);
        
        return response()->json([
            'success' => true,
            'field' => $field,
            'value' => $actor->$field
        ]);
    }
}

// Example 2: Middleware for additional actor access control
class ActorAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // If accessing specific actor
        if ($request->route('actor')) {
            $actor = $request->route('actor');
            $policy = app(ActorPolicy::class);
            
            // Additional business logic checks
            if ($policy->view($user, $actor)) {
                // Log access for audit trail
                Log::info('Actor accessed', [
                    'user_id' => $user->id,
                    'user_role' => $user->default_role,
                    'actor_id' => $actor->id,
                    'action' => $request->route()->getActionMethod()
                ]);
            }
        }
        
        return $next($request);
    }
}

// Example 3: API Resource with field filtering
class ActorResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = Auth::user();
        $policy = app(ActorPolicy::class);
        $viewableFields = $policy->getViewableFields($user, $this->resource);
        
        if (in_array('*', $viewableFields)) {
            // DBA sees everything
            return parent::toArray($request);
        }
        
        // Build filtered response
        $data = [];
        
        // Always include ID
        $data['id'] = $this->id;
        
        // Include allowed fields
        foreach ($viewableFields as $field) {
            if (isset($this->resource->$field)) {
                $data[$field] = $this->resource->$field;
            }
        }
        
        // Handle relationships based on permissions
        if (in_array('company.name', $viewableFields) && $this->company) {
            $data['company'] = [
                'id' => $this->company->id,
                'name' => $this->company->name
            ];
        }
        
        return $data;
    }
}

// Example 4: Form Request with field validation
class ActorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->route('actor');
        $user = $this->user();
        
        return app(ActorPolicy::class)->update($user, $actor);
    }
    
    public function rules(): array
    {
        $actor = $this->route('actor');
        $user = $this->user();
        $policy = app(ActorPolicy::class);
        
        $rules = [];
        $editableFields = $policy->getEditableFields($user, $actor);
        
        // Apply validation rules only for editable fields
        if (in_array('name', $editableFields)) {
            $rules['name'] = 'required|max:100';
        }
        
        if (in_array('email', $editableFields)) {
            $rules['email'] = 'nullable|email|max:255';
        }
        
        if (in_array('VAT_number', $editableFields)) {
            $rules['VAT_number'] = 'nullable|string|max:45';
        }
        
        if (in_array('ren_discount', $editableFields)) {
            $rules['ren_discount'] = 'nullable|numeric|min:0|max:100';
        }
        
        return $rules;
    }
    
    public function messages(): array
    {
        return [
            'name.required' => 'Actor name is required',
            'email.email' => 'Please provide a valid email address',
            'ren_discount.numeric' => 'Renewal discount must be a number',
        ];
    }
}

// Example 5: Blade/Vue.js integration helper
class ActorPermissionHelper
{
    public static function getFieldPermissionsForFrontend(User $user, Actor $actor): array
    {
        $policy = app(ActorPolicy::class);
        
        return [
            'can_view' => $policy->view($user, $actor),
            'can_update' => $policy->update($user, $actor),
            'can_delete' => $policy->delete($user, $actor),
            'viewable_fields' => $policy->getViewableFields($user, $actor),
            'editable_fields' => $policy->getEditableFields($user, $actor),
            'field_categories' => [
                'basic_info' => array_intersect($policy->getEditableFields($user, $actor), [
                    'name', 'first_name', 'display_name', 'phy_person', 'small_entity'
                ]),
                'contact' => array_intersect($policy->getEditableFields($user, $actor), [
                    'email', 'phone', 'address', 'city', 'country'
                ]),
                'financial' => array_intersect($policy->getEditableFields($user, $actor), [
                    'VAT_number', 'registration_no', 'ren_discount'
                ]),
            ]
        ];
    }
}