<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
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

    public function show(User $user)
    {
        Gate::authorize('readonly');
        $userInfo = $user->load(['company:id,name', 'roleInfo']);

        if (request()->wantsJson()) {
            return response()->json($userInfo);
        }

        $table = new Actor;
        $userComments = $table->getTableComments();
        return view('user.show', compact('userInfo', 'userComments'));
    }

    public function edit(User $user)
    {
        Gate::authorize('admin');
        $table = new Actor;
        $userComments = $table->getTableComments();

        return view('user.edit', compact('user', 'userComments'));
    }

    public function profile()
    {
        $userInfo = Auth::user()->load(['company:id,name', 'roleInfo']);
        $table = new Actor;
        $userComments = $table->getTableComments();

        // Add a flag to indicate this is the profile view
        $isProfileView = true;

        return view('user.profile', compact('userInfo', 'userComments', 'isProfileView'));
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('admin');
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

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'password' => 'nullable|confirmed|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[^a-zA-Z0-9]/',
            'email' => 'required|email',
            'language' => 'required|string|max:5',
        ]);

        $request->merge(['updater' => $user->login]);

        $dataToUpdate = [
            'email' => $request->email,
            'language' => $request->language,
            'updater' => $user->login,
        ];

        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }

        $user->update($dataToUpdate);

        // Update locale for the current session
        if ($request->filled('language')) {
            // Set application locale to the full locale (e.g., 'en_US', 'fr')
            app()->setLocale($request->language);

            // Store the locale in session
            session(['locale' => $request->language]);
        }

        return redirect()->route('user.profile')->with('success', 'Profile updated successfully');
    }

    public function destroy(User $user)
    {
        Gate::authorize('admin');
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
