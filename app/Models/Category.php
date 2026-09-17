<?php

namespace App\Models;

use App\Traits\HasTableComments;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property ?string $ref_prefix
 * @property ?string $category
 * @property ?string $display_with
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Matter> $matters
 * @property-read \App\Models\Category|null $displayWithInfo
 */
class Category extends Model
{
    use HasTableComments;
    use HasTranslationsExtended;

    protected $table = 'matter_category';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    protected $guarded = ['created_at', 'updated_at'];

    public $translatable = ['category'];

    /**
     * @return HasMany<Matter, $this>
     */
    public function matters(): HasMany
    {
        return $this->hasMany(Matter::class, 'category_code', 'code');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function displayWithInfo(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'display_with', 'code');
    }
}
