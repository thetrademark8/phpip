<?php

namespace App\Models;

use App\Traits\HasTableComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $class_id
 * @property ?string $language
 * @property ?string $style
 * @property ?string $category
 * @property ?string $format
 * @property ?string $summary
 * @property ?string $subject
 * @property ?string $body
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\TemplateClass|null $class
 */
class TemplateMember extends Model
{
    use HasTableComments;

    protected $guarded = ['created_at', 'updated_at'];

    /**
     * @return BelongsTo<TemplateClass, $this>
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(TemplateClass::class);
    }
}
