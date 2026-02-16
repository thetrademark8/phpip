<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ClassifierType;
use App\Models\Country;
use App\Models\EventName;
use App\Models\MatterType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OptionsController extends Controller
{
    /**
     * Get all categories for Select dropdown (small list, no filtering needed).
     */
    public function categories(): JsonResponse
    {
        $categories = Category::orderBy('code')
            ->get()
            ->map(fn ($cat) => [
                'value' => $cat->code,
                'label' => $cat->category,
                'prefix' => $cat->ref_prefix,
            ]);

        return response()->json($categories);
    }

    /**
     * Get all matter types for Select dropdown (small list).
     */
    public function types(): JsonResponse
    {
        $types = MatterType::orderBy('code')
            ->get()
            ->map(fn ($type) => [
                'value' => $type->code,
                'label' => $type->type,
            ]);

        return response()->json($types);
    }

    /**
     * Get all countries for Combobox (large list, supports search).
     */
    public function countries(Request $request): JsonResponse
    {
        $query = Country::orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereJsonLike('name', $search)
                    ->orWhereLike('iso', "{$search}%");
            });
        }

        $countries = $query->get()->map(fn ($country) => [
            'value' => $country->iso,
            'label' => $country->name,
        ]);

        return response()->json($countries);
    }

    /**
     * Get all users for Combobox.
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', "{$search}%")
                    ->orWhereLike('login', "{$search}%");
            });
        }

        $users = $query->get()->map(fn ($user) => [
            'value' => $user->login,
            'label' => $user->name,
        ]);

        return response()->json($users);
    }

    /**
     * Get event names for Combobox (filtered by is_task).
     */
    public function eventNames(Request $request, int $isTask = 0): JsonResponse
    {
        $query = EventName::where('is_task', $isTask)->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereJsonLike('name', $search)
                    ->orWhereLike('code', "{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('category')
                    ->orWhere('category', $request->category);
            });
        }

        $events = $query->get()->map(fn ($event) => [
            'value' => $event->code,
            'label' => $event->name,
        ]);

        return response()->json($events);
    }

    /**
     * Get classifier types for Select dropdown.
     */
    public function classifierTypes(Request $request, ?int $mainDisplay = null): JsonResponse
    {
        $query = ClassifierType::orderBy('type');

        if ($mainDisplay !== null) {
            $query->where('main_display', $mainDisplay);
        }

        $types = $query->get()
            ->map(fn ($type) => [
                'value' => $type->code,
                'label' => $type->type,
            ]);

        return response()->json($types);
    }

    /**
     * Get status event names for Combobox (status_event = 1).
     */
    public function statuses(Request $request): JsonResponse
    {
        $query = EventName::where('status_event', 1)->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereJsonLike('name', $search)
                    ->orWhereLike('code', "{$search}%");
            });
        }

        $statuses = $query->get()->map(fn ($event) => [
            'value' => $event->code,
            'label' => $event->name,
        ]);

        return response()->json($statuses);
    }
}
