<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicServiceRequest extends Model
{
    protected $fillable = [
        'reference_no','request_type','service_category','subject','details','site_city','site_address','latitude','longitude','urgency',
        'requested_date','requested_time','equipment_image_path','supporting_image_paths',
        'company_name','responsible_person','commercial_registration','email','mobile','status','submitted_at',
        'tenant_id','organization_id','crm_lead_id','crm_opportunity_id','crm_quotation_id',
        'service_request_id','work_order_id','converted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'converted_at' => 'datetime',
        'requested_date' => 'date',
        'supporting_image_paths' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
