<?php

namespace App\Notifications;

use App\Models\Actor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class UrgentTasksNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Actor $agent;

    protected Collection $overdueTasks;

    protected Collection $dueSoonTasks;

    protected string $language;

    /**
     * Create a new notification instance.
     */
    public function __construct(Actor $agent, array $overdueTasks, array $dueSoonTasks, string $language = 'en')
    {
        $this->agent = $agent;
        $this->overdueTasks = collect($overdueTasks);
        $this->dueSoonTasks = collect($dueSoonTasks);
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
        $totalTasks = $this->overdueTasks->count() + $this->dueSoonTasks->count();

        $subject = $this->getSubject($totalTasks);

        // Use markdown method instead of view to properly work with mail::layout components
        return (new MailMessage)
            ->subject($subject)
            ->markdown('notifications.urgent-tasks', [
                'agent' => $this->agent,
                'overdueTasks' => $this->overdueTasks,
                'dueSoonTasks' => $this->dueSoonTasks,
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
            'type' => 'urgent_tasks',
            'agent_id' => $this->agent->id,
            'agent_name' => $this->agent->name,
            'overdue_count' => $this->overdueTasks->count(),
            'due_soon_count' => $this->dueSoonTasks->count(),
            'total_tasks' => $this->overdueTasks->count() + $this->dueSoonTasks->count(),
            'language' => $this->language,
            'sent_at' => now(),
            'overdue_task_ids' => $this->overdueTasks->pluck('id')->toArray(),
            'due_soon_task_ids' => $this->dueSoonTasks->pluck('id')->toArray(),
        ];
    }

    /**
     * Get localized email subject
     */
    private function getSubject(int $totalTasks): string
    {
        return match ($this->language) {
            'fr' => "[phpIP] Tâches urgentes ({$totalTasks} tâche" . ($totalTasks > 1 ? 's' : '') . ' nécessitent votre attention)',
            'de' => "[phpIP] Dringende Aufgaben ({$totalTasks} Aufgabe" . ($totalTasks > 1 ? 'n' : '') . ' erfordern Ihre Aufmerksamkeit)',
            default => "[phpIP] Urgent Tasks ({$totalTasks} task" . ($totalTasks > 1 ? 's' : '') . ' require your attention)',
        };
    }
}
