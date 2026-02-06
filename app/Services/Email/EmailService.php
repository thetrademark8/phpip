<?php

namespace App\Services\Email;

use App\Models\Actor;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Models\Matter;
use App\Models\TemplateMember;
use App\Notifications\MatterEmailNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function __construct(
        protected PlaceholderService $placeholderService
    ) {}

    /**
     * Send an email to a recipient.
     */
    public function sendEmail(
        Matter $matter,
        Actor $recipient,
        string $subject,
        string $body,
        array $cc = [],
        array $bcc = [],
        array $attachmentIds = [],
        ?int $templateId = null
    ): EmailLog {
        // Resolve placeholders
        $this->placeholderService->setMatter($matter)->setRecipient($recipient);
        $resolvedSubject = $this->placeholderService->resolve($subject);
        $resolvedBody = $this->placeholderService->resolve($body);

        // Apply branding
        $resolvedBody = $this->applyBranding($resolvedBody);

        // Get current user's actor ID for sender
        $senderId = Auth::user()?->id;

        // Create email log entry
        $emailLog = EmailLog::create([
            'matter_id' => $matter->id,
            'template_id' => $templateId,
            'sender_id' => $senderId,
            'recipient_email' => $recipient->email,
            'recipient_name' => $recipient->name,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => $resolvedSubject,
            'body_html' => $resolvedBody,
            'body_text' => strip_tags($resolvedBody),
            'attachments' => $attachmentIds,
            'status' => 'pending',
            'creator' => Auth::user()?->login,
        ]);

        try {
            // Send notification - pass attachment IDs directly
            // The notification will reload models from DB to avoid serialization issues
            $recipient->notify(new MatterEmailNotification(
                $emailLog,
                $resolvedSubject,
                $resolvedBody,
                $cc,
                $bcc,
                $attachmentIds
            ));

            $emailLog->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Email send failed', [
                'email_log_id' => $emailLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $emailLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $emailLog;
    }

    /**
     * Preview an email with resolved placeholders.
     */
    public function preview(
        Matter $matter,
        ?Actor $recipient,
        string $subject,
        string $body
    ): array {
        $this->placeholderService->setMatter($matter)->setRecipient($recipient);

        return [
            'subject' => $this->placeholderService->resolve($subject),
            'body' => $this->applyBranding($this->placeholderService->resolve($body)),
        ];
    }

    /**
     * Apply branding (header/footer) to email body.
     */
    protected function applyBranding(string $body): string
    {
        $header = EmailSetting::get('email_header', '');
        $footer = EmailSetting::get('email_footer', '');

        return $header . $body . $footer;
    }

    /**
     * Get available templates for a matter.
     */
    public function getTemplatesForMatter(Matter $matter, ?string $language = null): Collection
    {
        $query = TemplateMember::with('class')
            ->whereHas('class', function ($q) {
                // Exclude system templates (sys_*)
                $q->where('name', 'NOT LIKE', 'sys_%');
            });

        if ($language) {
            $query->where('language', $language);
        }

        return $query->orderBy('summary')->get();
    }

    /**
     * Get email history for a matter.
     */
    public function getEmailHistory(Matter $matter, int $perPage = 20)
    {
        return $matter->emailLogs()
            ->with(['sender', 'template'])
            ->paginate($perPage);
    }

    /**
     * Get available placeholders.
     */
    public function getAvailablePlaceholders(): array
    {
        return $this->placeholderService->getAvailablePlaceholders();
    }
}
