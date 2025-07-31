<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $Code = $request->input('Code');
        $Name = $request->input('Name');
        $sort = $request->input('sort', 'code');
        $direction = $request->input('direction', 'asc');
        
        $role = Role::query();

        if (! is_null($Code)) {
            $role = $role->whereLike('code', $Code.'%');
        }

        if (! is_null($Name)) {
            $role = $role->whereJsonLike('name', $Name);
        }

        // Apply sorting
        $role->orderBy($sort, $direction);

        // Paginate results
        $roles = $role->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($roles);
        }

        return Inertia::render('Role/Index', [
            'roles' => $roles,
            'filters' => $request->only(['Code', 'Name']),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        $table = new Role;
        $tableComments = $table->getTableComments();

        return view('role.create', compact('tableComments'));
    }

    public function store(Request $request)
    {
        $this->authorize('readwrite', Role::class);
        
        $request->validate([
            'code' => 'required|unique:actor_role|max:5',
            'name' => 'required|max:45',
            'display_order' => 'numeric|nullable',
            'notes' => 'nullable|string',
            'shareable' => 'boolean',
            'show_ref' => 'boolean',
            'show_company' => 'boolean',
            'show_rate' => 'boolean',
            'show_date' => 'boolean',
        ]);
        $request->merge(['creator' => Auth::user()->login]);

        $role = Role::create($request->except(['_token', '_method']));
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('role.index')
                ->with('success', 'Role created successfully');
        }
        
        return $role;
    }

    public function show(Role $role)
    {
        if (request()->wantsJson()) {
            return response()->json($role);
        }
        
        $tableComments = $role->getTableComments();

        return view('role.show', compact('role', 'tableComments'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('readwrite', Role::class);
        
        $request->validate([
            'name' => 'required|max:45',
            'display_order' => 'numeric|nullable',
            'notes' => 'nullable|string',
            'shareable' => 'boolean',
            'show_ref' => 'boolean',
            'show_company' => 'boolean',
            'show_rate' => 'boolean',
            'show_date' => 'boolean',
        ]);
        
        $request->merge(['updater' => Auth::user()->login]);
        $role->update($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('role.index')
                ->with('success', 'Role updated successfully');
        }
        
        return $role;
    }

    public function destroy(Role $role)
    {
        $this->authorize('readwrite', Role::class);
        
        $role->delete();

        if (request()->header('X-Inertia')) {
            return redirect()->route('role.index')
                ->with('success', 'Role deleted successfully');
        }
        
        return $role;
    }
    
    public function autocomplete(Request $request)
    {
        $query = $request->get('query', '');
        
        $roles = Role::where('name', 'like', "%{$query}%")
            ->take(10)
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->code,
                    'name' => $role->name,
                ];
            });

        return response()->json($roles);
    }
}
