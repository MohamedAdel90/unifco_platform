<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterDataEntry extends Model
{
    public const TYPES=['SERVICE_TYPE','REQUEST_TYPE','PRIORITY','STATUS','CATEGORY'];
    protected $fillable=['tenant_id','type','code','name_ar','name_en','description','configuration','is_active'];
    protected function casts():array{return ['configuration'=>'array','is_active'=>'boolean'];}
}
