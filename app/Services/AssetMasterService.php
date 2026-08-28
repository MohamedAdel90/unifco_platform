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
        if(!$customerId || empty($data['customer_site_id'])) return null;
        $base=Asset::where('tenant_id',$tenantId)->where('customer_id',$customerId)->where('customer_site_id',$data['customer_site_id']);
        if($exceptId)$base->whereKeyNot($exceptId);
        $serial=mb_strtolower(trim((string)($data['serial_no']??''))); $manufacturer=mb_strtolower(trim((string)($data['manufacturer']??''))); $model=mb_strtolower(trim((string)($data['model_no']??''))); $code=mb_strtolower(trim((string)($data['customer_asset_code']??'')));
        return (clone $base)->where(function($q) use($serial,$manufacturer,$model,$code){
            if($code!=='')$q->orWhereRaw('LOWER(customer_asset_code)=?',[$code]);
            if($serial!=='')$q->orWhere(function($x) use($serial,$manufacturer,$model){$x->whereRaw('LOWER(serial_no)=?',[$serial]);if($manufacturer!=='')$x->whereRaw('LOWER(manufacturer)=?',[$manufacturer]);if($model!=='')$x->whereRaw('LOWER(model_no)=?',[$model]);});
        })->first();
    }

    public function create(int $tenantId,int $organizationId,int $userId,array $data): Asset
    {
        if($duplicate=$this->findStrongDuplicate($tenantId,$data['customer_id']??null,$data))abort(422,'Possible Duplicate Asset: '.$duplicate->asset_code.' · '.$duplicate->name);
        $this->validateParent($tenantId,(int)($data['customer_id']??0),(int)($data['customer_site_id']??0),$data['parent_asset_id']??null);
        return DB::transaction(function() use($tenantId,$organizationId,$userId,$data){
            $temporary='AST-TMP-'.Str::upper(Str::random(12));
            $asset=Asset::create([...$data,'tenant_id'=>$tenantId,'organization_id'=>$organizationId,'asset_code'=>$data['asset_code']??$temporary,'qr_token'=>$data['qr_token']??Str::random(40),'lifecycle_status'=>'PENDING_VERIFICATION','operational_status'=>$data['operational_status']??'STANDBY','verification_status'=>'PENDING','commissioning_status'=>'NOT_STARTED','status'=>'REGISTERED']);
            if(empty($data['asset_code']))$asset->update(['asset_code'=>'AST-UNF-'.str_pad((string)$asset->id,6,'0',STR_PAD_LEFT)]);
            $this->event($asset,'ASSET_CREATED',null,'PENDING_VERIFICATION','Asset created','Creation is separated from approval; asset awaits independent verification.',$userId);
            $this->event($asset,'QR_GENERATED','PENDING_VERIFICATION','PENDING_VERIFICATION','QR generated','Unique field QR identity generated.',$userId,['qr_token'=>$asset->qr_token]);
            if($asset->installation_date)$this->event($asset,'ASSET_INSTALLED','PENDING_VERIFICATION','PENDING_VERIFICATION','Asset installed','Installation date '.$asset->installation_date->toDateString(),$userId);
            return $this->refreshCompleteness($asset);
        });
    }

    public function update(Asset $asset,array $data): Asset
    {
        $probe=[...$asset->only(['customer_id','customer_site_id','serial_no','manufacturer','model_no','customer_asset_code']),...$data];
        if($duplicate=$this->findStrongDuplicate((int)$asset->tenant_id,$probe['customer_id']??$asset->customer_id,$probe,$asset->id))abort(422,'Possible Duplicate Asset: '.$duplicate->asset_code.' · '.$duplicate->name);
        $this->validateParent((int)$asset->tenant_id,(int)($data['customer_id']??$asset->customer_id),(int)($data['customer_site_id']??$asset->customer_site_id),$data['parent_asset_id']??$asset->parent_asset_id,$asset);
        unset($data['asset_code']); $asset->update($data); app(AssetAcceptanceContractService::class)->recalculateCriticality($asset->fresh()); app(AssetMaintenanceIntelligenceService::class)->recalculate($asset->fresh()); return $this->refreshCompleteness($asset);
    }

    public function profileMissingFields(Asset $asset): array
    {
        $hasNameplate=DB::table('asset_documents')->where('asset_id',$asset->id)->where('document_type','NAMEPLATE_PHOTO')->exists();
        $fields=[
            'Customer'=>$asset->customer_id,'Site'=>$asset->customer_site_id,'Category'=>$asset->asset_category,'Asset Name'=>$asset->name,
            'Asset Type'=>$asset->asset_type,'Manufacturer'=>$asset->manufacturer,'Model'=>$asset->model_no,'Serial Number'=>$asset->serial_no,
            'Warranty Date'=>$asset->warranty_expiry,'Nameplate Photo'=>$hasNameplate,'PM Strategy'=>$asset->maintenance_strategy,'Ownership'=>$asset->ownership_type,
            'Physical Location'=>$asset->asset_location_id ?: ($asset->physical_location ?: $asset->location_code),'Installation Date'=>$asset->installation_date,
            'Criticality'=>$asset->criticality,'Technical Specifications'=>$asset->technical_specifications,'QR Identity'=>$asset->qr_token,
        ];
        return array_keys(array_filter($fields,fn($v)=>is_null($v)||$v===''||$v===[]||$v===false));
    }

    public function minimumVerificationMissing(Asset $asset): array
    {
        $required=['Customer'=>$asset->customer_id,'Site'=>$asset->customer_site_id,'Category'=>$asset->asset_category,'Asset Name'=>$asset->name,'Serial Number'=>$asset->serial_no,'PM Strategy'=>$asset->maintenance_strategy];
        return array_keys(array_filter($required,fn($v)=>is_null($v)||$v===''));
    }

    public function verify(Asset $asset,int $userId,?string $notes=null): Asset
    {
        abort_unless($asset->verification_status==='PENDING' && $asset->lifecycle_status==='PENDING_VERIFICATION',422,'Only an asset pending verification can be verified.');
        $creatorId=(int)(AssetLifecycleEvent::where('asset_id',$asset->id)->where('event_type','ASSET_CREATED')->orderBy('id')->value('performed_by') ?? 0);
        abort_if($creatorId>0 && $creatorId===$userId,422,'Maker/checker control: the user who created this asset cannot Verify & Activate it. Independent verification is required.');
        $asset=$this->refreshCompleteness($asset); $missing=$this->minimumVerificationMissing($asset); abort_if($missing,422,'Minimum Verification Data missing: '.implode(', ',$missing));
        abort_if($asset->data_completeness_score<70,422,'Asset profile must be at least 70% complete before activation.');
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
        return DB::transaction(function() use($asset,$userId,$data){$record=AssetCommissioningRecord::create(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'status'=>'PENDING_APPROVAL','inspection_date'=>$data['inspection_date']??now()->toDateString(),'inspection_result'=>$data['inspection_result']??'PASS','checklist'=>$data['checklist']??null,'notes'=>$data['notes']??null,'created_by'=>$userId]);$asset->update(['commissioning_status'=>'PENDING_APPROVAL','commissioning_requested_by'=>$userId,'commissioning_requested_at'=>now(),'commissioning_notes'=>$data['notes']??null]);$this->event($asset,'COMMISSIONING_REQUESTED',$asset->lifecycle_status,$asset->lifecycle_status,'Commissioning submitted for approval',$data['notes']??null,$userId,['commissioning_record_id'=>$record->id]);return $record;});
    }

    public function reviewCommissioning(Asset $asset,AssetCommissioningRecord $record,int $reviewerId,bool $approve,?string $notes=null): Asset
    {
        abort_unless((int)$record->asset_id===(int)$asset->id && $record->status==='PENDING_APPROVAL',422,'Commissioning review is not pending.'); abort_if((int)$record->created_by===$reviewerId,422,'Maker/checker control: commissioning requester cannot approve their own record.');
        if($approve){abort_unless(in_array($record->inspection_result,['PASS','PASS_WITH_NOTES'],true),422,'A failed commissioning inspection cannot be approved.');abort_unless($asset->verification_status==='VERIFIED' && $asset->lifecycle_status==='ACTIVE',422,'Asset lifecycle changed after commissioning request; approval requires a verified active asset.');}
        $from=$asset->lifecycle_status;
        return DB::transaction(function() use($asset,$record,$reviewerId,$approve,$notes,$from){$record->update(['status'=>$approve?'APPROVED':'REJECTED','approved_by'=>$reviewerId,'approved_at'=>now(),'notes'=>$notes ?: $record->notes]);if($approve){$asset->update(['commissioning_status'=>'COMMISSIONED','commission_date'=>$record->inspection_date ?: now()->toDateString(),'commissioning_approved_by'=>$reviewerId,'commissioning_approved_at'=>now(),'commissioning_notes'=>$notes ?: $record->notes,'operational_status'=>'ACTIVE','lifecycle_status'=>'ACTIVE','status'=>'ACTIVE']);$this->event($asset,'COMMISSIONING_APPROVED',$from,'ACTIVE','Asset commissioned and operational',$notes,$reviewerId,['commissioning_record_id'=>$record->id]);}else{$asset->update(['commissioning_status'=>'REJECTED','commissioning_approved_by'=>$reviewerId,'commissioning_approved_at'=>now(),'commissioning_notes'=>$notes]);$this->event($asset,'COMMISSIONING_REJECTED',$from,$from,'Commissioning rejected',$notes,$reviewerId,['commissioning_record_id'=>$record->id]);}return $asset->refresh();});
    }

    public function refreshCompleteness(Asset $asset): Asset
    {
        $missing=$this->profileMissingFields($asset); $score=(int)round(((17-count($missing))/17)*100); if((int)$asset->data_completeness_score!==$score)$asset->update(['data_completeness_score'=>$score]); return $asset->refresh();
    }

    private function validateParent(int $tenantId,int $customerId,int $siteId,mixed $parentId,?Asset $asset=null): void
    {
        if(!$parentId)return; $parent=Asset::where('tenant_id',$tenantId)->find($parentId); abort_unless($parent,422,'Parent asset must belong to the same tenant.'); abort_unless((int)$parent->customer_id===$customerId && (int)$parent->customer_site_id===$siteId,422,'Parent asset must belong to the same customer and site.'); if(!$asset)return; abort_if((int)$parent->id===(int)$asset->id,422,'An asset cannot be its own parent.'); $cursor=$parent;$visited=[];while($cursor){abort_if(isset($visited[$cursor->id]),422,'Asset hierarchy contains an existing cycle.');$visited[$cursor->id]=true;abort_if((int)$cursor->id===(int)$asset->id,422,'Asset hierarchy cannot contain a cycle.');$cursor=$cursor->parent_asset_id?Asset::where('tenant_id',$tenantId)->find($cursor->parent_asset_id):null;}
    }

    private function event(Asset $asset,string $type,?string $from,?string $to,string $title,?string $notes,?int $userId,array $metadata=[]): void
    {AssetLifecycleEvent::create(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'event_type'=>$type,'from_status'=>$from,'to_status'=>$to,'title'=>$title,'notes'=>$notes,'metadata'=>$metadata ?: null,'performed_by'=>$userId,'performed_at'=>now()]);}
}
