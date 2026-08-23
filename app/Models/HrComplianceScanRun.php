<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class HrComplianceScanRun extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','scan_no','summary','run_by','completed_at']; protected function casts():array{return ['summary'=>'array','completed_at'=>'datetime'];} }
