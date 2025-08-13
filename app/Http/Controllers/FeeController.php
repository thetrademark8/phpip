<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Fee::query();

        // Apply filters
        if ($request->filled('Origin')) {
            $query->where('for_origin', 'LIKE', $request->Origin . '%');
        }

        if ($request->filled('Category')) {
            $query->where('for_category', 'LIKE', $request->Category . '%');
        }

        if ($request->filled('Qt')) {
            $query->where('qt', 'LIKE', $request->Qt . '%');
        }

        if ($request->filled('Country')) {
            $query->where('for_country', 'LIKE', $request->Country . '%');
        }

        // Handle sorting
        $sortField = $request->input('sort', 'for_category');
        $sortDirection = $request->input('direction', 'asc');
        
        // Map frontend column names to database columns
        $sortableColumns = [
            'for_category' => 'for_category',
            'for_country' => 'for_country',
            'for_origin' => 'for_origin',
            'qt' => 'qt',
            'cost' => 'cost',
            'fee' => 'fee',
            'use_after' => 'use_after',
            'use_before' => 'use_before',
        ];
        
        // Apply sorting
        if (isset($sortableColumns[$sortField])) {
            $query->orderBy($sortableColumns[$sortField], $sortDirection);
        } else {
            // Default sorting
            $query->orderBy('for_category')->orderBy('for_country')->orderBy('qt');
        }

        // Load relationships for better display
        $query->with(['country:iso,name', 'category:code,category', 'origin:iso,name']);

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $perPage = config('renewal.general.paginate', 25);
        if ($perPage == 0) {
            $perPage = 25;
        }

        $fees = $query->paginate($perPage);
        $fees->appends($request->input())->links();

        return Inertia::render('Fee/Index', [
            'fees' => $fees,
            'filters' => $request->only(['Origin', 'Category', 'Qt', 'Country']),
            'sort' => $sortField,
            'direction' => $sortDirection,
        ]);
    }

    public function create()
    {
        $table = new Fee;
        $tableComments = $table->getTableComments();

        return Inertia::render('Fee/Create', [
            'comments' => $tableComments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'for_category' => 'required',
            'from_qt' => 'required|integer',
            'to_qt' => 'nullable|integer',
            'use_after' => 'nullable|date',
            'use_before' => 'nullable|date',
        ]);
        
        $request->merge(['creator' => Auth::user()->login]);
        
        if (is_null($request->input('to_qt'))) {
            $request->merge(['qt' => $request->input('from_qt')]);
            Fee::create($request->except(['from_qt', 'to_qt', '_token', '_method']));
        } else {
            for ($i = intval($request->input('from_qt')); $i <= intval($request->input('to_qt')); $i++) {
                $request->merge(['qt' => $i]);
                Fee::create($request->except(['from_qt', 'to_qt', '_token', '_method']));
            }
        }

        if ($request->header('X-Inertia')) {
            return redirect()->route('fee.index')
                ->with('success', 'Fee created successfully');
        }

        return response()->json(['success' => 'Fee created']);
    }

    public function show(Fee $fee)
    {
        $feeInfo = $fee->load(['country:iso,name', 'category:code,category', 'origin:iso,name']);
        $feeComments = $fee->getTableComments();

        if (request()->wantsJson()) {
            return response()->json([
                'fee' => $feeInfo,
                'comments' => $feeComments,
            ]);
        }

        return Inertia::render('Fee/Show', [
            'fee' => $feeInfo,
            'comments' => $feeComments,
        ]);
    }

    public function update(Request $request, Fee $fee)
    {
        $this->validate($request, [
            'use_after' => 'nullable|date',
            'use_before' => 'nullable|date',
            'cost' => 'nullable|numeric',
            'fee' => 'nullable|numeric',
            'cost_reduced' => 'nullable|numeric',
            'fee_reduced' => 'nullable|numeric',
            'cost_sup' => 'nullable|numeric',
            'fee_sup' => 'nullable|numeric',
            'cost_sup_reduced' => 'nullable|numeric',
            'fee_sup_reduced' => 'nullable|numeric',
        ]);
        
        $request->merge(['updater' => Auth::user()->login]);
        $fee->update($request->except(['_token', '_method']));

        if ($request->header('X-Inertia')) {
            return redirect()->route('fee.index')
                ->with('success', 'Fee updated successfully');
        }

        return response()->json(['success' => 'Fee updated']);
    }

    public function destroy(Fee $fee)
    {
        $fee->delete();

        if (request()->header('X-Inertia')) {
            return redirect()->route('fee.index')
                ->with('success', 'Fee deleted successfully');
        }

        return response()->json(['success' => 'Fee deleted']);
    }
}
