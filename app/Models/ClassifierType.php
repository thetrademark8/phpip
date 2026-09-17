<?php

namespace App\Models;

use App\Traits\HasTableComments;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $code
 * @property ?string $type
 * @property bool $main_display
 * @property ?string $for_category
 * @property int $display_order
 * @property ?string $notes
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Category|null $category
 */
class ClassifierType extends Model
{
    use HasTableComments;
    use HasTranslationsExtended;

    protected $table = 'classifier_type';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = ['created_at', 'updated_at'];

    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'main_display' => 'boolean',
        'display_order' => 'integer',
    ];

    public $translatable = ['type'];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'for_category', 'code');
    }
}
