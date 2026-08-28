<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgreedAssetIntelligenceService
{
    public function createPmPlan(Asset $asset,int $actorId,array $data): int
    {
        $id=DB::table('maintenance_plans')->insertGetId([
            'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,
            'plan_no'=>$data['plan_no']??('PM-'.strtoupper(Str::random(8))),'name'=>$data['name'],'frequency_type'=>$data['frequency_type'],
            'frequency_value'=>$data['frequency_value'],'next_due_date'=>$data['next_due_date']??null,'next_due_meter'=>$data['next_due_meter']??null,
            'priority'=>$data['priority']??'NORMAL','status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->event($asset,'PM_PLAN_CREATED','Preventive maintenance plan created',$actorId,['maintenance_plan_id'=>$id]);
        return $id;
    }

    public function recordInspection(Asset $asset,int $actorId,array $data): int
    {
        $id=DB::table('asset_inspection_records')->insertGetId([
            'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'work_order_id'=>$data['work_order_id']??null,
            'inspection_date'=>$data['inspection_date'],'inspection_type'=>$data['inspection_type']??'GENERAL','condition_status'=>$data['condition_status'],
            'findings'=>$data['findings']??null,'recommendations'=>$data['recommendations']??null,'inspected_by'=>$actorId,'created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->event($asset,'ASSET_INSPECTED','Asset inspection recorded',$actorId,['inspection_id'=>$id,'condition'=>$data['condition_status']]);
        return $id;
    }

    public function recordFailure(Asset $asset,int $actorId,array $data): int
    {
        $failed=Carbon::parse($data['failed_at']);
        $restored=!empty($data['restored_at'])?Carbon::parse($data['restored_at']):null;
        abort_if($restored && $restored->lt($failed),422,'Restoration cannot precede failure.');
        $downtime=$restored ? $failed->diffInMinutes($restored) : (int)($data['downtime_minutes']??0);
        $id=DB::table('asset_failures')->insertGetId([
            'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'work_order_id'=>$data['work_order_id']??null,
            'failure_code'=>$data['failure_code']??null,'failure_mode'=>$data['failure_mode']??null,'failure_effect'=>$data['failure_effect']??null,
            'failure_cause'=>$data['failure_cause']??null,'root_cause'=>$data['root_cause']??null,'corrective_action'=>$data['corrective_action']??null,
            'failed_at'=>$failed,'restored_at'=>$restored,'downtime_minutes'=>$downtime,'meter_at_failure'=>$data['meter_at_failure']??$asset->meter_value,
            'severity'=>$data['severity']??'MEDIUM','status'=>$restored?'RESTORED':'OPEN','reported_by'=>$actorId,'created_at'=>now(),'updated_at'=>now(),
        ]);
        app(AssetMaintenanceIntelligenceService::class)->recalculate($asset->fresh());
        $this->event($asset,'ASSET_FAILURE_RECORDED','Asset failure recorded',$actorId,['failure_id'=>$id,'downtime_minutes'=>$downtime]);
        return $id;
    }

    public function phaseBSnapshot(Asset $asset): array
    {
        $base=app(AssetMaintenanceIntelligenceService::class)->snapshot($asset);
        $base['pm_plans']=DB::table('maintenance_plans')->where('asset_id',$asset->id)->count();
        $base['meter_readings']=DB::table('asset_meter_readings')->where('asset_id',$asset->id)->count();
        $base['inspections']=DB::table('asset_inspection_records')->where('asset_id',$asset->id)->count();
        return $base;
    }

    public function phaseCSnapshot(Asset $asset): array
    {
        $workOrders=DB::table('work_orders')->where('asset_id',$asset->id);
        $labor=(float)(clone $workOrders)->sum('labor_cost');
        $materials=(float)(clone $workOrders)->sum('material_cost');
        $external=(float)(clone $workOrders)->sum('external_cost');
        $installed=DB::table('asset_part_installations')->where('asset_id',$asset->id)->count();
        $partSpend=(float)DB::table('asset_part_installations')->where('asset_id',$asset->id)->sum('total_cost');
        $lifetime=$labor+$materials+$external;
        return [
            'parent_asset_id'=>$asset->parent_asset_id,'child_assets'=>Asset::where('parent_asset_id',$asset->id)->count(),
            'installed_components'=>$installed,'spare_parts_history'=>$installed,'part_installation_cost'=>$partSpend,
            'labor_cost'=>round($labor,2),'material_cost'=>round($materials,2),'external_cost'=>round($external,2),'lifetime_maintenance_cost'=>round($lifetime,2),
            'replacement_value'=>(float)($asset->replacement_value??0),'net_book_value'=>(float)($asset->net_book_value??0),
            'replacement_recommendation'=>$asset->replacement_recommendation,'replacement_reason'=>$asset->replacement_reason,
        ];
    }

    private function event(Asset $asset,string $type,string $title,int $actorId,array $metadata): void
    {
        DB::table('asset_lifecycle_events')->insert(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'event_type'=>$type,'from_status'=>$asset->lifecycle_status,'to_status'=>$asset->lifecycle_status,'title'=>$title,'metadata'=>json_encode($metadata),'performed_by'=>$actorId,'performed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
    }
}
