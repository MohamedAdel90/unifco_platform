<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicServiceRequest extends Model
{
    protected $fillable = [
        'reference_no','request_type','service_category','subject','details','site_city','urgency',
        'company_name','commercial_registration','email','mobile','status','submitted_at',
        'tenant_id','organization_id','crm_lead_id','crm_opportunity_id','crm_quotation_id',
        'service_request_id','work_order_id','converted_at',
    ];

    protected $casts = ['submitted_at' => 'datetime', 'converted_at' => 'datetime'];
}
