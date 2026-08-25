<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicServiceRequest extends Model
{
    protected $fillable = [
        'reference_no','ticket_serial','request_type','request_intent','request_subtype','service_category','service_family','asset_type','service_other',
        'equipment_brand','equipment_model','subject','details','site_name','site_area','site_city','site_address','latitude','longitude','urgency',
        'requested_date','requested_time','equipment_image_path','equipment_photo_paths','problem_photo_paths','supporting_image_paths','supporting_document_paths','previous_report_paths',
        'company_name','responsible_person','contact_role','commercial_registration','email','mobile','status','submitted_at',
        'tenant_id','organization_id','asset_id','crm_lead_id','crm_opportunity_id','crm_quotation_id',
        'service_request_id','work_order_id','converted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'converted_at' => 'datetime',
        'requested_date' => 'date',
        'equipment_photo_paths' => 'array',
        'problem_photo_paths' => 'array',
        'supporting_image_paths' => 'array',
        'supporting_document_paths' => 'array',
        'previous_report_paths' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
