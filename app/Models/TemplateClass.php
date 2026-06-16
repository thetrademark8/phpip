<?php

namespace App\Models;

use App\Traits\HasTableComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property ?string $name
 * @property ?string $notes
 * @property ?string $default_role
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Role|null $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rule> $rules
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventName> $eventNames
 */
class TemplateClass extends Model
{
    use HasTableComments;

    protected $guarded = ['created_at', 'updated_at'];

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'default_role', 'code');
    }

    /**
     * @return BelongsToMany<Rule, $this>
     */
    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(Rule::class, 'rule_class_lnk', 'template_class_id', 'task_rule_id');
    }

    /**
     * @return BelongsToMany<EventName, $this>
     */
    public function eventNames(): BelongsToMany
    {
        return $this->belongsToMany(EventName::class, 'event_class_lnk', 'template_class_id', 'event_name_code');
    }
}
