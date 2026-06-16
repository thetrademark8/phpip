<?php

namespace App\Models;

use App\Traits\HasTableComments;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property ?string $name
 * @property ?string $category
 * @property ?string $country
 * @property bool $is_task
 * @property bool $status_event
 * @property ?string $default_responsible
 * @property bool $use_matter_resp
 * @property bool $unique
 * @property bool $killer
 * @property ?string $notes
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read \App\Models\Country|null $countryInfo
 * @property-read \App\Models\Category|null $categoryInfo
 * @property-read \App\Models\User|null $default_responsibleInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TemplateClass> $templates
 */
class EventName extends Model
{
    use HasTableComments;
    use HasTranslationsExtended;

    protected $table = 'event_name';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    protected $guarded = ['created_at', 'updated_at'];

    // Define which attributes are translatable
    public $translatable = ['name'];

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'code');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'code');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function countryInfo(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'iso');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function categoryInfo(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category', 'code');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function default_responsibleInfo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_responsible', 'login');
    }

    /**
     * @return BelongsToMany<TemplateClass, $this>
     */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(TemplateClass::class, 'event_class_lnk', 'event_name_code', 'template_class_id');
    }
}
