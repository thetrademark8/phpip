<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?string $event_name_code
 * @property ?int $template_class_id
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\TemplateClass|null $class
 */
class EventClassLnk extends Model
{
    protected $table = 'event_class_lnk';

    protected $guarded = [];

    /**
     * @return BelongsTo<TemplateClass, $this>
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(TemplateClass::class, 'template_class_id');
    }
}
