<?php

namespace App\Models;

use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;

class MatterClassifiers extends Model
{
    use HasTranslationsExtended;

    public $timestamps = false;

    // Define which attributes are translatable
    public $translatable = ['type_name'];

    public function linkedMatter()
    {
        return $this->belongsTo(Matter::class, 'lnk_matter_id');
    }

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }

    public function classifierType()
    {
        return $this->belongsTo(ClassifierType::class, 'type_code', 'code');
    }
}
