<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Services\DateServiceInterface;
use App\Contracts\Services\MatterServiceInterface;
use App\Contracts\Services\RenewalServiceInterface;
use App\Contracts\Services\NotificationServiceInterface;
use App\Services\DateService;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register service bindings
        $this->app->bind(DateServiceInterface::class, DateService::class);
        
        // Register placeholder implementations for now
        // These will be implemented in future phases
        $this->app->bind(MatterServiceInterface::class, function ($app) {
            // Placeholder until MatterService is implemented
            return new class implements MatterServiceInterface {
                public function create(array $data): \App\Models\Matter
                {
                    return \App\Models\Matter::create($data);
                }
                
                public function update(\App\Models\Matter $matter, array $data): \App\Models\Matter
                {
                    $matter->update($data);
                    return $matter->fresh();
                }
                
                public function delete(\App\Models\Matter $matter): bool
                {
                    return $matter->delete();
                }
                
                public function clone(\App\Models\Matter $matter, array $overrides = []): \App\Models\Matter
                {
                    $clone = $matter->replicate();
                    $clone->fill($overrides);
                    $clone->save();
                    return $clone;
                }
                
                public function createFamily(array $patentData): \Illuminate\Support\Collection
                {
                    return collect();
                }
                
                public function copyToCountries(\App\Models\Matter $matter, array $countries): \Illuminate\Support\Collection
                {
                    return collect();
                }
                
                public function getExpiringMatters(int $days = 30): \Illuminate\Support\Collection
                {
                    return \App\Models\Matter::where('expire_date', '<=', now()->addDays($days))
                        ->where('expire_date', '>=', now())
                        ->get();
                }
                
                public function calculateRenewalDeadline(\App\Models\Matter $matter): ?string
                {
                    return $matter->expire_date?->format('Y-m-d');
                }
            };
        });
        
        $this->app->bind(RenewalServiceInterface::class, function ($app) {
            // Placeholder until RenewalService is implemented
            return new class implements RenewalServiceInterface {
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
            return new class implements NotificationServiceInterface {
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