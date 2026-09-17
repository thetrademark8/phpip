<?php

namespace App\Models;

use App\Traits\HasTableComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?string $for_category
 * @property ?string $for_country
 * @property ?string $for_origin
 * @property ?int $qt
 * @property ?string $use_before
 * @property ?string $use_after
 * @property ?float $cost
 * @property ?float $fee
 * @property ?float $cost_reduced
 * @property ?float $fee_reduced
 * @property ?float $cost_sup
 * @property ?float $fee_sup
 * @property ?float $cost_sup_reduced
 * @property ?float $fee_sup_reduced
 * @property ?string $currency
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Country|null $country
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\Country|null $origin
 */
class Fee extends Model
{
    use HasTableComments;

    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'for_country');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'for_category', 'code');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'for_origin', 'iso');
    }
}
