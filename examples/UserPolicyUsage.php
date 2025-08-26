<?php

/**
 * Example of how to use the UserPolicy in controllers
 * 
 * This file demonstrates various ways to integrate the UserPolicy
 * with Laravel's authorization system.
 */

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Update user profile (example implementation)
     */
    public function updateProfile(Request $request, User $user)
    {
        // Method 1: Using Gate facade
        if (!Gate::allows('updateProfile', $user)) {
            abort(403, 'You can only update your own profile.');
        }

        // Method 2: Using authorize helper
        $this->authorize('updateProfile', $user);

        // Validate and update profile...
    }

    /**
     * Update a specific field (example implementation)
     */
    public function updateField(Request $request, User $user, string $field)
    {
        // Method 1: Using Gate with multiple parameters
        if (!Gate::allows('updateField', [$user, $field])) {
            abort(403, "You are not authorized to edit the '{$field}' field.");
        }

        // Method 2: Using policy directly for detailed feedback
        $policy = app(\App\Policies\UserPolicy::class);
        $result = $policy->canEditFieldWithReason(auth()->user(), $user, $field);
        
        if (!$result['allowed']) {
            return response()->json([
                'error' => $result['reason']
            ], 403);
        }

        // Validate and update field...
    }

    /**
     * Get editable fields for current user
     */
    public function getEditableFields(User $user)
    {
        $this->authorize('view', $user);

        $policy = app(\App\Policies\UserPolicy::class);
        $editableFields = $policy->getEditableFields(auth()->user()->default_role);

        return response()->json([
            'editable_fields' => $editableFields
        ]);
    }

    /**
     * Admin function to update any user
     */
    public function adminUpdateUser(Request $request, User $user)
    {
        $this->authorize('updateAny', $user);

        // Admin can update any field for any user...
    }

    /**
     * Form validation example using policy
     */
    public function validateProfileForm(Request $request, User $user)
    {
        $this->authorize('updateProfile', $user);

        $policy = app(\App\Policies\UserPolicy::class);
        $editableFields = $policy->getEditableFields(auth()->user()->default_role);

        // Only validate fields that the user can edit
        $rules = [];
        if (in_array('name', $editableFields)) {
            $rules['name'] = 'required|string|max:255';
        }
        if (in_array('email', $editableFields)) {
            $rules['email'] = 'required|email|unique:users,email,' . $user->id;
        }
        if (in_array('default_role', $editableFields)) {
            $rules['default_role'] = 'nullable|in:CLI,DBRO,DBRW,DBA';
        }

        $request->validate($rules);

        // Process the form...
    }
}

/**
 * Example middleware usage
 */
class EnsureCanEditFieldMiddleware
{
    public function handle($request, \Closure $next, $field = null)
    {
        $user = $request->route('user');
        $fieldToEdit = $field ?: $request->get('field');

        if (!Gate::allows('updateField', [$user, $fieldToEdit])) {
            abort(403, "You cannot edit the '{$fieldToEdit}' field.");
        }

        return $next($request);
    }
}

/**
 * Example API Resource with field filtering
 */
class UserResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    public function toArray($request)
    {
        $policy = app(\App\Policies\UserPolicy::class);
        $editableFields = $policy->getEditableFields(auth()->user()->default_role ?? 'CLI');

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
        ];

        // Only include sensitive fields if user can edit them
        if (in_array('default_role', $editableFields)) {
            $data['default_role'] = $this->default_role;
        }

        if (in_array('company_id', $editableFields)) {
            $data['company_id'] = $this->company_id;
            $data['company'] = $this->company;
        }

        $data['editable_fields'] = $editableFields;

        return $data;
    }
}

/**
 * Example Inertia.js integration
 */
class InertiaUserController extends Controller
{
    public function edit(User $user)
    {
        $this->authorize('view', $user);

        $policy = app(\App\Policies\UserPolicy::class);
        $editableFields = $policy->getEditableFields(auth()->user()->default_role ?? 'CLI');

        return inertia('User/Edit', [
            'user' => $user,
            'editableFields' => $editableFields,
            'fieldRestrictions' => [
                'default_role' => !in_array('default_role', $editableFields),
                'company' => !in_array('company', $editableFields),
            ]
        ]);
    }
}