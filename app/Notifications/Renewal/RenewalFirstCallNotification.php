<?php

namespace App\Notifications\Renewal;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\TemplateMember;
use Illuminate\Database\Eloquent\Builder;

class RenewalFirstCallNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Collection $renewals;
    protected string $language;
    protected string $subject;
    protected string $validity_date;
    protected string $instruction_date;
    protected float $total;
    protected float $total_ht;
    protected string $dest;
    protected int $gracePeriod;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        Collection $renewals,
        string $validity_date,
        string $instruction_date,
        float $total,
        float $total_ht,
        string $subject,
        string $dest,
        int $gracePeriod = 0
    ) {
        $this->renewals = $renewals->sortBy([
            ['caseref', 'asc'],
            ['country', 'asc'],
        ])->values();

        $this->language = $this->renewals->first()['language'] ?? app()->getLocale();
        $this->validity_date = $validity_date;
        $this->instruction_date = $instruction_date;
        $this->total = $total;
        $this->total_ht = $total_ht;
        $this->subject = $subject;
        $this->dest = $dest;
        $this->gracePeriod = $gracePeriod;
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
        // Get the template
        $template = TemplateMember::whereHas('class', function (Builder $q) {
            $q->where('name', 'sys_renewals');
        })->where('language', $this->language)
            ->where('category', 'firstcall')
            ->firstOrFail();

        $this->subject .= $template->subject;

        $message = (new MailMessage)
            ->subject($this->subject)
            ->view('email.renewalCall', [
                'template' => $template,
                'language' => $this->language,
                'renewals' => $this->renewals,
                'validity_date' => $this->validity_date,
                'instruction_date' => $this->instruction_date,
                'total' => $this->total,
                'total_ht' => $this->total_ht,
                'step' => 'first',
                'dest' => $this->dest,
            ]);

        // Add confirmation headers if user is authenticated
        if (Auth::check()) {
            $message->withSymfonyMessage(function ($symfonyMessage) {
                $headers = $symfonyMessage->getHeaders();
                $userEmail = Auth::user()->email;
                $headers->addTextHeader('X-Confirm-Reading-To', '<' . $userEmail . '>');
                $headers->addTextHeader('Return-receipt-to', '<' . $userEmail . '>');
            });
        }

        return $message;
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'renewal_first_call',
            'renewals_count' => $this->renewals->count(),
            'total' => $this->total,
            'total_ht' => $this->total_ht,
            'validity_date' => $this->validity_date,
            'instruction_date' => $this->instruction_date,
            'language' => $this->language,
            'grace_period' => $this->gracePeriod,
            'renewal_ids' => $this->renewals->pluck('id')->toArray(),
        ];
    }
}