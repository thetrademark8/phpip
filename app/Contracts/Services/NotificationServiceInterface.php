<?php

namespace App\Contracts\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

interface NotificationServiceInterface
{
    /**
     * Send task reminder email
     */
    public function sendTaskReminder(Task $task, User $recipient): bool;

    /**
     * Send bulk task reminders for upcoming tasks
     *
     * @return int Number of notifications sent
     */
    public function sendUpcomingTaskReminders(int $daysAhead = 7): int;

    /**
     * Send renewal notification
     */
    public function sendRenewalNotification(Task $renewalTask, array $recipients): bool;

    /**
     * Send matter status change notification
     */
    public function sendStatusChangeNotification(
        string $matterId,
        string $oldStatus,
        string $newStatus,
        array $recipients
    ): bool;

    /**
     * Get notification recipients for a task
     */
    public function getTaskRecipients(Task $task): Collection;

    /**
     * Send custom notification
     */
    public function sendCustomNotification(
        string $template,
        array $data,
        array $recipients,
        array $attachments = []
    ): bool;

    /**
     * Queue notification for later sending
     *
     * @param  string  $sendAt  ISO datetime format
     * @return string Queue ID
     */
    public function queueNotification(string $type, array $data, string $sendAt): string;

    /**
     * Check if notification should be sent based on rules
     */
    public function shouldSendNotification(string $type, array $context): bool;
}
