<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Models\Matter;
use App\Services\Email\EmailService;
use App\Services\Email\PlaceholderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MatterEmailController extends Controller
{
    public function __construct(
        protected EmailService $emailService,
        protected PlaceholderService $placeholderService
    ) {}

    /**
     * Show the email composer page.
     */
    public function compose(Matter $matter): Response
    {
        $matter->load(['actors.actor', 'attachments', 'titles', 'filing', 'grant', 'registration', 'countryInfo', 'category']);

        // Get recipients from matter actors (only those with email)
        $recipients = $matter->actors
            ->filter(fn ($a) => $a->actor?->email)
            ->map(fn ($a) => [
                'id' => $a->actor_id,
                'name' => $a->name,
                'email' => $a->actor?->email,
                'role' => $a->role_code,
                'role_name' => $a->role_name,
            ])
            ->values();

        $templates = $this->emailService->getTemplatesForMatter($matter, app()->getLocale());
        $placeholders = $this->placeholderService->getAvailablePlaceholders();

        return Inertia::render('Matter/EmailComposer', [
            'matter' => $matter,
            'recipients' => $recipients,
            'templates' => $templates,
            'placeholders' => $placeholders,
            'attachments' => $matter->attachments,
        ]);
    }

    /**
     * Preview an email with resolved placeholders.
     */
    public function preview(Request $request, Matter $matter): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string',
            'body' => 'required|string',
            'recipient_id' => 'nullable|integer|exists:actor,id',
        ]);

        $recipient = $request->recipient_id
            ? Actor::find($request->recipient_id)
            : null;

        $preview = $this->emailService->preview(
            $matter,
            $recipient,
            $request->subject,
            $request->body
        );

        return response()->json($preview);
    }

    /**
     * Send email(s) to selected recipients.
     */
    public function send(Request $request, Matter $matter): JsonResponse
    {
        $request->validate([
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'integer|exists:actor,id',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'cc' => 'nullable|array',
            'cc.*' => 'email',
            'bcc' => 'nullable|array',
            'bcc.*' => 'email',
            'attachment_ids' => 'nullable|array',
            'attachment_ids.*' => 'integer|exists:matter_attachments,id',
            'template_id' => 'nullable|integer|exists:template_members,id',
        ]);

        $results = [];
        $recipients = Actor::whereIn('id', $request->recipient_ids)->get();

        foreach ($recipients as $recipient) {
            $emailLog = $this->emailService->sendEmail(
                $matter,
                $recipient,
                $request->subject,
                $request->body,
                $request->cc ?? [],
                $request->bcc ?? [],
                $request->attachment_ids ?? [],
                $request->template_id
            );

            $results[] = [
                'recipient' => $recipient->email,
                'recipient_name' => $recipient->name,
                'status' => $emailLog->status,
                'email_log_id' => $emailLog->id,
                'error' => $emailLog->error_message,
            ];
        }

        $allSuccess = collect($results)->every(fn ($r) => $r['status'] === 'sent');

        return response()->json([
            'success' => $allSuccess,
            'results' => $results,
            'message' => $allSuccess
                ? __('email.sentSuccessfully')
                : __('email.partialFailure'),
        ]);
    }

    /**
     * Get email history for a matter.
     */
    public function history(Matter $matter): JsonResponse
    {
        $emails = $this->emailService->getEmailHistory($matter);

        return response()->json($emails);
    }

    /**
     * Show a specific email log.
     */
    public function showEmail(Matter $matter, string $emailLogId): JsonResponse
    {
        $email = $matter->emailLogs()->findOrFail($emailLogId);
        $email->load(['sender', 'template']);

        return response()->json([
            'email' => $email,
            'attachments' => $email->attachmentFiles(),
        ]);
    }
}
