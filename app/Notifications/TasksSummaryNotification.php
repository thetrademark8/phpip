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
     * Get localized greeting
     */
    private function getGreeting(): string
    {
        $hour = now()->hour;

        return match ($this->language) {
            'fr' => $hour < 12 ? 'Bonjour,' : 'Bonsoir,',
            'de' => $hour < 12 ? 'Guten Morgen,' : 'Guten Abend,',
            default => $hour < 12 ? 'Good morning,' : 'Good evening,',
        };
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

    /**
     * Get localized intro line
     */
    private function getIntroLine(int $totalTasks, int $overdueCount, int $dueSoonCount): string
    {
        if ($totalTasks === 0) {
            return match ($this->language) {
                'fr' => '✅ Parfait ! Aucune tâche urgente dans le système en ce moment.',
                'de' => '✅ Perfekt! Keine dringenden Aufgaben im System im Moment.',
                default => '✅ Great! No urgent tasks in the system at this time.',
            };
        }

        $intro = match ($this->language) {
            'fr' => 'Voici le récapitulatif des tâches urgentes dans le système:',
            'de' => 'Hier ist die Zusammenfassung der dringenden Aufgaben im System:',
            default => 'Here is the summary of urgent tasks in the system:',
        };

        $parts = [];

        if ($overdueCount > 0) {
            $part = match ($this->language) {
                'fr' => "🚨 {$overdueCount} tâche" . ($overdueCount > 1 ? 's' : '') . ' en retard',
                'de' => "🚨 {$overdueCount} überfällige Aufgabe" . ($overdueCount > 1 ? 'n' : ''),
                default => "🚨 {$overdueCount} overdue task" . ($overdueCount > 1 ? 's' : ''),
            };
            $parts[] = $part;
        }

        if ($dueSoonCount > 0) {
            $part = match ($this->language) {
                'fr' => "⏰ {$dueSoonCount} tâche" . ($dueSoonCount > 1 ? 's' : '') . ' à échéance proche',
                'de' => "⏰ {$dueSoonCount} bald fällige Aufgabe" . ($dueSoonCount > 1 ? 'n' : ''),
                default => "⏰ {$dueSoonCount} task" . ($dueSoonCount > 1 ? 's' : '') . ' due soon',
            };
            $parts[] = $part;
        }

        return $intro . "\n\n" . implode("\n", $parts);
    }

    /**
     * Get localized statistics line
     */
    private function getStatisticsLine(int $overdueCount, int $dueSoonCount): string
    {
        if ($overdueCount === 0 && $dueSoonCount === 0) {
            return '';
        }

        $stats = [];

        if ($overdueCount > 0) {
            $urgencyText = match ($this->language) {
                'fr' => 'nécessitent une action immédiate',
                'de' => 'erfordern sofortige Maßnahmen',
                default => 'require immediate action',
            };
            $stats[] = "**{$overdueCount}** " . $urgencyText;
        }

        if ($dueSoonCount > 0) {
            $soonText = match ($this->language) {
                'fr' => 'nécessitent une attention dans les prochains jours',
                'de' => 'erfordern Aufmerksamkeit in den nächsten Tagen',
                default => 'require attention in the coming days',
            };
            $stats[] = "**{$dueSoonCount}** " . $soonText;
        }

        return implode(' • ', $stats);
    }

    /**
     * Get localized action text
     */
    private function getActionText(): string
    {
        return match ($this->language) {
            'fr' => 'Voir toutes les tâches urgentes',
            'de' => 'Alle dringenden Aufgaben anzeigen',
            default => 'View All Urgent Tasks',
        };
    }

    /**
     * Get localized closing line
     */
    private function getClosingLine(): string
    {
        return match ($this->language) {
            'fr' => 'Ce récapitulatif est envoyé quotidiennement pour surveiller les tâches nécessitant une attention. Les agents responsables reçoivent également des notifications individuelles pour leurs tâches.',
            'de' => 'Diese Zusammenfassung wird täglich gesendet, um Aufgaben zu überwachen, die Aufmerksamkeit erfordern. Verantwortliche Agenten erhalten auch individuelle Benachrichtigungen für ihre Aufgaben.',
            default => 'This summary is sent daily to monitor tasks requiring attention. Responsible agents also receive individual notifications for their tasks.',
        };
    }

    /**
     * Get localized salutation
     */
    private function getSalutation(): string
    {
        return match ($this->language) {
            'fr' => 'Cordialement,<br>L\'équipe phpIP',
            'de' => 'Mit freundlichen Grüßen,<br>Das phpIP-Team',
            default => 'Best regards,<br>The phpIP Team',
        };
    }
}
