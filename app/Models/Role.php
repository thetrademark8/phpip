<?php

namespace App\Models;

use App\Traits\HasTableComments;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;

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
