<?php

namespace App\Notifications;

use App\Models\Actor;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
     * Get localized greeting
     */
    private function getGreeting(): string
    {
        $hour = now()->hour;
        $name = $this->agent->name ?? 'Agent';

        return match($this->language) {
            'fr' => $hour < 12 ? "Bonjour {$name}," : "Bonsoir {$name},",
            'de' => $hour < 12 ? "Guten Morgen {$name}," : "Guten Abend {$name},",
            default => $hour < 12 ? "Good morning {$name}," : "Good evening {$name},",
        };
    }

    /**
     * Get localized email subject
     */
    private function getSubject(int $totalTasks): string
    {
        return match($this->language) {
            'fr' => "[phpIP] Tâches urgentes ({$totalTasks} tâche" . ($totalTasks > 1 ? 's' : '') . " nécessitent votre attention)",
            'de' => "[phpIP] Dringende Aufgaben ({$totalTasks} Aufgabe" . ($totalTasks > 1 ? 'n' : '') . " erfordern Ihre Aufmerksamkeit)",
            default => "[phpIP] Urgent Tasks ({$totalTasks} task" . ($totalTasks > 1 ? 's' : '') . " require your attention)",
        };
    }

    /**
     * Get localized intro line
     */
    private function getIntroLine(int $totalTasks): string
    {
        if ($totalTasks === 0) {
            return match($this->language) {
                'fr' => '✅ Parfait ! Vous n\'avez aucune tâche urgente en ce moment.',
                'de' => '✅ Perfekt! Sie haben derzeit keine dringenden Aufgaben.',
                default => '✅ Great! You have no urgent tasks at this time.',
            };
        }

        $overdueCount = $this->overdueTasks->count();
        $dueSoonCount = $this->dueSoonTasks->count();

        $parts = [];
        
        if ($overdueCount > 0) {
            $part = match($this->language) {
                'fr' => "{$overdueCount} tâche" . ($overdueCount > 1 ? 's' : '') . " en retard",
                'de' => "{$overdueCount} überfällige Aufgabe" . ($overdueCount > 1 ? 'n' : ''),
                default => "{$overdueCount} overdue task" . ($overdueCount > 1 ? 's' : ''),
            };
            $parts[] = "🚨 " . $part;
        }

        if ($dueSoonCount > 0) {
            $part = match($this->language) {
                'fr' => "{$dueSoonCount} tâche" . ($dueSoonCount > 1 ? 's' : '') . " à échéance proche",
                'de' => "{$dueSoonCount} bald fällige Aufgabe" . ($dueSoonCount > 1 ? 'n' : ''),
                default => "{$dueSoonCount} task" . ($dueSoonCount > 1 ? 's' : '') . " due soon",
            };
            $parts[] = "⏰ " . $part;
        }

        $intro = match($this->language) {
            'fr' => 'Vous avez des tâches qui nécessitent votre attention immédiate:',
            'de' => 'Sie haben Aufgaben, die Ihre sofortige Aufmerksamkeit erfordern:',
            default => 'You have tasks that require your immediate attention:',
        };

        return $intro . "\n\n" . implode("\n", $parts);
    }

    /**
     * Get localized action text
     */
    private function getActionText(): string
    {
        return match($this->language) {
            'fr' => 'Voir mes tâches urgentes',
            'de' => 'Meine dringenden Aufgaben anzeigen',
            default => 'View My Urgent Tasks',
        };
    }

    /**
     * Get localized closing line
     */
    private function getClosingLine(): string
    {
        return match($this->language) {
            'fr' => 'Les détails de chaque tâche sont disponibles en cliquant sur les références de dossier ci-dessus. N\'hésitez pas à mettre à jour le statut des tâches une fois terminées.',
            'de' => 'Details zu jeder Aufgabe sind verfügbar, indem Sie auf die oben genannten Aktenreferenzen klicken. Bitte aktualisieren Sie den Status der Aufgaben, sobald sie abgeschlossen sind.',
            default => 'Details for each task are available by clicking on the matter references above. Please update task status once completed.',
        };
    }

    /**
     * Get localized salutation
     */
    private function getSalutation(): string
    {
        return match($this->language) {
            'fr' => 'Cordialement,<br>L\'équipe phpIP',
            'de' => 'Mit freundlichen Grüßen,<br>Das phpIP-Team',
            default => 'Best regards,<br>The phpIP Team',
        };
    }
}