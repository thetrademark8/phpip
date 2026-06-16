<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class TasksSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Collection $tasks;

    protected string $language;

    /**
     * Create a new notification instance.
     */
    public function __construct(Collection $tasks, string $language = 'en')
    {
        $this->tasks = $tasks;
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
        $totalTasks = $this->tasks->count();
        $overdueTasks = $this->tasks->filter(fn ($task) => $task->due_date < now());
        $dueSoonTasks = $this->tasks->filter(fn ($task) => $task->due_date >= now() && $task->due_date <= now()->addDays(7));

        $subject = $this->getSubject($totalTasks);

        // Use markdown method instead of view to properly work with mail::layout components
        return (new MailMessage)
            ->subject($subject)
            ->markdown('notifications.tasks-summary', [
                'tasks' => $this->tasks,
                'overdueTasks' => $overdueTasks,
                'dueSoonTasks' => $dueSoonTasks,
                'language' => $this->language,
                'totalTasks' => $totalTasks,
                'phpip_url' => config('app.url'),
            ]);
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'tasks_summary',
            'total_tasks' => $this->tasks->count(),
            'overdue_tasks' => $this->tasks->filter(fn ($task) => $task->due_date < now())->count(),
            'due_soon_tasks' => $this->tasks->filter(fn ($task) => $task->due_date >= now() && $task->due_date <= now()->addDays(7))->count(),
            'language' => $this->language,
            'sent_at' => now(),
            'task_ids' => $this->tasks->pluck('id')->toArray(),
        ];
    }

    /**
     * Get localized email subject
     */
    private function getSubject(int $totalTasks): string
    {
        return match ($this->language) {
            'fr' => "[phpIP] Récapitulatif des tâches urgentes ({$totalTasks} tâche" . ($totalTasks > 1 ? 's' : '') . ')',
            'de' => "[phpIP] Zusammenfassung dringender Aufgaben ({$totalTasks} Aufgabe" . ($totalTasks > 1 ? 'n' : '') . ')',
            default => "[phpIP] Urgent Tasks Summary ({$totalTasks} task" . ($totalTasks > 1 ? 's' : '') . ')',
        };
    }
}
