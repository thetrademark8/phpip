<?php

namespace App\Providers;

use App\Contracts\Services\DatePickerServiceInterface;
use App\Contracts\Services\DateServiceInterface;
use App\Contracts\Services\MatterServiceInterface;
use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\RenewalServiceInterface;
use App\Repositories\MatterRepository;
use App\Services\DatePickerService;
use App\Services\DateService;
use App\Services\MatterService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->singleton(MatterRepository::class);

        // Register service bindings
        $this->app->bind(DateServiceInterface::class, DateService::class);
        $this->app->bind(DatePickerServiceInterface::class, DatePickerService::class);
        $this->app->bind(MatterServiceInterface::class, MatterService::class);

        $this->app->bind(RenewalServiceInterface::class, function ($app) {
            // Placeholder until RenewalService is implemented
            return new class implements RenewalServiceInterface
            {
                public function calculateFees(\App\Models\Matter $matter): array
                {
                    return [];
                }

                public function createRenewalTasks(\App\Models\Matter $matter, string $startDate): \Illuminate\Support\Collection
                {
                    return collect();
                }

                public function getUpcomingRenewals(int $months = 12): \Illuminate\Support\Collection
                {
                    return collect();
                }

                public function processRenewal(\App\Models\Task $task, array $data): bool
                {
                    return true;
                }

                public function getRenewalCycle(string $categoryCode): int
                {
                    // Trademark renewals are typically every 10 years
                    return str_starts_with($categoryCode, 'TM') ? 10 : 1;
                }

                public function requiresRenewal(\App\Models\Matter $matter): bool
                {
                    return true;
                }

                public function syncExternalData(\App\Models\Matter $matter): array
                {
                    return [];
                }

                public function cancelRenewalTasks(\App\Models\Matter $matter, string $reason): int
                {
                    return 0;
                }
            };
        });

        $this->app->bind(NotificationServiceInterface::class, function ($app) {
            // Placeholder until NotificationService is implemented
            return new class implements NotificationServiceInterface
            {
                public function sendTaskReminder(\App\Models\Task $task, \App\Models\User $recipient): bool
                {
                    return true;
                }

                public function sendUpcomingTaskReminders(int $daysAhead = 7): int
                {
                    return 0;
                }

                public function sendRenewalNotification(\App\Models\Task $renewalTask, array $recipients): bool
                {
                    return true;
                }

                public function sendStatusChangeNotification(
                    string $matterId,
                    string $oldStatus,
                    string $newStatus,
                    array $recipients
                ): bool {
                    return true;
                }

                public function getTaskRecipients(\App\Models\Task $task): \Illuminate\Support\Collection
                {
                    return collect();
                }

                public function sendCustomNotification(
                    string $template,
                    array $data,
                    array $recipients,
                    array $attachments = []
                ): bool {
                    return true;
                }

                public function queueNotification(string $type, array $data, string $sendAt): string
                {
                    return uniqid('notification_');
                }

                public function shouldSendNotification(string $type, array $context): bool
                {
                    return true;
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
