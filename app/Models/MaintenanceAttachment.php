<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceAttachment extends Model
{
    protected $fillable=['tenant_id','customer_id','asset_id','work_order_id','visit_report_id','attachment_type','original_name','storage_path','mime_type','size_bytes'];
}
