<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenewalInvoiceBatchTask extends Model
{
    protected $guarded = [];

    protected $primaryKey = 'task_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public function batch(): BelongsTo
    {
        return $this->belongsTo(RenewalInvoiceBatch::class, 'batch_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
