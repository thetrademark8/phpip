<?php

namespace App\Providers;

use App\Contracts\Renewal\RenewalEmailServiceInterface;
use App\Contracts\Renewal\RenewalExportServiceInterface;
use App\Contracts\Renewal\RenewalFeeCalculatorInterface;
use App\Contracts\Renewal\RenewalInvoiceServiceInterface;
use App\Contracts\Renewal\RenewalLogServiceInterface;
use App\Contracts\Renewal\RenewalQueryServiceInterface;
use App\Contracts\Renewal\RenewalWorkflowServiceInterface;
use App\Repositories\ActorRepository;
use App\Repositories\Contracts\ActorRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\MatterRepositoryInterface;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\Repositories\EventRepository;
use App\Repositories\MatterRepository;
use App\Repositories\RenewalRepository;
use App\Services\Renewal\RenewalEmailService;
use App\Services\Renewal\RenewalExportService;
use App\Services\Renewal\RenewalFeeCalculatorService;
use App\Services\Renewal\RenewalInvoiceService;
use App\Services\Renewal\RenewalLogService;
use App\Services\Renewal\RenewalQueryService;
use App\Services\Renewal\RenewalWorkflowService;
use Illuminate\Support\ServiceProvider;

class RenewalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(RenewalRepositoryInterface::class, RenewalRepository::class);
        $this->app->bind(MatterRepositoryInterface::class, MatterRepository::class);
        $this->app->bind(ActorRepositoryInterface::class, ActorRepository::class);
        $this->app->bind(EventRepositoryInterface::class, EventRepository::class);
        
        // Register service bindings
        $this->app->bind(RenewalQueryServiceInterface::class, RenewalQueryService::class);
        $this->app->bind(RenewalFeeCalculatorInterface::class, RenewalFeeCalculatorService::class);
        $this->app->bind(RenewalWorkflowServiceInterface::class, RenewalWorkflowService::class);
        $this->app->bind(RenewalEmailServiceInterface::class, RenewalEmailService::class);
        $this->app->bind(RenewalInvoiceServiceInterface::class, RenewalInvoiceService::class);
        $this->app->bind(RenewalExportServiceInterface::class, RenewalExportService::class);
        $this->app->bind(RenewalLogServiceInterface::class, RenewalLogService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}