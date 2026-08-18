<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class Inspection extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','work_order_id','asset_id','inspection_template_id','employee_id','inspection_no','status','responses','findings','recommendations','completed_at']; protected function casts():array{return ['responses'=>'array','completed_at'=>'datetime'];} }
