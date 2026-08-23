<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class HrWorkforcePlan extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','plan_year','department','target_headcount','budgeted_monthly_cost','target_saudi_pct','notes','status','created_by','updated_by'];
    protected function casts(): array { return ['target_headcount'=>'integer','budgeted_monthly_cost'=>'decimal:2','target_saudi_pct'=>'decimal:2']; }
}
