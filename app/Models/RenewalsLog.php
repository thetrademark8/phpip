<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $task_id
 * @property ?string $job_id
 * @property ?int $from_step
 * @property ?int $to_step
 * @property ?int $from_grace
 * @property ?int $to_grace
 * @property ?int $from_invoice
 * @property ?int $to_invoice
 * @property ?string $from_done
 * @property ?string $to_done
 * @property ?string $creator
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Matter|null $matter
 * @property-read \App\Models\User|null $creatorInfo
 * @property-read \App\Models\Task|null $task
 */
class RenewalsLog extends Model
{
    protected $guarded = [];

    /**
     * Get the matter of the log line.
     *
     * @return BelongsTo<Matter, $this>
     */
    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    /**
     * Get the matter of the creator.
     *
     * @return BelongsTo<User, $this>
     */
    public function creatorInfo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator', 'login');
    }

    /**
     * Get the matter of the renewal task.
     *
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
