<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function roleInfo()
    {
        return $this->belongsTo(Role::class, 'default_role');
    }

    public function company()
    {
        return $this->belongsTo(Actor::class, 'company_id');
    }

    public function parent()
    {
        return $this->belongsTo(Actor::class, 'parent_id');
    }

    public function matters()
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
