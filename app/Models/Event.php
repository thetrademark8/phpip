<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property ?string $code
 * @property ?int $matter_id
 * @property ?\Illuminate\Support\Carbon $event_date
 * @property ?int $alt_matter_id
 * @property ?string $detail
 * @property ?string $notes
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\EventName|null $info
 * @property-read \App\Models\Matter|null $matter
 * @property-read \App\Models\Matter|null $altMatter
 * @property-read \App\Models\Event|null $link
 * @property-read \App\Models\Event|null $retroLink
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 */
class Event extends Model
{
    use HasFactory;

    protected $table = 'event';

    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $touches = ['matter'];

    protected $casts = [
        'event_date' => 'date:Y-m-d',
    ];

    /**
     * @return BelongsTo<EventName, $this>
     */
    public function info(): BelongsTo
    {
        return $this->belongsTo(EventName::class, 'code', 'code');
    }

    /**
     * @return BelongsTo<Matter, $this>
     */
    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    /**
     * @return BelongsTo<Matter, $this>
     */
    public function altMatter(): BelongsTo
    {
        return $this->belongsTo(Matter::class, 'alt_matter_id')->withDefault();
    }

    /**
     * @return HasOne<Event, $this>
     */
    public function link(): HasOne
    {
        return $this->hasOne(Event::class, 'matter_id', 'alt_matter_id')->whereCode('FIL')->withDefault();
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function retroLink(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'matter_id', 'alt_matter_id')->withDefault();
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'trigger_id')->orderBy('due_date');
    }

    public function cleanNumber()
    {
        return preg_replace(["/^{$this->matter->country}/", '/ /', '/,/', '/-/', '/\//', '/\.[0-9]/'], '', $this->detail);
    }

    // Produces a link to official published information

    public function publicUrl()
    {
        if (!in_array($this->code, ['FIL', 'PUB', 'GRT'])) {
            return false;
        }
        if ($this->matter->origin == 'EP') {
            $CC = 'EP';
        } else {
            $CC = $this->matter->country;
        }
        $category = $this->matter->category_code;
        $cleanednumber = $this->cleanNumber();
        $href = '';
        $pubno = '';
        if ($this->code == 'PUB' || $this->code == 'GRT') {
            // Fix US pub number for Espacenet by keeping the last 6 digits after the year
            if ($CC == 'US' && $this->code == 'PUB') {
                $cleanednumber = substr($cleanednumber, 0, 4) . substr($cleanednumber, -6);
            }
            $href = "http://worldwide.espacenet.com/publicationDetails/biblio?DB=EPODOC&CC=$CC&NR=$cleanednumber";
        } elseif ($this->code == 'FIL') {
            switch ($this->matter->country) {
                case 'EP':
                    $href = "https://register.epo.org/espacenet/application?number=EP$cleanednumber";
                    break;
                case 'FR':
                    $pubno = $this->matter->publication->cleanNumber();
                    if ($category == 'PAT' && $pubno) {
                        $href = "https://data.inpi.fr/brevets/$CC$pubno";
                    } elseif ($category == 'TM') {
                        if ($this->event_date->isoFormat('YYYY') >= '2000') {
                            $cleanednumber = substr($cleanednumber, -7);
                        }
                        $href = "https://data.inpi.fr/marques/$CC$cleanednumber";
                    }
                    break;
                case 'US':
                    if (substr($cleanednumber, 0, 2) < 13) {
                        $cleanednumber = substr($cleanednumber, 2) . $this->event_date->isoFormat('YY');
                    } else {
                        $cleanednumber = $this->event_date->isoFormat('YYYY') . $cleanednumber;
                    }
                    $href = "https://register.epo.org/ipfwretrieve?apn=US.$cleanednumber.A";
                    break;
                case 'GB':
                    $href = "http://www.ipo.gov.uk/p-ipsum/Case/ApplicationNumber/$CC$cleanednumber";
                    break;
                case 'EM':
                    $href = "https://euipo.europa.eu/eSearch/#details/trademarks/$cleanednumber";
                    break;
            }
        }

        return $href;
    }
}
