<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicServiceRequest extends Model
{
    protected $fillable = [
        'reference_no','request_type','service_category','subject','details','site_city','urgency',
        'company_name','commercial_registration','email','mobile','status','submitted_at',
    ];

    protected $casts = ['submitted_at' => 'datetime'];
}
