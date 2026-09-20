<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','customer_id','customer_site_id','service_contract_id','project_id','asset_id','operational_domain_id','assigned_engineer_id','operations_manager_id','project_manager_id','project_routing_status','operations_routing_status','request_no','request_type','request_subtype','company_name','commercial_registration','email','mobile',
        'service_category','subject','details','site_city','priority','status','workflow_stage','workflow_key','assigned_department','approval_state','next_action','workflow_context','eligibility','procurement_required','work_order_id','quotation_id','workflow_started_at','current_stage_due_at',
        'responded_at','resolved_at','response_sla_minutes','resolution_sla_minutes',
    ];

    protected function casts(): array
    {
        return [
            'responded_at'=>'datetime','resolved_at'=>'datetime','workflow_started_at'=>'datetime','current_stage_due_at'=>'datetime',
            'procurement_required'=>'boolean','workflow_context'=>'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ServiceRequest $request) {
            if (!$request->operational_domain_id && $request->asset_id) {
                $request->operational_domain_id = Asset::query()->whereKey($request->asset_id)->value('operational_domain_id');
            }
        });
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function operationalDomain(): BelongsTo { return $this->belongsTo(OperationalDomain::class); }
    public function operationsManager(): BelongsTo { return $this->belongsTo(User::class, 'operations_manager_id'); }
    public function projectManager(): BelongsTo { return $this->belongsTo(User::class, 'project_manager_id'); }
}
