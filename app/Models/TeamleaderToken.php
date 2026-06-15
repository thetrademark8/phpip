<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamleaderToken extends Model
{
    protected $table = 'teamleader_tokens';

    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
        'webhook_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return true;
        }

        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->access_token && !$this->isExpired();
    }
}
