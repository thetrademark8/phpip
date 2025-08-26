<?php

namespace App\Models;

use App\Traits\HasTableComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Translation\HasLocalePreference;

class Actor extends Model implements HasLocalePreference
{
    use HasFactory, HasTableComments, Notifiable;

    protected $table = 'actor';

    // Hide these from JSON/array output
    protected $hidden = [
        'password',
        'remember_token',
        'creator',
        'updater',
        'created_at',
        'updated_at',
        'login',
    ];

    // Prevent mass assignment of these
    protected $guarded = [
        'id',
        'password',
        'remember_token',
        'creator',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'language' => 'string',
    ];

    /**
     * Get the actor's preferred language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language ?? config('app.locale');
    }

    /**
     * Get the actor's preferred locale for Laravel notifications.
     * Implementation of HasLocalePreference contract.
     *
     * @return string
     */
    public function preferredLocale(): string
    {
        return $this->getLanguage();
    }

    public function company()
    {
        return $this->belongsTo(Actor::class, 'company_id');
    }

    public function parent()
    {
        return $this->belongsTo(Actor::class, 'parent_id');
    }

    public function site()
    {
        return $this->belongsTo(Actor::class, 'site_id');
    }

    public function matters()
    {
        return $this->belongsToMany(Matter::class, 'matter_actor_lnk');
    }

    public function mattersWithLnk()
    {
        return $this->hasMany(ActorPivot::class, 'actor_id');
    }

    public function droleInfo()
    {
        return $this->belongsTo(Role::class, 'default_role');
    }

    public function countryInfo()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function country_mailingInfo()
    {
        return $this->belongsTo(Country::class, 'country_mailing');
    }

    public function country_billingInfo()
    {
        return $this->belongsTo(Country::class, 'country_billing');
    }

    public function nationalityInfo()
    {
        return $this->belongsTo(Country::class, 'nationality');
    }

    /**
     * Route notifications for mail delivery.
     * Required for Laravel Notifications to work with Actor model.
     */
    public function routeNotificationForMail()
    {
        return $this->email;
    }
}
