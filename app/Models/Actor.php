<?php

namespace App\Models;

use App\Traits\HasTableComments;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property ?string $name
 * @property ?string $first_name
 * @property ?string $display_name
 * @property ?string $login
 * @property ?string $password
 * @property ?string $default_role
 * @property ?string $function
 * @property ?int $parent_id
 * @property ?int $company_id
 * @property ?int $site_id
 * @property bool $phy_person
 * @property ?string $nationality
 * @property ?string $language
 * @property bool $small_entity
 * @property ?string $address
 * @property ?string $country
 * @property ?string $address_mailing
 * @property ?string $country_mailing
 * @property ?string $address_billing
 * @property ?string $country_billing
 * @property ?string $email
 * @property ?string $phone
 * @property ?string $legal_form
 * @property ?string $registration_no
 * @property bool $warn
 * @property ?float $ren_discount
 * @property ?string $notes
 * @property ?string $VAT_number
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?string $remember_token
 * @property-read \App\Models\Actor|null $company
 * @property-read \App\Models\Actor|null $parent
 * @property-read \App\Models\Actor|null $site
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Matter> $matters
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActorPivot> $mattersWithLnk
 * @property-read \App\Models\Role|null $droleInfo
 * @property-read \App\Models\Country|null $countryInfo
 * @property-read \App\Models\Country|null $country_mailingInfo
 * @property-read \App\Models\Country|null $country_billingInfo
 * @property-read \App\Models\Country|null $nationalityInfo
 */
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
     * @var array<string, string>
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
        return !empty($this->language) ? $this->language : config('app.locale');
    }

    /**
     * Get the actor's preferred locale for Laravel notifications.
     * Implementation of HasLocalePreference contract.
     */
    public function preferredLocale(): string
    {
        return $this->getLanguage();
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
     * @return BelongsTo<Actor, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'site_id');
    }

    /**
     * @return BelongsToMany<Matter, $this>
     */
    public function matters(): BelongsToMany
    {
        return $this->belongsToMany(Matter::class, 'matter_actor_lnk');
    }

    /**
     * @return HasMany<ActorPivot, $this>
     */
    public function mattersWithLnk(): HasMany
    {
        return $this->hasMany(ActorPivot::class, 'actor_id');
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function droleInfo(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'default_role');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function countryInfo(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country_mailingInfo(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_mailing');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country_billingInfo(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_billing');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function nationalityInfo(): BelongsTo
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
