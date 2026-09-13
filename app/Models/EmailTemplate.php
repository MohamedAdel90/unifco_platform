<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable=['tenant_id','code','subject_ar','subject_en','body_ar','body_en','is_active'];
    protected function casts():array{return ['is_active'=>'boolean'];}
}
