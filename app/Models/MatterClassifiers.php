<?php

namespace App\Models;

use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $matter_id
 * @property ?string $type_code
 * @property ?string $type_name
 * @property ?int $main_display
 * @property ?string $value
 * @property ?string $url
 * @property ?int $lnk_matter_id
 * @property ?int $display_order
 * @property-read \App\Models\Matter|null $linkedMatter
 * @property-read \App\Models\Matter|null $matter
 * @property-read \App\Models\ClassifierType|null $classifierType
 */
class MatterClassifiers extends Model
{
    use HasTranslationsExtended;

    public $timestamps = false;

    // Define which attributes are translatable
    public $translatable = ['type_name'];

    /**
     * @return BelongsTo<Matter, $this>
     */
    public function linkedMatter(): BelongsTo
    {
        return $this->belongsTo(Matter::class, 'lnk_matter_id');
    }

    /**
     * @return BelongsTo<Matter, $this>
     */
    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    /**
     * @return BelongsTo<ClassifierType, $this>
     */
    public function classifierType(): BelongsTo
    {
        return $this->belongsTo(ClassifierType::class, 'type_code', 'code');
    }
}
