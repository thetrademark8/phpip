<?php

namespace App\Models;

use App\Traits\HasTableComments;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;

class MatterType extends Model
{
    use HasTableComments;
    use HasTranslationsExtended;

    protected $table = 'matter_type';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    protected $guarded = ['created_at', 'updated_at'];

    public $translatable = ['type'];
}
