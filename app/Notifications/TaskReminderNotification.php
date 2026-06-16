<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Task $task;

    protected string $language;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, string $language = 'en')
    {
        $this->task = $task;
        $this->language = $language;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $daysUntilDue = $this->getDaysUntilDue();
        $urgencyLevel = $this->getUrgencyLevel();

        $subject = $this->getSubject();

        return (new MailMessage)
            ->subject($subject)
            ->markdown('notifications.task-reminder', [
                'task' => $this->task,
                'language' => $this->language,
                'urgencyLevel' => $urgencyLevel,
                'daysUntilDue' => $daysUntilDue,
                'phpip_url' => config('app.url'),
            ]);
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'task_reminder',
            'task_id' => $this->task->id,
            'matter_id' => $this->task->matter->id,
            'matter_uid' => $this->task->matter->uid,
            'task_code' => $this->task->code,
            'task_name' => $this->task->info->name ?? $this->task->code,
            'due_date' => $this->task->due_date,
            'days_until_due' => $this->getDaysUntilDue(),
            'urgency_level' => $this->getUrgencyLevel(),
            'language' => $this->language,
            'sent_at' => now(),
        ];
    }

    /**
     * Calculate days until due (negative if overdue)
     */
    private function getDaysUntilDue(): int
    {
        return (int) round(now()->diffInDays($this->task->due_date, false));
    }

    /**
     * Get urgency level based on due date
     */
    private function getUrgencyLevel(): string
    {
        $days = $this->getDaysUntilDue();

        if ($days < 0) {
            return 'overdue';
        }
        if ($days <= 1) {
            return 'critical';
        }
        if ($days <= 3) {
            return 'urgent';
        }
        if ($days <= 7) {
            return 'attention';
        }

        return 'normal';
    }

    /**
     * Get localized email subject
     */
    private function getSubject(): string
    {
        $taskName = $this->task->info->name ?? $this->task->code;
        $matterRef = $this->task->matter->uid;

        return match ($this->language) {
            'fr' => "[phpIP] Rappel de tâche: {$taskName} - {$matterRef}",
            'de' => "[phpIP] Aufgabenerinnerung: {$taskName} - {$matterRef}",
            default => "[phpIP] Task Reminder: {$taskName} - {$matterRef}",
        };
    }
}
