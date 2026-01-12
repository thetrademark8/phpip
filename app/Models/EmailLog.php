<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

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

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateMember::class, 'template_id');
    }

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
