<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?string $code
 * @property ?int $matter_id
 * @property ?\Illuminate\Support\Carbon $event_date
 * @property ?string $detail
 * @property ?string $country
 * @property-read \App\Models\Matter|null $matter
 */
class EventLnkList extends Model
{
    protected $table = 'event_lnk_list';

    protected $casts = [
        'event_date' => 'date',
    ];

    /**
     * @return BelongsTo<Matter, $this>
     */
    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
