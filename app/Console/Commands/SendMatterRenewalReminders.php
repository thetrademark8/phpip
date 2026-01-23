<?php

namespace App\Console\Commands;

use App\Models\Actor;
use App\Models\Matter;
use App\Notifications\MatterRenewalReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendMatterRenewalReminders extends Command
{
    protected $signature = 'matters:send-renewal-reminders';

    protected $description = 'Send renewal reminders for matters expiring at 6 months, 3 months, and 1 month';

    /**
     * Reminder intervals configuration
     * Key: months before expiry
     * Value: reminder type identifier
     */
    private const REMINDER_INTERVALS = [
        6 => 'first',    // 1st reminder at 6 months
        3 => 'second',   // 2nd reminder at 3 months
        1 => 'last',     // Last reminder at 1 month
    ];

    public function handle(): int
    {
        $this->info('Starting matter renewal reminders process...');

        $sentCount = 0;
        $errorCount = 0;

        foreach (self::REMINDER_INTERVALS as $months => $reminderType) {
            $matters = $this->getMattersForReminder($months);

            if ($matters->isEmpty()) {
                $this->info("No matters found for {$months}-month reminder ({$reminderType})");
                continue;
            }

            $this->info("Found {$matters->count()} matters for {$months}-month reminder ({$reminderType})");

            $mattersByResponsible = $this->groupMattersByResponsible($matters);

            foreach ($mattersByResponsible as $responsibleLogin => $responsibleMatters) {
                $result = $this->sendReminderToResponsible($responsibleLogin, $responsibleMatters, $reminderType, $months);

                if ($result) {
                    $sentCount++;
                } else {
                    $errorCount++;
                }
            }
        }

        $this->info("Completed. Sent: {$sentCount} notifications, Errors: {$errorCount}");

        Log::info('Matter renewal reminders completed', [
            'sent_count' => $sentCount,
            'error_count' => $errorCount,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Get matters that have expire_date exactly X months from now (within a day range)
     */
    private function getMattersForReminder(int $months): Collection
    {
        $targetDate = Carbon::now()->addMonths($months);

        // Use a date range to catch matters expiring on this specific day
        // We check for expire_date that falls within today's date + X months
        $startOfDay = $targetDate->copy()->startOfDay();
        $endOfDay = $targetDate->copy()->endOfDay();

        return Matter::query()
            ->where('dead', 0)
            ->whereNotNull('expire_date')
            ->whereBetween('expire_date', [$startOfDay, $endOfDay])
            ->with(['client', 'responsibleActor', 'titles', 'countryInfo'])
            ->get();
    }

    /**
     * Group matters by their responsible person
     */
    private function groupMattersByResponsible(Collection $matters): array
    {
        $grouped = [];

        foreach ($matters as $matter) {
            $responsible = $matter->responsible;

            if (!$responsible) {
                Log::warning('Matter has no responsible assigned', ['matter_id' => $matter->id, 'uid' => $matter->uid]);
                continue;
            }

            if (!isset($grouped[$responsible])) {
                $grouped[$responsible] = [];
            }

            $grouped[$responsible][] = $matter;
        }

        return $grouped;
    }

    /**
     * Send reminder notification to a responsible person
     */
    private function sendReminderToResponsible(string $responsibleLogin, array $matters, string $reminderType, int $monthsRemaining): bool
    {
        $actor = Actor::where('login', $responsibleLogin)->first();

        if (!$actor) {
            Log::warning('Responsible actor not found', ['login' => $responsibleLogin]);
            return false;
        }

        if (!$actor->email) {
            Log::warning('Responsible actor has no email', ['login' => $responsibleLogin, 'actor_id' => $actor->id]);
            return false;
        }

        try {
            $language = $actor->language ?? 'en';

            $actor->notify(new MatterRenewalReminderNotification(
                collect($matters),
                $reminderType,
                $monthsRemaining,
                $language
            ));

            Log::info('Renewal reminder sent', [
                'actor_login' => $responsibleLogin,
                'actor_email' => $actor->email,
                'reminder_type' => $reminderType,
                'months_remaining' => $monthsRemaining,
                'matter_count' => count($matters),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send renewal reminder', [
                'actor_login' => $responsibleLogin,
                'reminder_type' => $reminderType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
