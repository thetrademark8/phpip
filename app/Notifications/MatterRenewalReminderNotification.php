<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class MatterRenewalReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Collection $matters;
    protected string $reminderType;
    protected int $monthsRemaining;
    protected string $language;

    /**
     * Create a new notification instance.
     *
     * @param Collection $matters - Matters that need renewal
     * @param string $reminderType - 'first', 'second', or 'last'
     * @param int $monthsRemaining - Months until expiry (6, 3, or 1)
     * @param string $language - Notification language
     */
    public function __construct(Collection $matters, string $reminderType, int $monthsRemaining, string $language = 'en')
    {
        $this->matters = $matters;
        $this->reminderType = $reminderType;
        $this->monthsRemaining = $monthsRemaining;
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
        $subject = $this->getSubject();

        return (new MailMessage)
            ->subject($subject)
            ->markdown('notifications.matter-renewal-reminder', [
                'matters' => $this->matters,
                'reminderType' => $this->reminderType,
                'monthsRemaining' => $this->monthsRemaining,
                'language' => $this->language,
                'phpip_url' => config('app.url'),
            ]);
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'matter_renewal_reminder',
            'reminder_type' => $this->reminderType,
            'months_remaining' => $this->monthsRemaining,
            'matter_count' => $this->matters->count(),
            'matter_ids' => $this->matters->pluck('id')->toArray(),
            'matter_uids' => $this->matters->pluck('uid')->toArray(),
            'language' => $this->language,
            'sent_at' => now(),
        ];
    }

    /**
     * Get localized email subject
     */
    private function getSubject(): string
    {
        $count = $this->matters->count();

        return match ($this->language) {
            'fr' => $this->getFrenchSubject($count),
            'de' => $this->getGermanSubject($count),
            default => $this->getEnglishSubject($count),
        };
    }

    private function getEnglishSubject(int $count): string
    {
        $reminderLabel = match ($this->reminderType) {
            'first' => '1st Reminder',
            'second' => '2nd Reminder',
            'last' => 'FINAL Reminder',
            default => 'Reminder',
        };

        $matterText = $count === 1 ? 'matter' : 'matters';

        return "[phpIP] Renewal {$reminderLabel}: {$count} {$matterText} expiring in {$this->monthsRemaining} month" . ($this->monthsRemaining > 1 ? 's' : '');
    }

    private function getFrenchSubject(int $count): string
    {
        $reminderLabel = match ($this->reminderType) {
            'first' => '1er rappel',
            'second' => '2eme rappel',
            'last' => 'DERNIER rappel',
            default => 'Rappel',
        };

        $matterText = $count === 1 ? 'dossier expire' : 'dossiers expirent';

        return "[phpIP] Renouvellement - {$reminderLabel}: {$count} {$matterText} dans {$this->monthsRemaining} mois";
    }

    private function getGermanSubject(int $count): string
    {
        $reminderLabel = match ($this->reminderType) {
            'first' => '1. Erinnerung',
            'second' => '2. Erinnerung',
            'last' => 'LETZTE Erinnerung',
            default => 'Erinnerung',
        };

        $matterText = $count === 1 ? 'Akte' : 'Akten';

        return "[phpIP] Verlangerung - {$reminderLabel}: {$count} {$matterText} lauft in {$this->monthsRemaining} Monat" . ($this->monthsRemaining > 1 ? 'en' : '') . ' ab';
    }
}
