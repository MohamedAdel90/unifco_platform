<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class HrComplianceProfile extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','economic_activity','qiwa_contract_target_pct','nitaqat_reported_band','wps_status','last_wps_period','mudad_reference','last_gosi_reconciliation_on','last_qiwa_reconciliation_on','last_nitaqat_review_on','updated_by']; protected function casts():array{return ['last_wps_period'=>'date','last_gosi_reconciliation_on'=>'date','last_qiwa_reconciliation_on'=>'date','last_nitaqat_review_on'=>'date','qiwa_contract_target_pct'=>'decimal:2'];} }
