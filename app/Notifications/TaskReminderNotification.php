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
     * Get localized greeting
     */
    private function getGreeting($notifiable): string
    {
        $name = $notifiable->name ?? 'Agent';
        $hour = now()->hour;

        return match ($this->language) {
            'fr' => $hour < 12 ? "Bonjour {$name}," : "Bonsoir {$name},",
            'de' => $hour < 12 ? "Guten Morgen {$name}," : "Guten Abend {$name},",
            default => $hour < 12 ? "Good morning {$name}," : "Good evening {$name},",
        };
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

    /**
     * Get localized intro line
     */
    private function getIntroLine(int $daysUntilDue, string $urgencyLevel): string
    {
        $icon = match ($urgencyLevel) {
            'overdue' => '🚨',
            'critical' => '🔴',
            'urgent' => '🟠',
            'attention' => '🟡',
            default => 'ℹ️',
        };

        if ($daysUntilDue < 0) {
            $daysOverdue = abs($daysUntilDue);

            return match ($this->language) {
                'fr' => "{$icon} Cette tâche est en retard de {$daysOverdue} jour" . ($daysOverdue > 1 ? 's' : '') . ' et nécessite votre attention immédiate.',
                'de' => "{$icon} Diese Aufgabe ist {$daysOverdue} Tag" . ($daysOverdue > 1 ? 'e' : '') . ' überfällig und erfordert Ihre sofortige Aufmerksamkeit.',
                default => "{$icon} This task is {$daysOverdue} day" . ($daysOverdue > 1 ? 's' : '') . ' overdue and requires your immediate attention.',
            };
        } elseif ($daysUntilDue === 0) {
            return match ($this->language) {
                'fr' => "{$icon} Cette tâche est due aujourd'hui.",
                'de' => "{$icon} Diese Aufgabe ist heute fällig.",
                default => "{$icon} This task is due today.",
            };
        } elseif ($daysUntilDue === 1) {
            return match ($this->language) {
                'fr' => "{$icon} Cette tâche est due demain.",
                'de' => "{$icon} Diese Aufgabe ist morgen fällig.",
                default => "{$icon} This task is due tomorrow.",
            };
        } else {
            return match ($this->language) {
                'fr' => "{$icon} Cette tâche est due dans {$daysUntilDue} jours.",
                'de' => "{$icon} Diese Aufgabe ist in {$daysUntilDue} Tagen fällig.",
                default => "{$icon} This task is due in {$daysUntilDue} days.",
            };
        }
    }

    /**
     * Get formatted task details
     */
    private function getTaskDetails(): string
    {
        $details = [];

        $labels = match ($this->language) {
            'fr' => [
                'matter' => 'Dossier',
                'task' => 'Tâche',
                'due_date' => 'Date d\'échéance',
                'client' => 'Client',
            ],
            'de' => [
                'matter' => 'Akte',
                'task' => 'Aufgabe',
                'due_date' => 'Fälligkeitsdatum',
                'client' => 'Kunde',
            ],
            default => [
                'matter' => 'Matter',
                'task' => 'Task',
                'due_date' => 'Due Date',
                'client' => 'Client',
            ],
        };

        $details[] = "**{$labels['matter']}:** {$this->task->matter->uid}";
        if ($this->task->matter->alt_ref) {
            $details[] = "**Alt. Ref:** {$this->task->matter->alt_ref}";
        }

        $details[] = "**{$labels['task']}:** " . ($this->task->info->name ?? $this->task->code);
        if ($this->task->detail) {
            $details[] = "**Details:** {$this->task->detail}";
        }

        $details[] = "**{$labels['due_date']}:** " . \Carbon\Carbon::parse($this->task->due_date)->format('d/m/Y');

        if ($this->task->matter->client) {
            $details[] = "**{$labels['client']}:** {$this->task->matter->client->name}";
        }

        return implode("\n", $details);
    }

    /**
     * Get localized action text
     */
    private function getActionText(): string
    {
        return match ($this->language) {
            'fr' => 'Voir le dossier',
            'de' => 'Akte anzeigen',
            default => 'View Matter',
        };
    }

    /**
     * Get localized closing line
     */
    private function getClosingLine(): string
    {
        return match ($this->language) {
            'fr' => 'Veuillez traiter cette tâche dès que possible et mettre à jour son statut une fois terminée.',
            'de' => 'Bitte bearbeiten Sie diese Aufgabe so bald wie möglich und aktualisieren Sie ihren Status, sobald sie abgeschlossen ist.',
            default => 'Please process this task as soon as possible and update its status once completed.',
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
