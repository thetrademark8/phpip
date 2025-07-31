<?php

namespace App\Models;

use App\Traits\HasTableComments;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;

class ClassifierType extends Model
{
    use HasTableComments;
    use HasTranslationsExtended;

    protected $table = 'classifier_type';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = ['created_at', 'updated_at'];

    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'main_display' => 'boolean',
        'display_order' => 'integer',
    ];

    public $translatable = ['type'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'for_category', 'code');
    }
}
