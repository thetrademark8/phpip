<?php

namespace App\Models;

use App\Traits\HasTableComments;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property bool $active
 * @property ?string $task
 * @property ?string $trigger_event
 * @property bool $clear_task
 * @property bool $delete_task
 * @property ?string $for_category
 * @property ?string $for_country
 * @property ?string $for_origin
 * @property ?string $for_type
 * @property ?string $detail
 * @property ?int $days
 * @property ?int $months
 * @property ?int $years
 * @property bool $recurring
 * @property bool $end_of_month
 * @property ?string $abort_on
 * @property ?string $condition_event
 * @property bool $use_priority
 * @property ?string $use_before
 * @property ?string $use_after
 * @property ?float $cost
 * @property ?float $fee
 * @property ?string $currency
 * @property ?string $responsible
 * @property ?string $notes
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?string $uid
 * @property-read \App\Models\Country|null $country
 * @property-read \App\Models\Country|null $origin
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\EventName|null $trigger
 * @property-read \App\Models\EventName|null $taskInfo
 * @property-read \App\Models\MatterType|null $type
 * @property-read \App\Models\EventName|null $condition_eventInfo
 * @property-read \App\Models\EventName|null $abort_onInfo
 * @property-read \App\Models\Actor|null $responsibleInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TemplateClass> $templates
 */
class Rule extends Model
{
    use HasTableComments;
    use HasTranslationsExtended;

    protected $table = 'task_rules';

    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $translatable = ['detail'];

    protected $casts = [
        'clear_task' => 'boolean',
        'delete_task' => 'boolean',
        'active' => 'boolean',
        'end_of_month' => 'boolean',
        'use_priority' => 'boolean',
        'recurring' => 'boolean',
    ];

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'for_country', 'iso');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'for_origin', 'iso');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'for_category', 'code');
    }

    /**
     * @return BelongsTo<EventName, $this>
     */
    public function trigger(): BelongsTo
    {
        return $this->belongsTo(EventName::class, 'trigger_event');
    }

    /**
     * @return BelongsTo<EventName, $this>
     */
    public function taskInfo(): BelongsTo
    {
        return $this->belongsTo(EventName::class, 'task');
    }

    /**
     * @return BelongsTo<MatterType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(MatterType::class, 'for_type', 'code');
    }

    /**
     * @return BelongsTo<EventName, $this>
     */
    public function condition_eventInfo(): BelongsTo
    {
        return $this->belongsTo(EventName::class, 'condition_event');
    }

    /**
     * @return BelongsTo<EventName, $this>
     */
    public function abort_onInfo(): BelongsTo
    {
        return $this->belongsTo(EventName::class, 'abort_on');
    }

    /**
     * @return BelongsTo<Actor, $this>
     */
    public function responsibleInfo(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'responsible', 'login');
    }

    /**
     * @return BelongsToMany<TemplateClass, $this>
     */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(TemplateClass::class, 'rule_class_lnk', 'task_rule_id', 'template_class_id');
    }
}
