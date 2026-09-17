<?php

namespace App\Models;

use App\Traits\HasTableComments;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $code
 * @property ?string $name
 * @property ?int $display_order
 * @property bool $shareable
 * @property bool $show_ref
 * @property bool $show_company
 * @property bool $show_rate
 * @property bool $show_date
 * @property ?string $notes
 * @property ?string $creator
 * @property ?string $updater
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
class Role extends Model
{
    use HasTableComments;
    use HasTranslationsExtended;

    protected $table = 'actor_role';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $hidden = ['created_at', 'updated_at'];

    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'shareable' => 'boolean',
        'show_ref' => 'boolean',
        'show_company' => 'boolean',
        'show_rate' => 'boolean',
        'show_date' => 'boolean',
    ];

    public $incrementing = false;

    // Define which attributes are translatable
    public $translatable = ['name'];
}
