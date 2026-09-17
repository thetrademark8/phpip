<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property int $matter_id
 * @property ?int $template_id
 * @property int $sender_id
 * @property ?string $recipient_email
 * @property ?string $recipient_name
 * @property ?array<int, mixed> $cc
 * @property ?array<int, mixed> $bcc
 * @property ?string $subject
 * @property ?string $body_html
 * @property ?string $body_text
 * @property ?array<int, mixed> $attachments
 * @property string $status
 * @property ?string $error_message
 * @property ?\Illuminate\Support\Carbon $sent_at
 * @property ?string $creator
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Matter|null $matter
 * @property-read \App\Models\TemplateMember|null $template
 * @property-read \App\Models\Actor|null $sender
 * @property-read string $status_color
 */
class EmailLog extends Model
{
    use HasUuids;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'cc' => 'array',
        'bcc' => 'array',
        'attachments' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Matter, $this>
     */
    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    /**
     * @return BelongsTo<TemplateMember, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateMember::class, 'template_id');
    }

    /**
     * @return BelongsTo<Actor, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'sender_id');
    }

    public function attachmentFiles(): Collection
    {
        if (empty($this->attachments)) {
            return collect();
        }

        return MatterAttachment::whereIn('id', $this->attachments)->get();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'green',
            'failed' => 'red',
            'pending' => 'yellow',
            default => 'gray',
        };
    }
}
