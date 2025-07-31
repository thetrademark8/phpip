<?php

namespace App\Http\Controllers;

use App\Models\DefaultActor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DefaultActorController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'asc');
        
        $Actor = $request->input('Actor');
        $Role = $request->input('Role');
        $Country = $request->input('Country');
        $Category = $request->input('Category');
        $Client = $request->input('Client');
        $default_actor = new DefaultActor;

        if (! is_null($Actor)) {
            $default_actor = $default_actor->whereHas('actor', function ($q) use ($Actor) {
                $q->where('name', 'like', $Actor.'%');
            });
        }
        if (! is_null($Role)) {
            $default_actor = $default_actor->whereHas('roleInfo', function ($q) use ($Role) {
                $q->where('name', 'like', $Role.'%');
            });
        }
        if (! is_null($Country)) {
            $default_actor = $default_actor->whereHas('country', function ($q) use ($Country) {
                $q->where('name', 'like', $Country.'%');
            });
        }
        if (! is_null($Category)) {
            $default_actor = $default_actor->whereHas('category', function ($q) use ($Category) {
                $q->where('category', 'like', $Category.'%');
            });
        }
        if (! is_null($Client)) {
            $default_actor = $default_actor->whereHas('client', function ($q) use ($Client) {
                $q->where('name', 'like', $Client.'%');
            });
        }
        
        // Apply sorting
        if ($sort === 'actor') {
            $default_actor = $default_actor->join('actor', 'default_actor.actor_id', '=', 'actor.id')
                ->orderBy('actor.name', $direction)
                ->select('default_actor.*');
        } elseif ($sort === 'role') {
            $default_actor = $default_actor->orderBy('role', $direction);
        } elseif ($sort === 'country') {
            $default_actor = $default_actor->orderBy('for_country', $direction);
        } elseif ($sort === 'category') {
            $default_actor = $default_actor->orderBy('for_category', $direction);
        } elseif ($sort === 'client') {
            $default_actor = $default_actor->leftJoin('actor as client', 'default_actor.for_client', '=', 'client.id')
                ->orderBy('client.name', $direction)
                ->select('default_actor.*');
        } else {
            $default_actor = $default_actor->orderBy($sort, $direction);
        }
        
        $default_actors = $default_actor->with(['roleInfo:code,name', 'actor:id,name', 'client:id,name', 'category:code,category', 'country:iso,name'])
            ->paginate(25)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($default_actors);
        }

        return Inertia::render('DefaultActor/Index', [
            'defaultActors' => $default_actors,
            'filters' => $request->only(['Actor', 'Role', 'Country', 'Category', 'Client']),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        $table = new DefaultActor;
        $tableComments = $table->getTableComments();

        return view('default_actor.create', compact('tableComments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'actor_id' => 'required',
            'role' => 'required',
        ]);

        $defaultActor = DefaultActor::create($request->except(['_token', '_method']));
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('default_actor.index')
                ->with('success', 'Default actor created successfully');
        }
        
        return $defaultActor;
    }

    public function show(DefaultActor $default_actor)
    {
        $default_actor->load(['roleInfo:code,name', 'actor:id,name', 'client:id,name', 'category:code,category', 'country:iso,name']);
        
        if (request()->wantsJson()) {
            return response()->json($default_actor);
        }
        
        $tableComments = $default_actor->getTableComments();
        return view('default_actor.show', compact('default_actor', 'tableComments'));
    }

    public function update(Request $request, DefaultActor $default_actor)
    {
        $request->validate([
            'actor_id' => 'sometimes|required',
            'role' => 'sometimes|required',
        ]);
        $default_actor->update($request->except(['_token', '_method']));
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('default_actor.index')
                ->with('success', 'Default actor updated successfully');
        }

        return $default_actor;
    }

    public function destroy(Request $request, DefaultActor $default_actor)
    {
        $default_actor->delete();
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('default_actor.index')
                ->with('success', 'Default actor deleted successfully');
        }

        return $default_actor;
    }
    
    public function autocomplete(Request $request)
    {
        $query = $request->get('query', '');
        
        $defaultActors = DefaultActor::with(['actor:id,name', 'roleInfo:code,name'])
            ->whereHas('actor', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->take(10)
            ->get()
            ->map(function ($da) {
                return [
                    'id' => $da->id,
                    'name' => $da->actor->name . ' (' . $da->roleInfo->name . ')',
                ];
            });

        return response()->json($defaultActors);
    }
}
