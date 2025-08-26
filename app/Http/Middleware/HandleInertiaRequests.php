<?php

namespace App\Http\Middleware;

use App\Helpers\PermissionHelper;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Get user's locale preference or fall back to app default
        $locale = config('app.locale');
        if ($request->user()) {
            // Assuming the User model has the actor relationship
            $userActor = \App\Models\Actor::find($request->user()->id);
            if ($userActor && $userActor->language) {
                $locale = $userActor->language;
            }
        }
        
        // Set the application locale
        app()->setLocale($locale);
        
        // Load translations for the current locale
        $translations = [];
        $translationFile = base_path("lang/{$locale}.json");
        if (file_exists($translationFile)) {
            $translations = json_decode(file_get_contents($translationFile), true) ?? [];
        }
        
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? array_merge([
                    'id' => $request->user()->id,
                    'login' => $request->user()->login,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ], PermissionHelper::getUserPermissions($request->user())) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'app' => [
                'name' => config('app.name'),
                'company_name' => config('app.company_name'),
                'company_logo' => config('app.company_logo'),
            ],
            'matter_categories' => cache()->remember('matter_categories_nav', now()->addHours(1), function () {
                return Category::whereColumn('code', 'display_with')
                    ->select('code', 'category')
                    ->orderBy('code')
                    ->get();
            }),
            'csrf_token' => csrf_token(),
            'locale' => $locale,
            'translations' => $translations,
            'available_locales' => ['en', 'fr', 'de'],
        ];
    }
}
