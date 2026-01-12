<?php

namespace App\Http\Controllers;

use App\Models\Matter;
use App\Models\MatterAttachment;
use App\Services\Email\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatterAttachmentController extends Controller
{
    public function __construct(
        protected AttachmentService $attachmentService
    ) {}

    /**
     * List all attachments for a matter.
     */
    public function index(Matter $matter, Request $request): JsonResponse
    {
        $attachments = $this->attachmentService->getAttachmentsForMatter(
            $matter,
            $request->category
        );

        return response()->json($attachments);
    }

    /**
     * Upload a new attachment.
     */
    public function store(Request $request, Matter $matter): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $attachment = $this->attachmentService->upload(
            $matter,
            $request->file('file'),
            $request->category,
            $request->description
        );

        return response()->json($attachment, 201);
    }

    /**
     * Download an attachment.
     */
    public function download(Matter $matter, MatterAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->matter_id === $matter->id, 404);
        abort_unless($this->attachmentService->exists($attachment), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name
        );
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Matter $matter, MatterAttachment $attachment): JsonResponse
    {
        abort_unless($attachment->matter_id === $matter->id, 404);

        $this->attachmentService->delete($attachment);

        return response()->json(['success' => true]);
    }

    /**
     * Get a temporary URL for an attachment (for preview).
     */
    public function temporaryUrl(Matter $matter, MatterAttachment $attachment): JsonResponse
    {
        abort_unless($attachment->matter_id === $matter->id, 404);

        $url = $this->attachmentService->getTemporaryUrl($attachment);

        return response()->json(['url' => $url]);
    }
}
