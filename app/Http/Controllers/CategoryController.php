<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $oldCode = $category->code;
        $newCode = $request->input('code');
        $data = $request->except(['_token', '_method']);

        if ($newCode !== $oldCode) {
            // If display_with was self-referencing the old code, move the reference to the new code.
            if (($data['display_with'] ?? null) === $oldCode) {
                $data['display_with'] = $newCode;
            }

            // The raw query builder below bypasses Eloquent casts, so translatable columns
            // (e.g. `category`) must be JSON-encoded here; otherwise MySQL rejects the plain
            // string with "Invalid JSON text" for the JSON column.
            foreach ($category->getTranslatableAttributes() as $attribute) {
                if (array_key_exists($attribute, $data)) {
                    $category->setAttribute($attribute, $data[$attribute]);
                    $data[$attribute] = $category->getAttributes()[$attribute];
                }
            }

            // InnoDB cannot cascade self-referencing FKs (matter_category.display_with → matter_category.code),
            // which makes a plain PK update fail when any row references the old code via display_with.
            // We bypass FK checks and update every referencing column manually inside a transaction.
            DB::transaction(function () use ($oldCode, $newCode, $data) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                DB::table('matter_category')->where('display_with', $oldCode)->update(['display_with' => $newCode]);
                DB::table('matter')->where('category_code', $oldCode)->update(['category_code' => $newCode]);
                DB::table('task_rules')->where('for_category', $oldCode)->update(['for_category' => $newCode]);
                DB::table('classifier_type')->where('for_category', $oldCode)->update(['for_category' => $newCode]);
                DB::table('default_actor')->where('for_category', $oldCode)->update(['for_category' => $newCode]);

                DB::table('matter_category')->where('code', $oldCode)->update($data);

                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            });

            $category = Category::find($newCode);
        } else {
            $category->update($data);
        }

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
