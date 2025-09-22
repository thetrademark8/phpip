<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('readonly');
        
        $Code = $request->input('Code');
        $Category = $request->input('Category');
        $sort = $request->input('sort', 'code');
        $direction = $request->input('direction', 'asc');
        
        $query = Category::query();

        if (! is_null($Code)) {
            $query->whereLike('code', $Code.'%');
        }

        if (! is_null($Category)) {
            $query->whereJsonLike('category', $Category);
        }

        // Load relationships
        $query->with(['displayWithInfo:code,category']);

        // Apply sorting
        $locale = app()->getLocale();
        $baseLocale = substr($locale, 0, 2);
        
        $sortableColumns = [
            'code' => 'code',
            'category' => "JSON_UNQUOTE(JSON_EXTRACT(category, '$.\"$baseLocale\"'))",
            'display_with' => 'display_with',
        ];

        if (array_key_exists($sort, $sortableColumns)) {
            if ($sort === 'category') {
                $query->orderByRaw("{$sortableColumns[$sort]} {$direction}");
            } else {
                $query->orderBy($sortableColumns[$sort], $direction);
            }
        } else {
            $query->orderBy('code', 'asc');
        }

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $categories = $query->paginate(15);
        $categories->appends($request->input())->links();

        return Inertia::render('Category/Index', [
            'categories' => $categories,
            'filters' => $request->only(['Code', 'Category']),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        Gate::authorize('readwrite');
        
        $category = new Category;
        $tableComments = $category->getTableComments();

        return response()->json([
            'comments' => $tableComments,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('readwrite');

        $request->validate([
            'code' => 'required|unique:matter_category|max:5',
            'category' => 'required|max:45',
            'display_with' => 'required',
        ]);
        $request->merge(['creator' => Auth::user()->login]);

        $category = Category::create($request->except(['_token', '_method']));

        // Clear the navigation cache to immediately show new categories
        cache()->forget('matter_categories_nav');

        if ($request->header('X-Inertia')) {
            return redirect()->route('category.index')
                ->with('success', 'Category created successfully');
        }

        return $category;
    }

    public function show(Category $category)
    {
        Gate::authorize('readonly');
        
        $tableComments = $category->getTableComments();
        $category->load(['displayWithInfo:code,category']);

        return response()->json([
            'category' => $category,
            'comments' => $tableComments,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        Gate::authorize('readwrite');

        $request->validate([
            'code' => 'required|unique:matter_category,code,'.$category->code.',code|max:5',
            'category' => 'required|max:45',
            'display_with' => 'required',
        ]);

        $request->merge(['updater' => Auth::user()->login]);
        $category->update($request->except(['_token', '_method']));

        // Clear the navigation cache to immediately reflect changes
        cache()->forget('matter_categories_nav');

        if ($request->header('X-Inertia')) {
            return redirect()->route('category.index')
                ->with('success', 'Category updated successfully');
        }

        return $category;
    }

    public function destroy(Category $category)
    {
        Gate::authorize('readwrite');

        $category->delete();

        // Clear the navigation cache after deletion
        cache()->forget('matter_categories_nav');

        if (request()->header('X-Inertia')) {
            return redirect()->route('category.index')
                ->with('success', 'Category deleted successfully');
        }

        return response()->json(['success' => true]);
    }
    
    public function autocomplete(Request $request)
    {
        $query = $request->get('query', '');
        
        $categories = Category::where('category', 'like', "%{$query}%")
            ->take(10)
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->code,
                    'name' => $cat->category,
                ];
            });

        return response()->json($categories);
    }
}
