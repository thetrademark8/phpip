<?php

namespace App\Notifications;

use App\Models\EmailLog;
use App\Models\MatterAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MatterEmailNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected EmailLog $emailLog;

    protected string $subject;

    protected string $body;

    protected array $cc;

    protected array $bcc;

    protected Collection $attachments;

    public function __construct(
        EmailLog $emailLog,
        string $subject,
        string $body,
        array $cc,
        array $bcc,
        Collection $attachments
    ) {
        $this->emailLog = $emailLog;
        $this->subject = $subject;
        $this->body = $body;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->attachments = $attachments;
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
        $message = (new MailMessage)
            ->subject($this->subject)
            ->view('email.matter-email', [
                'body' => $this->body,
                'matter' => $this->emailLog->matter,
            ]);

        // Add CC recipients
        foreach ($this->cc as $ccEmail) {
            if (filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                $message->cc($ccEmail);
            }
        }

        // Add BCC recipients
        foreach ($this->bcc as $bccEmail) {
            if (filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
                $message->bcc($bccEmail);
            }
        }

        // Add attachments
        foreach ($this->attachments as $attachment) {
            if ($attachment instanceof MatterAttachment) {
                $contents = Storage::disk($attachment->disk)->get($attachment->path);
                $message->attachData(
                    $contents,
                    $attachment->original_name,
                    ['mime' => $attachment->mime_type]
                );
            }
        }

        // Add read receipt headers
        $this->addEmailHeaders($message);

        return $message;
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'matter_email',
            'email_log_id' => $this->emailLog->id,
            'matter_id' => $this->emailLog->matter_id,
            'subject' => $this->subject,
            'sent_by' => Auth::id(),
            'sent_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Add common email headers for read receipts.
     */
    protected function addEmailHeaders(MailMessage $message): MailMessage
    {
        if (Auth::check()) {
            $message->withSymfonyMessage(function ($symfonyMessage) {
                $headers = $symfonyMessage->getHeaders();
                $userEmail = Auth::user()->email;
                if ($userEmail) {
                    $headers->addTextHeader('X-Confirm-Reading-To', '<' . $userEmail . '>');
                    $headers->addTextHeader('Return-receipt-to', '<' . $userEmail . '>');
                }
            });
        }

        return $message;
    }
}
