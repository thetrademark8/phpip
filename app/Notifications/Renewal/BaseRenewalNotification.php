<?php

namespace App\Notifications\Renewal;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

abstract class BaseRenewalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $language;

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Abstract method to be implemented by child classes for mail representation.
     */
    abstract public function toMail($notifiable): MailMessage;

    /**
     * Abstract method to be implemented by child classes for database representation.
     */
    abstract public function toDatabase($notifiable): array;

    /**
     * Add common email headers for read receipts.
     */
    protected function addEmailHeaders(MailMessage $message): MailMessage
    {
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
     * Get common data for database storage.
     */
    protected function getCommonDatabaseData(string $type, array $additionalData = []): array
    {
        return array_merge([
            'type' => $type,
            'language' => $this->language,
            'created_by' => Auth::id(),
            'sent_at' => now(),
        ], $additionalData);
    }

    /**
     * Set the notification locale based on the first renewal or app locale.
     */
    protected function setLanguageFromRenewals($renewals): void
    {
        if (is_object($renewals) && method_exists($renewals, 'first')) {
            $first = $renewals->first();
            $this->language = $first['language'] ?? app()->getLocale();
        } else {
            $this->language = app()->getLocale();
        }
    }
}