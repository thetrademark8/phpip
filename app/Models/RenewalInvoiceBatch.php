<?php

namespace App\Models;

use Database\Factories\RenewalInvoiceBatchFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RenewalInvoiceBatch extends Model
{
    /** @use HasFactory<RenewalInvoiceBatchFactory> */
    use HasFactory, HasUlids;

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_CREATED = 'created';

    public const STATUS_FAILED = 'failed';

    public const STATUS_UNCERTAIN = 'uncertain';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'actor_id' => 'integer',
            'dolibarr_socid' => 'integer',
            'external_invoice_id' => 'integer',
            'payload' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'login');
    }

    public function batchTasks(): HasMany
    {
        return $this->hasMany(RenewalInvoiceBatchTask::class, 'batch_id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'renewal_invoice_batch_tasks', 'batch_id', 'task_id')
            ->withTimestamps();
    }
}
