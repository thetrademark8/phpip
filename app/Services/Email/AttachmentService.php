<?php

namespace App\Services\Email;

use App\Models\Matter;
use App\Models\MatterAttachment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    protected string $disk = 's3';

    protected string $basePath = 'matter-attachments';

    public function __construct()
    {
        // Allow override from config
        $this->disk = config('filesystems.default', 's3');
    }

    /**
     * Upload a file and create an attachment record.
     */
    public function upload(
        Matter $matter,
        UploadedFile $file,
        ?string $category = null,
        ?string $description = null
    ): MatterAttachment {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $this->basePath . '/' . $matter->id . '/' . $filename;

        Storage::disk($this->disk)->put($path, file_get_contents($file));

        return MatterAttachment::create([
            'matter_id' => $matter->id,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $this->disk,
            'path' => $path,
            'category' => $category,
            'description' => $description,
            'creator' => Auth::user()?->login,
        ]);
    }

    /**
     * Delete an attachment and its file.
     */
    public function delete(MatterAttachment $attachment): bool
    {
        Storage::disk($attachment->disk)->delete($attachment->path);

        return $attachment->delete();
    }

    /**
     * Get all attachments for a matter, optionally filtered by category.
     */
    public function getAttachmentsForMatter(Matter $matter, ?string $category = null): Collection
    {
        $query = $matter->attachments();

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get();
    }

    /**
     * Get a temporary URL for an attachment (for S3).
     */
    public function getTemporaryUrl(MatterAttachment $attachment, int $minutes = 60): string
    {
        if ($attachment->disk === 's3') {
            return Storage::disk($attachment->disk)->temporaryUrl(
                $attachment->path,
                now()->addMinutes($minutes)
            );
        }

        return Storage::disk($attachment->disk)->url($attachment->path);
    }

    /**
     * Get the contents of an attachment.
     */
    public function getContents(MatterAttachment $attachment): string
    {
        return Storage::disk($attachment->disk)->get($attachment->path);
    }

    /**
     * Check if an attachment exists.
     */
    public function exists(MatterAttachment $attachment): bool
    {
        return Storage::disk($attachment->disk)->exists($attachment->path);
    }
}
