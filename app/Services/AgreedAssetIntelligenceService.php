<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgreedAssetIntelligenceService
{
    public const STANDARD_TEMPLATES=[
        ['category'=>'Electrical','asset_type'=>'Transformer','name'=>'Distribution Transformer','schema'=>['manufacturer'=>'text','model'=>'text','serial_number'=>'text','rated_power_kva'=>'number','primary_voltage'=>'number','secondary_voltage'=>'number','frequency'=>'number','phase'=>'text','vector_group'=>'text','cooling_type'=>'text','impedance_percent'=>'number','oil_type'=>'text','manufacture_year'=>'number','temperature_class'=>'text']],
        ['category'=>'HVAC','asset_type'=>'Chiller','name'=>'Chiller','schema'=>['cooling_capacity'=>'number','refrigerant_type'=>'text','compressor_type'=>'text','voltage'=>'number','current'=>'number','power_consumption'=>'number','cop_eer'=>'number','number_of_compressors'=>'number']],
    ];

    public function ensureStandardTemplates(int $tenantId,?int $organizationId): void
    {
        foreach(self::STANDARD_TEMPLATES as $template){
            DB::table('asset_category_templates')->updateOrInsert(
                ['tenant_id'=>$tenantId,'category'=>$template['category'],'asset_type'=>$template['asset_type']],
                ['organization_id'=>$organizationId,'code'=>'STD-'.Str::upper(Str::slug($template['asset_type'],'_')),'system_group'=>Str::upper($template['category']),'name'=>$template['name'],'specification_schema'=>json_encode($template['schema']),'active'=>true,'status'=>'ACTIVE','updated_at'=>now(),'created_at'=>now()]
            );
        }
    }

    public function createPmPlan(Asset $asset,int $actorId,array $data): int
    {
        $id=DB::table('maintenance_plans')->insertGetId(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'plan_no'=>$data['plan_no']??('PM-'.strtoupper(Str::random(8))),'name'=>$data['name'],'frequency_type'=>$data['frequency_type'],'frequency_value'=>$data['frequency_value'],'next_due_date'=>$data['next_due_date']??null,'next_due_meter'=>$data['next_due_meter']??null,'priority'=>$data['priority']??'NORMAL','status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now()]);
        $asset->update(['pm_template'=>$data['name'],'pm_frequency'=>$data['frequency_value'].' '.$data['frequency_type'],'next_pm'=>$data['next_due_date']??null]);
        $this->event($asset,'PM_PLAN_CREATED','Preventive maintenance plan created',$actorId,['maintenance_plan_id'=>$id]);
        return $id;
    }

    public function recordInspection(Asset $asset,int $actorId,array $data): int
    {
        $id=DB::table('asset_inspection_records')->insertGetId(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'work_order_id'=>$data['work_order_id']??null,'inspection_date'=>$data['inspection_date'],'inspection_type'=>$data['inspection_type']??'GENERAL','condition_status'=>$data['condition_status'],'findings'=>$data['findings']??null,'recommendations'=>$data['recommendations']??null,'inspected_by'=>$actorId,'created_at'=>now(),'updated_at'=>now()]);
        $asset->update(['last_inspection'=>$data['inspection_date'],'next_inspection'=>$data['next_inspection']??null]);
        $this->event($asset,'ASSET_INSPECTED','Asset inspection recorded',$actorId,['inspection_id'=>$id,'condition'=>$data['condition_status']]);
        return $id;
    }

    public function recordFailure(Asset $asset,int $actorId,array $data): int
    {
        $failed=Carbon::parse($data['failed_at']); $restored=!empty($data['restored_at'])?Carbon::parse($data['restored_at']):null;
        abort_if($restored && $restored->lt($failed),422,'Restoration cannot precede failure.');
        $downtime=$restored?$failed->diffInMinutes($restored):(int)($data['downtime_minutes']??0);
        $id=DB::table('asset_failures')->insertGetId(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'work_order_id'=>$data['work_order_id']??null,'failure_code'=>$data['failure_code']??null,'failure_mode'=>$data['failure_mode']??null,'failure_effect'=>$data['failure_effect']??null,'failure_cause'=>$data['failure_cause']??null,'root_cause'=>$data['root_cause']??null,'corrective_action'=>$data['corrective_action']??null,'failed_at'=>$failed,'restored_at'=>$restored,'downtime_minutes'=>$downtime,'meter_at_failure'=>$data['meter_at_failure']??$asset->meter_value,'severity'=>$data['severity']??'MEDIUM','status'=>$restored?'RESTORED':'OPEN','reported_by'=>$actorId,'created_at'=>now(),'updated_at'=>now()]);
        $asset->update(['failure_impact'=>$data['severity']??'MEDIUM']); app(AssetMaintenanceIntelligenceService::class)->recalculate($asset->fresh());
        $this->event($asset,'ASSET_FAILURE_RECORDED','Asset failure recorded',$actorId,['failure_id'=>$id,'downtime_minutes'=>$downtime]); return $id;
    }

    public function phaseBSnapshot(Asset $asset): array
    {
        $base=app(AssetMaintenanceIntelligenceService::class)->snapshot($asset); $base['pm_plans']=DB::table('maintenance_plans')->where('asset_id',$asset->id)->count(); $base['meter_readings']=DB::table('asset_meter_readings')->where('asset_id',$asset->id)->count(); $base['inspections']=DB::table('asset_inspection_records')->where('asset_id',$asset->id)->count(); return $base;
    }

    public function phaseCSnapshot(Asset $asset): array
    {
        $workOrders=DB::table('work_orders')->where('asset_id',$asset->id); $labor=(float)(clone $workOrders)->sum('labor_cost'); $materials=(float)(clone $workOrders)->sum('material_cost'); $external=(float)(clone $workOrders)->sum('external_cost'); $installed=DB::table('asset_part_installations')->where('asset_id',$asset->id)->count(); $partSpend=(float)DB::table('asset_part_installations')->where('asset_id',$asset->id)->sum('total_cost'); $lifetime=$labor+$materials+$external;
        return ['parent_asset_id'=>$asset->parent_asset_id,'child_assets'=>Asset::where('parent_asset_id',$asset->id)->count(),'installed_components'=>$installed,'spare_parts_history'=>$installed,'part_installation_cost'=>$partSpend,'labor_cost'=>round($labor,2),'material_cost'=>round($materials,2),'external_cost'=>round($external,2),'lifetime_maintenance_cost'=>round($lifetime,2),'replacement_value'=>(float)($asset->replacement_value??0),'net_book_value'=>(float)($asset->net_book_value??0),'replacement_recommendation'=>$asset->replacement_recommendation,'replacement_reason'=>$asset->replacement_reason];
    }

    private function event(Asset $asset,string $type,string $title,int $actorId,array $metadata): void
    { DB::table('asset_lifecycle_events')->insert(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'event_type'=>$type,'from_status'=>$asset->lifecycle_status,'to_status'=>$asset->lifecycle_status,'title'=>$title,'metadata'=>json_encode($metadata),'performed_by'=>$actorId,'performed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]); }
}
