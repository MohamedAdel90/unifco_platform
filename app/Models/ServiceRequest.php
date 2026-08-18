<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','request_no','company_name','commercial_registration','email','mobile',
        'service_category','subject','details','site_city','priority','status','work_order_id',
    ];
}
