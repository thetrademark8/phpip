<?php

namespace App\Notifications\Renewal;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class RenewalReportNotification extends BaseRenewalNotification
{
    protected Collection $renewals;
    protected string $recipient;
    protected string $subject;
    protected array $reportData;

    /**
     * Create a new notification instance.
     */
    public function __construct(Collection $renewals, string $recipient)
    {
        $this->renewals = $renewals;
        $this->recipient = $recipient;
        $this->language = app()->getLocale(); // Default to app locale for reports
        $this->subject = 'Renewal Report - ' . $renewals->count() . ' renewals';
        
        // Prepare report data
        $this->reportData = [
            'renewals' => $renewals,
            'total_count' => $renewals->count(),
            'total_cost' => $renewals->sum('cost'),
            'total_fee' => $renewals->sum('fee'),
            'generated_at' => now(),
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->subject)
            ->view('email.renewalReport', array_merge($this->reportData, [
                'language' => $this->language,
                'recipient' => $this->recipient,
                'subject' => $this->subject,
            ]));

        return $this->addEmailHeaders($message);
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable): array
    {
        return $this->getCommonDatabaseData('renewal_report', [
            'recipient' => $this->recipient,
            'renewals_count' => $this->reportData['total_count'],
            'total_cost' => $this->reportData['total_cost'],
            'total_fee' => $this->reportData['total_fee'],
            'renewal_ids' => $this->renewals->pluck('id')->toArray(),
            'subject' => $this->subject,
        ]);
    }
}