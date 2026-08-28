<?php

namespace App\Services;

use App\Models\{Asset,AssetCommissioningRecord,AssetLifecycleEvent};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetMasterService
{
    public const LIFECYCLE=['DRAFT','PENDING_VERIFICATION','ACTIVE','UNDER_MAINTENANCE','OUT_OF_SERVICE','DECOMMISSIONED','DISPOSED'];
    public const CRITICALITY=['LOW','MEDIUM','HIGH','CRITICAL'];
    public const OWNERSHIP=['CUSTOMER_OWNED','UNIFCO_MANAGED','RENTAL','THIRD_PARTY'];
    public const STRATEGIES=['PREVENTIVE','PREDICTIVE','CONDITION_BASED','CORRECTIVE','RUN_TO_FAILURE'];
    public const LOCATION_TYPES=['SITE','BUILDING','FLOOR','ZONE','ROOM','AREA'];
    private const TRANSITIONS=['ACTIVE'=>['UNDER_MAINTENANCE','OUT_OF_SERVICE','DECOMMISSIONED'],'UNDER_MAINTENANCE'=>['ACTIVE','OUT_OF_SERVICE'],'OUT_OF_SERVICE'=>['ACTIVE','UNDER_MAINTENANCE','DECOMMISSIONED'],'DECOMMISSIONED'=>['DISPOSED'],'PENDING_VERIFICATION'=>['ACTIVE'],'DRAFT'=>['PENDING_VERIFICATION']];

    public function findStrongDuplicate(int $tenantId,?int $customerId,array $data,?int $exceptId=null): ?Asset
    {
        $base=Asset::where('tenant_id',$tenantId); if($customerId) $base->where('customer_id',$customerId); if($exceptId) $base->whereKeyNot($exceptId);
        if($serial=trim((string)($data['serial_no']??''))){ if($asset=(clone $base)->whereRaw('LOWER(serial_no)=?',[mb_strtolower($serial)])->first()) return $asset; }
        if($code=trim((string)($data['customer_asset_code']??''))){ if($asset=(clone $base)->whereRaw('LOWER(customer_asset_code)=?',[mb_strtolower($code)])->first()) return $asset; }
        return null;
    }

    public function create(int $tenantId,int $organizationId,int $userId,array $data): Asset
    {
        if($duplicate=$this->findStrongDuplicate($tenantId,$data['customer_id']??null,$data)) abort(422,'Possible duplicate asset: '.$duplicate->asset_code.' · '.$duplicate->name);
        $this->validateParent($tenantId,(int)($data['customer_id']??0),(int)($data['customer_site_id']??0),$data['parent_asset_id']??null);
        return DB::transaction(function() use($tenantId,$organizationId,$userId,$data){
            $temporary='AST-TMP-'.Str::upper(Str::random(12));
            $asset=Asset::create([...$data,'tenant_id'=>$tenantId,'organization_id'=>$organizationId,'asset_code'=>$data['asset_code']??$temporary,'qr_token'=>$data['qr_token']??Str::random(40),'lifecycle_status'=>'PENDING_VERIFICATION','operational_status'=>$data['operational_status']??'STANDBY','verification_status'=>'PENDING','commissioning_status'=>'NOT_STARTED','status'=>'REGISTERED']);
            if(empty($data['asset_code'])) $asset->update(['asset_code'=>'AST-UNF-'.str_pad((string)$asset->id,6,'0',STR_PAD_LEFT)]);
            $this->event($asset,'ASSET_CREATED',null,'PENDING_VERIFICATION','Asset created','Immutable internal asset identity created.',$userId);
            $this->event($asset,'QR_GENERATED','PENDING_VERIFICATION','PENDING_VERIFICATION','QR generated','Unique field QR identity generated.',$userId,['qr_token'=>$asset->qr_token]);
            if($asset->installation_date) $this->event($asset,'ASSET_INSTALLED','PENDING_VERIFICATION','PENDING_VERIFICATION','Asset installed','Installation date '.$asset->installation_date->toDateString(),$userId);
            return $this->refreshCompleteness($asset);
        });
    }

    public function update(Asset $asset,array $data): Asset
    {
        if($duplicate=$this->findStrongDuplicate((int)$asset->tenant_id,$data['customer_id']??$asset->customer_id,$data,$asset->id)) abort(422,'Possible duplicate asset: '.$duplicate->asset_code.' · '.$duplicate->name);
        $this->validateParent((int)$asset->tenant_id,(int)($data['customer_id']??$asset->customer_id),(int)($data['customer_site_id']??$asset->customer_site_id),$data['parent_asset_id']??$asset->parent_asset_id,$asset);
        unset($data['asset_code']); $asset->update($data); app(AssetAcceptanceContractService::class)->recalculateCriticality($asset->fresh()); app(AssetMaintenanceIntelligenceService::class)->recalculate($asset->fresh()); return $this->refreshCompleteness($asset);
    }

    public function verify(Asset $asset,int $userId,?string $notes=null): Asset
    {
        abort_unless($asset->verification_status==='PENDING' && $asset->lifecycle_status==='PENDING_VERIFICATION',422,'Only an asset pending verification can be verified.');
        $asset=$this->refreshCompleteness($asset); abort_if($asset->data_completeness_score<70,422,'Asset profile must be at least 70% complete before activation.'); abort_if(!$asset->customer_id || !$asset->customer_site_id,422,'Customer and site are required before activation.');
        $from=$asset->lifecycle_status; $asset->update(['verification_status'=>'VERIFIED','verified_by'=>$userId,'verified_at'=>now(),'verification_notes'=>$notes,'lifecycle_status'=>'ACTIVE','operational_status'=>$asset->operational_status==='STANDBY'?'ACTIVE':$asset->operational_status,'status'=>'ACTIVE']);
        $this->event($asset,'ASSET_VERIFIED',$from,'ACTIVE','Asset verified and activated',$notes,$userId); return $asset->refresh();
    }

    public function transition(Asset $asset,string $toStatus,int $userId,?string $notes=null): Asset
    {
        $toStatus=strtoupper($toStatus); $from=$asset->lifecycle_status; abort_if($from==='DISPOSED',422,'Disposed assets cannot transition.'); abort_unless(in_array($toStatus,self::TRANSITIONS[$from]??[],true),422,'Invalid asset lifecycle transition: '.$from.' → '.$toStatus);
        $changes=['lifecycle_status'=>$toStatus,'status'=>$toStatus==='ACTIVE'?'ACTIVE':$toStatus]; if($toStatus==='UNDER_MAINTENANCE')$changes['operational_status']='UNDER_MAINTENANCE'; if($toStatus==='OUT_OF_SERVICE')$changes['operational_status']='OUT_OF_SERVICE'; if($toStatus==='ACTIVE')$changes['operational_status']='ACTIVE'; if($toStatus==='DECOMMISSIONED')$changes['operational_status']='DECOMMISSIONED'; if($toStatus==='DISPOSED')$changes+=['operational_status'=>'DISPOSED','disposed_at'=>now()];
        $asset->update($changes); $this->event($asset,'STATUS_CHANGED',$from,$toStatus,'Asset status changed',$notes,$userId); return $asset->refresh();
    }

    public function requestCommissioning(Asset $asset,int $userId,array $data): AssetCommissioningRecord
    {
        abort_unless($asset->verification_status==='VERIFIED',422,'Asset must be verified before commissioning.'); abort_unless($asset->lifecycle_status==='ACTIVE',422,'Only an active asset can enter commissioning approval.'); abort_if($asset->commissioning_status==='COMMISSIONED',422,'Asset is already commissioned.'); abort_if(AssetCommissioningRecord::where('asset_id',$asset->id)->where('status','PENDING_APPROVAL')->exists(),422,'A commissioning review is already pending.');
        return DB::transaction(function() use($asset,$userId,$data){ $record=AssetCommissioningRecord::create(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'status'=>'PENDING_APPROVAL','inspection_date'=>$data['inspection_date']??now()->toDateString(),'inspection_result'=>$data['inspection_result']??'PASS','checklist'=>$data['checklist']??null,'notes'=>$data['notes']??null,'created_by'=>$userId]); $asset->update(['commissioning_status'=>'PENDING_APPROVAL','commissioning_requested_by'=>$userId,'commissioning_requested_at'=>now(),'commissioning_notes'=>$data['notes']??null]); $this->event($asset,'COMMISSIONING_REQUESTED',$asset->lifecycle_status,$asset->lifecycle_status,'Commissioning submitted for approval',$data['notes']??null,$userId,['commissioning_record_id'=>$record->id]); return $record; });
    }

    public function reviewCommissioning(Asset $asset,AssetCommissioningRecord $record,int $reviewerId,bool $approve,?string $notes=null): Asset
    {
        abort_unless((int)$record->asset_id===(int)$asset->id && $record->status==='PENDING_APPROVAL',422,'Commissioning review is not pending.'); abort_if((int)$record->created_by===$reviewerId,422,'Maker/checker control: commissioning requester cannot approve their own record.');
        if($approve){ abort_unless(in_array($record->inspection_result,['PASS','PASS_WITH_NOTES'],true),422,'A failed commissioning inspection cannot be approved.'); abort_unless($asset->verification_status==='VERIFIED' && $asset->lifecycle_status==='ACTIVE',422,'Asset lifecycle changed after commissioning request; approval requires a verified active asset.'); }
        $from=$asset->lifecycle_status;
        return DB::transaction(function() use($asset,$record,$reviewerId,$approve,$notes,$from){ $record->update(['status'=>$approve?'APPROVED':'REJECTED','approved_by'=>$reviewerId,'approved_at'=>now(),'notes'=>$notes ?: $record->notes]); if($approve){ $asset->update(['commissioning_status'=>'COMMISSIONED','commission_date'=>$record->inspection_date ?: now()->toDateString(),'commissioning_approved_by'=>$reviewerId,'commissioning_approved_at'=>now(),'commissioning_notes'=>$notes ?: $record->notes,'operational_status'=>'ACTIVE','lifecycle_status'=>'ACTIVE','status'=>'ACTIVE']); $this->event($asset,'COMMISSIONING_APPROVED',$from,'ACTIVE','Asset commissioned and operational',$notes,$reviewerId,['commissioning_record_id'=>$record->id]); } else { $asset->update(['commissioning_status'=>'REJECTED','commissioning_approved_by'=>$reviewerId,'commissioning_approved_at'=>now(),'commissioning_notes'=>$notes]); $this->event($asset,'COMMISSIONING_REJECTED',$from,$from,'Commissioning rejected',$notes,$reviewerId,['commissioning_record_id'=>$record->id]); } return $asset->refresh(); });
    }

    public function refreshCompleteness(Asset $asset): Asset
    {
        $checks=[$asset->customer_id,$asset->customer_site_id,$asset->name,$asset->asset_category,$asset->asset_type,$asset->criticality,$asset->manufacturer,$asset->model_no,$asset->serial_no,$asset->asset_location_id ?: ($asset->physical_location ?: $asset->location_code),$asset->installation_date,$asset->maintenance_strategy,$asset->ownership_type,$asset->technical_specifications]; $filled=count(array_filter($checks,fn($value)=>!is_null($value)&&$value!==''&&$value!==[])); $score=(int)round(($filled/count($checks))*100); if((int)$asset->data_completeness_score!==$score)$asset->update(['data_completeness_score'=>$score]); return $asset->refresh();
    }

    private function validateParent(int $tenantId,int $customerId,int $siteId,mixed $parentId,?Asset $asset=null): void
    {
        if(!$parentId)return; $parent=Asset::where('tenant_id',$tenantId)->find($parentId); abort_unless($parent,422,'Parent asset must belong to the same tenant.'); abort_unless((int)$parent->customer_id===$customerId && (int)$parent->customer_site_id===$siteId,422,'Parent asset must belong to the same customer and site.'); if(!$asset)return; abort_if((int)$parent->id===(int)$asset->id,422,'An asset cannot be its own parent.'); $cursor=$parent;$visited=[]; while($cursor){abort_if(isset($visited[$cursor->id]),422,'Asset hierarchy contains an existing cycle.');$visited[$cursor->id]=true;abort_if((int)$cursor->id===(int)$asset->id,422,'Asset hierarchy cannot contain a cycle.');$cursor=$cursor->parent_asset_id?Asset::where('tenant_id',$tenantId)->find($cursor->parent_asset_id):null;}
    }

    private function event(Asset $asset,string $type,?string $from,?string $to,string $title,?string $notes,?int $userId,array $metadata=[]): void
    { AssetLifecycleEvent::create(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'event_type'=>$type,'from_status'=>$from,'to_status'=>$to,'title'=>$title,'notes'=>$notes,'metadata'=>$metadata ?: null,'performed_by'=>$userId,'performed_at'=>now()]); }
}
