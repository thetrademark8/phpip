<?php

namespace App\Models;

use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $actor_id
 * @property ?string $display_name
 * @property ?string $name
 * @property ?string $first_name
 * @property ?string $email
 * @property ?int $display_order
 * @property ?string $role_code
 * @property ?string $role_name
 * @property ?int $shareable
 * @property ?int $show_ref
 * @property ?int $show_company
 * @property ?int $show_rate
 * @property ?int $show_date
 * @property ?int $matter_id
 * @property ?int $warn
 * @property ?string $actor_ref
 * @property ?string $date
 * @property ?float $rate
 * @property ?int $shared
 * @property ?int $company
 * @property ?int $inherited
 * @property-read \App\Models\Matter|null $matter
 * @property-read \App\Models\Actor|null $actor
 * @property-read \App\Models\Role|null $role
 */
class MatterActors extends Model
{
    use HasTranslationsExtended;

    public $timestamps = false;

    // Define which attributes are translatable
    public $translatable = ['role_name'];

    /**
     * @return BelongsTo<Matter, $this>
     */
    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    /**
     * @return BelongsTo<Actor, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_code');
    }

    /**
     * @return BelongsTo<Actor, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'company_id');
    }
}
