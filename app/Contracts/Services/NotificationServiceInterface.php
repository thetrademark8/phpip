<?php

namespace App\Contracts\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

interface NotificationServiceInterface
{
    /**
     * Send task reminder email
     *
     * @param Task $task
     * @param User $recipient
     * @return bool
     */
    public function sendTaskReminder(Task $task, User $recipient): bool;
    
    /**
     * Send bulk task reminders for upcoming tasks
     *
     * @param int $daysAhead
     * @return int Number of notifications sent
     */
    public function sendUpcomingTaskReminders(int $daysAhead = 7): int;
    
    /**
     * Send renewal notification
     *
     * @param Task $renewalTask
     * @param array $recipients
     * @return bool
     */
    public function sendRenewalNotification(Task $renewalTask, array $recipients): bool;
    
    /**
     * Send matter status change notification
     *
     * @param string $matterId
     * @param string $oldStatus
     * @param string $newStatus
     * @param array $recipients
     * @return bool
     */
    public function sendStatusChangeNotification(
        string $matterId,
        string $oldStatus,
        string $newStatus,
        array $recipients
    ): bool;
    
    /**
     * Get notification recipients for a task
     *
     * @param Task $task
     * @return Collection
     */
    public function getTaskRecipients(Task $task): Collection;
    
    /**
     * Send custom notification
     *
     * @param string $template
     * @param array $data
     * @param array $recipients
     * @param array $attachments
     * @return bool
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
     * @param string $type
     * @param array $data
     * @param string $sendAt ISO datetime format
     * @return string Queue ID
     */
    public function queueNotification(string $type, array $data, string $sendAt): string;
    
    /**
     * Check if notification should be sent based on rules
     *
     * @param string $type
     * @param array $context
     * @return bool
     */
    public function shouldSendNotification(string $type, array $context): bool;
}