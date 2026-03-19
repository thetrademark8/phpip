<?php

namespace App\Http\Controllers;

use App\Services\BrandedImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandedImportController extends Controller
{
    /**
     * Show the branded import page.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/BrandedImport');
    }

    /**
     * Import branded data from uploaded CSV files.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'actors_file' => 'required|file|mimes:csv,txt|max:10240',
            'matters_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $actorsPath = $request->file('actors_file')->getRealPath();
            $mattersPath = $request->file('matters_file')->getRealPath();

            $service = new BrandedImportService();
            $result = $service->import($actorsPath, $mattersPath);

            return response()->json([
                'success' => true,
                'stats' => $result['stats'] ?? [],
                'warnings' => $result['warnings'] ?? [],
                'message' => __('Import completed successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Import failed: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Preview branded data from uploaded CSV files without importing.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'actors_file' => 'required|file|mimes:csv,txt|max:10240',
            'matters_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $actorsPath = $request->file('actors_file')->getRealPath();
            $mattersPath = $request->file('matters_file')->getRealPath();

            $service = new BrandedImportService();
            $result = $service->preview($actorsPath, $mattersPath);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Preview failed: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }
}
