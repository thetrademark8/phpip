<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property ?string $name
 * @property ?string $login
 * @property ?string $password
 * @property ?string $default_role
 * @property ?int $company_id
 * @property ?string $email
 * @property ?string $phone
 * @property ?string $notes
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?string $remember_token
 * @property-read \App\Models\Role|null $roleInfo
 * @property-read \App\Models\Actor|null $company
 * @property-read \App\Models\Actor|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Matter> $matters
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'warn' => 'boolean',
    ];

    /**
     * @return BelongsTo<Role, $this>
     */
    public function roleInfo(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'default_role');
    }

    /**
     * @return BelongsTo<Actor, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'company_id');
    }

    /**
     * @return BelongsTo<Actor, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'parent_id');
    }

    /**
     * @return HasMany<Matter, $this>
     */
    public function matters(): HasMany
    {
        return $this->hasMany(Matter::class, 'responsible', 'login');
    }

    public function tasks()
    {
        return $this->matters()->has('tasksPending')->with('tasksPending');
    }

    public function renewals()
    {
        return $this->matters()->has('renewalsPending')->with('renewalsPending');
    }
}
