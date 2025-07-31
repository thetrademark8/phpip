<?php

namespace App\Http\Controllers;

use App\Models\MatterType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MatterTypeController extends Controller
{
    public function index(Request $request)
    {
        $Code = $request->input('Code');
        $Type = $request->input('Type');
        $sort = $request->input('sort', 'code');
        $direction = $request->input('direction', 'asc');
        
        $type = MatterType::query();

        if (! is_null($Code)) {
            $type = $type->whereLike('code', $Code.'%');
        }

        if (! is_null($Type)) {
            $type = $type->whereJsonLike('type', $Type);
        }

        // Apply sorting
        $type->orderBy($sort, $direction);

        // Paginate results
        $matter_types = $type->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($matter_types);
        }

        return Inertia::render('Type/Index', [
            'types' => $matter_types,
            'filters' => $request->only(['Code', 'Type']),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        $table = new MatterType;
        $tableComments = $table->getTableComments();

        return view('type.create', compact('tableComments'));
    }

    public function store(Request $request)
    {
        $this->authorize('readwrite', MatterType::class);
        
        $request->validate([
            'code' => 'required|unique:matter_type|max:5',
            'type' => 'required|max:45',
        ]);
        $request->merge(['creator' => Auth::user()->login]);

        $type = MatterType::create($request->except(['_token', '_method']));
        
        if ($request->header('X-Inertia')) {
            return redirect()->route('type.index')
                ->with('success', 'Type created successfully');
        }
        
        return $type;
    }

    public function show(MatterType $type)
    {
        if (request()->wantsJson()) {
            return response()->json($type);
        }
        
        $tableComments = $type->getTableComments();

        return view('type.show', compact('type', 'tableComments'));
    }

    public function update(Request $request, MatterType $type)
    {
        $this->authorize('readwrite', MatterType::class);
        
        $request->validate([
            'type' => 'required|max:45',
        ]);
        
        $request->merge(['updater' => Auth::user()->login]);
        $type->update($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('type.index')
                ->with('success', 'Type updated successfully');
        }
        
        return $type;
    }

    public function destroy(MatterType $type)
    {
        $this->authorize('readwrite', MatterType::class);
        
        $type->delete();

        if (request()->header('X-Inertia')) {
            return redirect()->route('type.index')
                ->with('success', 'Type deleted successfully');
        }
        
        return $type;
    }
}
