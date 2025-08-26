<?php

namespace App\Notifications;

use Illuminate\Support\Collection;

/**
 * System-specific tasks summary notification for email-only recipients.
 * Extends TasksSummaryNotification but only uses mail channel to avoid
 * database storage issues with non-user recipients.
 */
class SystemTasksSummaryNotification extends TasksSummaryNotification
{
    /**
     * Get the notification's delivery channels.
     * System notifications only use mail channel, not database.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * System notifications don't need database storage.
     * Override to prevent database operations for email-only recipients.
     */
    public function toDatabase($notifiable): array
    {
        // System notifications don't store in database
        // This method won't be called due to via() returning only ['mail']
        return [];
    }
}