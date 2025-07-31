<?php

namespace App\Providers;

use App\Helpers\PermissionHelper;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        
        // Use PermissionHelper to define gates
        Gate::define('client', fn ($user) => PermissionHelper::isClient($user));
        Gate::define('except_client', fn ($user) => PermissionHelper::isNotClient($user));
        Gate::define('admin', fn ($user) => PermissionHelper::isAdmin($user));
        Gate::define('readwrite', fn ($user) => PermissionHelper::canReadWrite($user));
        Gate::define('readonly', fn ($user) => PermissionHelper::canReadOnly($user));

        // Add query macro for case-insensitive JSON column queries
        \Illuminate\Database\Query\Builder::macro('whereJsonLike', function ($column, $value, $locale = null) {
            if (! $locale) {
                $locale = app()->getLocale();
                // Normalize to base locale (e.g., 'en' from 'en_US')
                $locale = substr($locale, 0, 2);
            }

            return $this->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT($column, '$.$locale')) COLLATE utf8mb4_0900_ai_ci LIKE ?",
                ["$value%"]
            );
        });
    }
}
