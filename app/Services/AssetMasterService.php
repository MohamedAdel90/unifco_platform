<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Str;

class AssetMasterService
{
    public const LIFECYCLE=['DRAFT','PENDING_VERIFICATION','ACTIVE','UNDER_MAINTENANCE','OUT_OF_SERVICE','DECOMMISSIONED','DISPOSED'];
    public const CRITICALITY=['LOW','MEDIUM','HIGH','CRITICAL'];
    public const OWNERSHIP=['CUSTOMER_OWNED','UNIFCO_MANAGED','RENTAL','THIRD_PARTY'];
    public const STRATEGIES=['PREVENTIVE','PREDICTIVE','CONDITION_BASED','CORRECTIVE','RUN_TO_FAILURE'];

    public function findStrongDuplicate(int $tenantId,?int $customerId,array $data,?int $exceptId=null): ?Asset
    {
        $base=Asset::where('tenant_id',$tenantId);
        if($customerId) $base->where('customer_id',$customerId);
        if($exceptId) $base->whereKeyNot($exceptId);

        if($serial=trim((string)($data['serial_no']??''))){
            if($asset=(clone $base)->whereRaw('LOWER(serial_no)=?',[mb_strtolower($serial)])->first()) return $asset;
        }
        if($code=trim((string)($data['customer_asset_code']??''))){
            if($asset=(clone $base)->whereRaw('LOWER(customer_asset_code)=?',[mb_strtolower($code)])->first()) return $asset;
        }
        return null;
    }

    public function create(int $tenantId,int $organizationId,int $userId,array $data): Asset
    {
        if($duplicate=$this->findStrongDuplicate($tenantId,$data['customer_id']??null,$data)){
            abort(422,'Possible duplicate asset: '.$duplicate->asset_code.' · '.$duplicate->name);
        }
        $next=((int)Asset::where('tenant_id',$tenantId)->max('id'))+1;
        $asset=Asset::create([
            ...$data,
            'tenant_id'=>$tenantId,'organization_id'=>$organizationId,
            'asset_code'=>$data['asset_code']??('AST-'.str_pad((string)$next,6,'0',STR_PAD_LEFT)),
            'qr_token'=>$data['qr_token']??Str::random(40),
            'lifecycle_status'=>'PENDING_VERIFICATION','operational_status'=>$data['operational_status']??'STANDBY',
            'verification_status'=>'PENDING','status'=>'REGISTERED',
        ]);
        return $this->refreshCompleteness($asset);
    }

    public function update(Asset $asset,array $data): Asset
    {
        if($duplicate=$this->findStrongDuplicate((int)$asset->tenant_id,$data['customer_id']??$asset->customer_id,$data,$asset->id)){
            abort(422,'Possible duplicate asset: '.$duplicate->asset_code.' · '.$duplicate->name);
        }
        unset($data['asset_code']); // immutable internal identity
        $asset->update($data);
        return $this->refreshCompleteness($asset);
    }

    public function verify(Asset $asset,int $userId,?string $notes=null): Asset
    {
        $asset=$this->refreshCompleteness($asset);
        abort_if($asset->data_completeness_score<70,422,'Asset profile must be at least 70% complete before activation.');
        abort_if(!$asset->customer_id || !$asset->customer_site_id,422,'Customer and site are required before activation.');
        $asset->update([
            'verification_status'=>'VERIFIED','verified_by'=>$userId,'verified_at'=>now(),'verification_notes'=>$notes,
            'lifecycle_status'=>'ACTIVE','operational_status'=>$asset->operational_status==='STANDBY'?'ACTIVE':$asset->operational_status,'status'=>'ACTIVE',
        ]);
        return $asset->refresh();
    }

    public function refreshCompleteness(Asset $asset): Asset
    {
        $checks=[
            $asset->customer_id,$asset->customer_site_id,$asset->name,$asset->asset_category,$asset->asset_type,$asset->criticality,
            $asset->manufacturer,$asset->model_no,$asset->serial_no,$asset->physical_location ?: $asset->location_code,
            $asset->installation_date,$asset->maintenance_strategy,$asset->ownership_type,$asset->technical_specifications,
        ];
        $filled=count(array_filter($checks,fn($value)=>!is_null($value) && $value!=='' && $value!==[]));
        $score=(int)round(($filled/count($checks))*100);
        if((int)$asset->data_completeness_score!==$score) $asset->update(['data_completeness_score'=>$score]);
        return $asset->refresh();
    }
}
