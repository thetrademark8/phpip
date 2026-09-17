<?php

namespace App\Models;

use App\Traits\HasTableComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $actor_id
 * @property ?string $role
 * @property ?string $for_category
 * @property ?string $for_country
 * @property ?int $for_client
 * @property bool $shared
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Actor|null $actor
 * @property-read \App\Models\Country|null $country
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\Actor|null $client
 * @property-read \App\Models\Role|null $roleInfo
 */
class DefaultActor extends Model
{
    use HasTableComments;

    protected $table = 'default_actor';

    protected $guarded = ['created_at', 'updated_at'];

    /**
     * @return BelongsTo<Actor, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'for_country', 'iso');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'for_category', 'code');
    }

    /**
     * @return BelongsTo<Actor, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'for_client');
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function roleInfo(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'code');
    }
}
