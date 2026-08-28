<?php

namespace App\Services;

use App\Models\{Asset,AssetCustody,AssetLifecycleEvent,AssetTransfer,CustomerSite,User};
use Illuminate\Support\Facades\DB;

class AssetCustodyService
{
    public function assign(Asset $asset,int $actorId,array $data): AssetCustody
    {
        abort_unless($asset->verification_status==='VERIFIED' && $asset->lifecycle_status==='ACTIVE',422,'Custody can only be assigned to a verified active asset.');
        abort_if(AssetCustody::where('asset_id',$asset->id)->where('status','ACTIVE')->exists(),422,'Asset already has active custody.');
        $this->validateTarget($asset,$data);
        return DB::transaction(function() use($asset,$actorId,$data){
            $custody=AssetCustody::create([
                'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,
                'custodian_user_id'=>$data['custodian_user_id']??null,'custodian_name'=>$data['custodian_name']??null,
                'department'=>$data['department']??null,'branch'=>$data['branch']??null,'status'=>'ACTIVE','assigned_at'=>now(),
                'notes'=>$data['notes']??null,'assigned_by'=>$actorId,
            ]);
            $this->event($asset,'CUSTODY_ASSIGNED','Asset custody assigned',$data['notes']??null,$actorId,['custody_id'=>$custody->id]);
            return $custody;
        });
    }

    public function return(Asset $asset,AssetCustody $custody,int $actorId,?string $notes=null): AssetCustody
    {
        abort_unless((int)$custody->asset_id===(int)$asset->id && $custody->status==='ACTIVE',422,'Only active custody for this asset can be returned.');
        return DB::transaction(function() use($asset,$custody,$actorId,$notes){
            $custody->update(['status'=>'RETURNED','returned_at'=>now(),'returned_by'=>$actorId,'notes'=>$notes ?: $custody->notes]);
            $this->event($asset,'CUSTODY_RETURNED','Asset custody returned',$notes,$actorId,['custody_id'=>$custody->id]);
            return $custody->refresh();
        });
    }

    public function requestTransfer(Asset $asset,int $actorId,array $data): AssetTransfer
    {
        abort_unless($asset->verification_status==='VERIFIED' && $asset->lifecycle_status==='ACTIVE',422,'Only a verified active asset can be transferred.');
        abort_if(AssetTransfer::where('asset_id',$asset->id)->where('status','PENDING_APPROVAL')->exists(),422,'An asset transfer is already pending approval.');
        $this->validateTarget($asset,$data);
        if(!empty($data['to_customer_site_id'])){
            $site=CustomerSite::with('customer')->find($data['to_customer_site_id']);
            abort_unless($site && $site->customer && (int)$site->customer->tenant_id===(int)$asset->tenant_id && (int)$site->customer_id===(int)$asset->customer_id,422,'Transfer site must belong to the same tenant and customer.');
        }
        $active=AssetCustody::where('asset_id',$asset->id)->where('status','ACTIVE')->first();
        $transfer=AssetTransfer::create([
            'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'from_custody_id'=>$active?->id,
            'to_custodian_user_id'=>$data['custodian_user_id']??null,'to_custodian_name'=>$data['custodian_name']??null,'to_department'=>$data['department']??null,'to_branch'=>$data['branch']??null,
            'to_customer_site_id'=>$data['to_customer_site_id']??null,'status'=>'PENDING_APPROVAL','reason'=>$data['reason'],'request_notes'=>$data['notes']??null,'requested_by'=>$actorId,'requested_at'=>now(),
        ]);
        $this->event($asset,'TRANSFER_REQUESTED','Asset transfer submitted for approval',$data['reason'],$actorId,['transfer_id'=>$transfer->id]);
        return $transfer;
    }

    public function reviewTransfer(Asset $asset,AssetTransfer $transfer,int $reviewerId,bool $approve,?string $notes=null): AssetTransfer
    {
        abort_unless((int)$transfer->asset_id===(int)$asset->id && $transfer->status==='PENDING_APPROVAL',422,'Transfer is not pending approval.');
        abort_if((int)$transfer->requested_by===$reviewerId,422,'Maker/checker control: transfer requester cannot approve their own transfer.');
        if($approve) abort_unless($asset->verification_status==='VERIFIED' && $asset->lifecycle_status==='ACTIVE',422,'Asset is no longer eligible for transfer.');
        return DB::transaction(function() use($asset,$transfer,$reviewerId,$approve,$notes){
            if(!$approve){
                $transfer->update(['status'=>'REJECTED','reviewed_by'=>$reviewerId,'reviewed_at'=>now(),'review_notes'=>$notes]);
                $this->event($asset,'TRANSFER_REJECTED','Asset transfer rejected',$notes,$reviewerId,['transfer_id'=>$transfer->id]);
                return $transfer->refresh();
            }
            $active=AssetCustody::where('asset_id',$asset->id)->where('status','ACTIVE')->lockForUpdate()->first();
            if($active) $active->update(['status'=>'TRANSFERRED','returned_at'=>now(),'returned_by'=>$reviewerId]);
            if($transfer->to_customer_site_id && (int)$transfer->to_customer_site_id!==(int)$asset->customer_site_id) $asset->update(['customer_site_id'=>$transfer->to_customer_site_id,'asset_location_id'=>null]);
            AssetCustody::create([
                'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'custodian_user_id'=>$transfer->to_custodian_user_id,
                'custodian_name'=>$transfer->to_custodian_name,'department'=>$transfer->to_department,'branch'=>$transfer->to_branch,'status'=>'ACTIVE','assigned_at'=>now(),'notes'=>$notes,'assigned_by'=>$reviewerId,
            ]);
            $transfer->update(['status'=>'APPROVED','reviewed_by'=>$reviewerId,'reviewed_at'=>now(),'review_notes'=>$notes,'completed_at'=>now()]);
            $this->event($asset,'ASSET_TRANSFERRED','Asset custody / location transfer completed',$notes,$reviewerId,['transfer_id'=>$transfer->id]);
            return $transfer->refresh();
        });
    }

    private function validateTarget(Asset $asset,array $data): void
    {
        abort_if(empty($data['custodian_user_id']) && empty(trim((string)($data['custodian_name']??''))) && empty(trim((string)($data['department']??''))),422,'Custodian user, custodian name, or department is required.');
        if(!empty($data['custodian_user_id'])){
            $user=User::find($data['custodian_user_id']);
            abort_unless($user && (int)$user->tenant_id===(int)$asset->tenant_id,422,'Custodian must belong to the same tenant.');
        }
    }

    private function event(Asset $asset,string $type,string $title,?string $notes,int $actorId,array $metadata): void
    {
        AssetLifecycleEvent::create(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'event_type'=>$type,'from_status'=>$asset->lifecycle_status,'to_status'=>$asset->lifecycle_status,'title'=>$title,'notes'=>$notes,'metadata'=>$metadata,'performed_by'=>$actorId,'performed_at'=>now()]);
    }
}
