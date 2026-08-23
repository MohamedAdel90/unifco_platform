<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandingSetting extends Model
{
    protected $fillable = [
        'logo_path',
        'logo_mime',
        'logo_original_name',
        'updated_by',
    ];
}
