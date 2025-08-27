<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Models\User;
use App\Services\ProfileFieldConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('readonly');
        
        $Name = $request->input('Name');
        $Role = $request->input('Role');
        $Username = $request->input('Username');
        $Company = $request->input('Company');
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        $page = $request->input('page', 1);

        $query = User::query()->with(['company:id,name', 'roleInfo:code,name']);

        // Apply filters
        if (!empty($Name)) {
            $query->whereLike('name', $Name.'%');
        }

        if (!empty($Role)) {
            $query->whereHas('roleInfo', function ($q) use ($Role) {
                $q->whereJsonLike('name', $Role);
            });
        }

        if (!empty($Username)) {
            $query->whereLike('login', $Username.'%');
        }

        if (!empty($Company)) {
            $query->whereHas('company', function ($q) use ($Company) {
                $q->whereLike('name', $Company.'%');
            });
        }

        // Apply sorting
        if ($sort === 'role') {
            $query->leftJoin('role', 'users.default_role', '=', 'role.code')
                  ->orderBy('role.name', $direction)
                  ->select('users.*');
        } elseif ($sort === 'company') {
            $query->leftJoin('actor', 'users.company_id', '=', 'actor.id')
                  ->orderBy('actor.name', $direction)
                  ->select('users.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $users = $query->paginate(15);

        // For autocomplete/API requests
        if ($request->wantsJson()) {
            return response()->json($users->items());
        }

        return Inertia::render('User/Index', [
            'users' => $users,
            'filters' => $request->only(['Name', 'Role', 'Username', 'Company']),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');

        $table = new Actor;
        $userComments = $table->getTableComments();

        return view('user.create', compact('userComments'));
    }

    public function store(Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'name' => 'required|max:100',
            'login' => 'required|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'email' => 'required|email',
            'default_role' => 'required',
        ]);
        $request->merge(['creator' => Auth::user()->login]);

        $user = User::create($request->except(['_token', '_method', 'password_confirmation']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('user.index')
                ->with('success', 'User created successfully');
        }

        return $user;
    }

    public function profile()
    {
        $user = Auth::user()->load(['company:id,name', 'roleInfo:code,name']);

        return Inertia::render('User/Profile', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        Gate::forUser(auth()->user())->authorize('update', $user);

        $request->validate([
            'name' => 'required|max:100',
            'login' => 'required|unique:users,login,'.$user->id,
            'password' => 'nullable|confirmed|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[^a-zA-Z0-9]/',
            'email' => 'required|email',
            'default_role' => 'required',
            'language' => 'required|string|max:5',
        ]);
        
        $request->merge(['updater' => Auth::user()->login]);
        
        $dataToUpdate = $request->except(['_token', '_method', 'password_confirmation']);
        
        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        } else {
            unset($dataToUpdate['password']);
        }
        
        $user->update($dataToUpdate);

        // Update locale for the current session if current user is updating their own profile
        if (Auth::id() === $user->id && $request->filled('language')) {
            app()->setLocale($request->language);
            session(['locale' => $request->language]);
        }

        if ($request->header('X-Inertia')) {
            return redirect()->route('user.index')
                ->with('success', 'User updated successfully');
        }

        return $user;
    }

    /**
     * Update the authenticated user's profile with field-level authorization
     * 
     * This method implements role-based field restrictions:
     * - CLI: Cannot edit 'default_role' or 'company_id'
     * - DBRO/DBRW: Cannot edit 'default_role'
     * - DBA: Can edit all fields
     * - Password can always be updated by the user themselves
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        Gate::forUser($user)->authorize('update', $user);

        // Determine what fields are being updated
        $fieldsToUpdate = $request->only([
            'name', 'email', 'phone', 'language', 'default_role', 'company_id'
        ]);

        $isPasswordUpdate = $request->filled('password');

        // Use the field configuration service for permission checking and validation
        $dataToUpdate = array_merge(['updater' => $user->login], $fieldsToUpdate);

        // Handle password update
        if ($isPasswordUpdate) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }

        // Update user data
        $user->update($dataToUpdate);

        // Update locale for the current session if language was changed
        if ($request->filled('language')) {
            app()->setLocale($request->language);
            session(['locale' => $request->language]);
        }

        // Determine success message
        $message = $isPasswordUpdate ? 'Password updated successfully' : 'Profile updated successfully';

        // Return the updated user data along with field configuration for frontend
        return redirect()->back()
            ->with('success', $message)
            ->with('user', $user->load(['company:id,name', 'roleInfo:code,name']));
    }

    public function destroy(User $user)
    {
        Gate::forUser(auth()->user())->authorize('delete', $user);

        $user->delete();

        if (request()->header('X-Inertia')) {
            return redirect()->route('user.index')
                ->with('success', 'User deleted successfully');
        }

        return $user;
    }

    public function autocomplete(Request $request)
    {
        $results = User::select('name as value', 'login as key')
            ->whereLike('name', "{$request->term}%")
            ->orWhereLike('login', "{$request->term}%")
            ->take(10)
            ->get();

        return response()->json($results->toArray());
    }
}
