<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class ConfigureGlobalRecipients
{
    public function handle(MessageSending $event): void
    {
        $bcc = config('mail.global_bcc');

        if ($bcc) {
            $event->message->addBcc($bcc);
        }

        $replyTo = config('mail.global_reply_to');

        if ($replyTo) {
            $event->message->addReplyTo($replyTo);
        }
    }
}
