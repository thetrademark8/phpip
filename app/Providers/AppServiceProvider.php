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
        // Renewal service bindings
        $this->app->bind(
            \App\Contracts\Renewal\RenewalInvoiceServiceInterface::class,
            \App\Services\Renewal\RenewalInvoiceService::class
        );

        $this->app->bind(
            \App\Contracts\Renewal\RenewalExportServiceInterface::class,
            \App\Services\Renewal\RenewalExportService::class
        );

        $this->app->bind(
            \App\Services\Renewal\Contracts\RenewalOrderServiceInterface::class,
            \App\Services\Renewal\RenewalOrderService::class
        );

        $this->app->bind(
            \App\Services\Renewal\Contracts\RenewalPaymentServiceInterface::class,
            \App\Services\Renewal\RenewalPaymentService::class
        );

        $this->app->bind(
            \App\Contracts\Renewal\RenewalLogServiceInterface::class,
            \App\Services\Renewal\RenewalLogService::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        
        // Register mail component namespace for email templates
        view()->addNamespace('mail', resource_path('views/vendor/mail'));
        
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

            // Use LOWER() for case-insensitive search and utf8mb4_unicode_ci for broader compatibility
            // Search with contains pattern (%value%) instead of prefix (value%)
            return $this->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT($column, '$.\"$locale\"'))) LIKE LOWER(?)",
                ["%$value%"]
            );
        });
    }
}
