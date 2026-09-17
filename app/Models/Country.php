<?php

namespace App\Models;

use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;

/**
 * @property ?int $numcode
 * @property string $iso
 * @property ?string $iso3
 * @property ?string $name_DE
 * @property ?string $name
 * @property ?string $name_FR
 * @property ?int $ep
 * @property ?int $wo
 * @property ?int $em
 * @property ?int $oa
 * @property ?int $renewal_first
 * @property ?string $renewal_base
 * @property ?string $renewal_start
 * @property ?\Illuminate\Support\Carbon $checked_on
 * @property-read bool $goesnational
 * @property-read \Illuminate\Support\Collection<string, mixed>|null $natcountries
 */
class Country extends Model
{
    use HasTranslationsExtended {
        getTranslations as baseGetTranslations;
    }

    protected $table = 'country';

    protected $primaryKey = 'iso';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $hidden = ['name_DE', 'name_FR', 'iso3', 'numcode'];

    protected $guarded = [];

    public $translatable = ['name'];

    /**
     * The country.name column is still a plain varchar that coexists with the
     * locale-specific name_FR / name_DE columns. A few rows were later updated
     * to contain JSON via the readability migration. Resolve both shapes here
     * so HasTranslations does not return an empty string when given plain text.
     */
    public function getTranslations(?string $key = null, ?array $allowedLocales = null): array
    {
        if ($key === 'name') {
            $raw = $this->getAttributes()['name'] ?? null;
            $decoded = is_string($raw) ? json_decode($raw, true) : null;

            if (is_array($decoded)) {
                return $decoded;
            }

            return array_filter([
                'en' => $raw,
                'fr' => $this->getAttributes()['name_FR'] ?? null,
                'de' => $this->getAttributes()['name_DE'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        return $this->baseGetTranslations($key, $allowedLocales);
    }

    public function getGoesnationalAttribute() // Defines "goesnational" as an attribute
    {
        return in_array($this->iso, ['EP', 'WO', 'EM', 'OA']);
    }

    public function getNatcountriesAttribute()
    {
        if ($this->goesnational) {
            return $this->where("$this->iso", 1)->pluck('name', 'iso');
        } else {
            return null;
        }
    }
}
