<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $matter_id
 * @property ?string $type_code
 * @property ?string $value
 * @property ?string $img
 * @property ?string $url
 * @property ?int $value_id
 * @property ?int $display_order
 * @property ?int $lnk_matter_id
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\ClassifierType|null $type
 * @property-read \App\Models\Matter|null $linkedMatter
 * @property-read \App\Models\Matter|null $matter
 */
class Classifier extends Model
{
    use HasFactory;

    protected $table = 'classifier';

    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $touches = ['matter'];

    /**
     * @return BelongsTo<ClassifierType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ClassifierType::class, 'type_code');
    }

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
}
