<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamleaderReference extends Model
{
    protected $table = 'teamleader_references';

    protected $fillable = [
        'actor_id',
        'teamleader_id',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'actor_id');
    }
}
