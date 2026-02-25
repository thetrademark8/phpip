<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class AddGlobalBcc
{
    public function handle(MessageSending $event): void
    {
        $bcc = config('mail.global_bcc');

        if ($bcc) {
            $event->message->addBcc($bcc);
        }
    }
}
