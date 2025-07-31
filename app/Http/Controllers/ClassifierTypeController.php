<?php

namespace App\Http\Controllers;

use App\Models\ClassifierType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClassifierTypeController extends Controller
{
    public function index(Request $request)
    {
        $Code = $request->input('Code');
        $Type = $request->input('Type');
        $Category = $request->input('Category');
        $sort = $request->input('sort', 'code');
        $direction = $request->input('direction', 'asc');
        $page = $request->input('page', 1);

        $query = ClassifierType::query()->with(['category:code,category']);

        // Apply filters
        if (!empty($Code)) {
            $query->whereLike('code', $Code.'%');
        }

        if (!empty($Type)) {
            $query->whereJsonLike('type', $Type);
        }

        if (!empty($Category)) {
            $query->whereHas('category', function ($q) use ($Category) {
                $q->whereJsonLike('category', $Category);
            });
        }

        // Apply sorting
        if ($sort === 'category') {
            $query->leftJoin('category', 'classifier_type.for_category', '=', 'category.code')
                  ->orderBy('category.category', $direction)
                  ->select('classifier_type.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $classifierTypes = $query->paginate(15);

        // For autocomplete/API requests
        if ($request->wantsJson()) {
            return response()->json($classifierTypes->items());
        }

        return Inertia::render('ClassifierType/Index', [
            'classifierTypes' => $classifierTypes,
            'filters' => $request->only(['Code', 'Type', 'Category']),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        $table = new ClassifierType;
        $tableComments = $table->getTableComments();

        return view('classifier_type.create', compact('tableComments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:classifier_type|max:5',
            'type' => 'required|max:45',
        ]);
        $request->merge(['creator' => Auth::user()->login]);

        $classifierType = ClassifierType::create($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('classifier_type.index')
                ->with('success', 'Classifier type created successfully');
        }

        return $classifierType;
    }

    public function show(ClassifierType $classifier_type)
    {
        $classifier_type->load(['category:code,category']);

        if (request()->wantsJson()) {
            return response()->json($classifier_type);
        }

        $tableComments = $classifier_type->getTableComments();
        return view('classifier_type.show', compact('classifier_type', 'tableComments'));
    }

    public function update(Request $request, ClassifierType $classifierType)
    {
        $request->validate([
            'code' => 'required|max:5|unique:classifier_type,code,'.$classifierType->code.',code',
            'type' => 'required|max:45',
        ]);
        
        $request->merge(['updater' => Auth::user()->login]);
        $classifierType->update($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('classifier_type.index')
                ->with('success', 'Classifier type updated successfully');
        }

        return $classifierType;
    }

    public function destroy(ClassifierType $classifierType)
    {
        $classifierType->delete();

        if (request()->header('X-Inertia')) {
            return redirect()->route('classifier_type.index')
                ->with('success', 'Classifier type deleted successfully');
        }

        return $classifierType;
    }

    public function autocomplete(Request $request)
    {
        $query = ClassifierType::whereJsonLike('type', $request->term)
            ->orderBy('type');

        $types = $query->take(10)->get();
        $results = $types->map(function ($item) {
            return [
                'key' => $item->code,
                'value' => $item->type,
            ];
        })->toArray();

        return response()->json($results);
    }
}
