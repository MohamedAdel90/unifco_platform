<?php

namespace App\Services;

use App\Models\{Asset,AssetCoverage,AssetCoverageClaim,AssetLifecycleEvent};
use Illuminate\Support\Facades\DB;

class AssetWarrantyInsuranceService
{
    public function createCoverage(Asset $asset, int $actorId, array $data): AssetCoverage
    {
        abort_unless($asset->verification_status === 'VERIFIED' && !in_array($asset->lifecycle_status, ['DISPOSED','DECOMMISSIONED'], true), 422, 'Coverage can only be recorded for a verified non-retired asset.');
        abort_if($data['expires_at'] < $data['starts_at'], 422, 'Coverage expiry must be on or after the start date.');

        return DB::transaction(function () use ($asset, $actorId, $data) {
            $coverage = AssetCoverage::create([
                'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,
                'coverage_type'=>$data['coverage_type'],'provider'=>$data['provider'],'reference_no'=>$data['reference_no']??null,
                'starts_at'=>$data['starts_at'],'expires_at'=>$data['expires_at'],'coverage_amount'=>$data['coverage_amount']??null,
                'currency'=>$data['currency']??null,'scope'=>$data['scope']??null,'status'=>'ACTIVE','created_by'=>$actorId,
            ]);
            $this->event($asset, $data['coverage_type'].'_COVERAGE_ADDED', $data['coverage_type'].' coverage added', $data['scope']??null, $actorId, ['coverage_id'=>$coverage->id]);
            return $coverage;
        });
    }

    public function renew(Asset $asset, AssetCoverage $coverage, int $actorId, array $data): AssetCoverage
    {
        abort_unless((int)$coverage->asset_id === (int)$asset->id && $coverage->status === 'ACTIVE', 422, 'Only an active coverage for this asset can be renewed.');
        abort_if($data['expires_at'] < $data['starts_at'], 422, 'Coverage expiry must be on or after the start date.');
        abort_if($data['starts_at'] <= $coverage->starts_at->toDateString(), 422, 'Renewal must start after the original coverage start date.');

        return DB::transaction(function () use ($asset, $coverage, $actorId, $data) {
            $coverage->update(['status'=>'RENEWED']);
            $renewed = AssetCoverage::create([
                'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,
                'coverage_type'=>$coverage->coverage_type,'provider'=>$data['provider']??$coverage->provider,
                'reference_no'=>$data['reference_no']??$coverage->reference_no,'starts_at'=>$data['starts_at'],'expires_at'=>$data['expires_at'],
                'coverage_amount'=>$data['coverage_amount']??$coverage->coverage_amount,'currency'=>$data['currency']??$coverage->currency,
                'scope'=>$data['scope']??$coverage->scope,'status'=>'ACTIVE','renewed_from_id'=>$coverage->id,'created_by'=>$actorId,
            ]);
            $this->event($asset, $coverage->coverage_type.'_COVERAGE_RENEWED', $coverage->coverage_type.' coverage renewed', $data['scope']??null, $actorId, ['coverage_id'=>$renewed->id,'renewed_from_id'=>$coverage->id]);
            return $renewed;
        });
    }

    public function submitClaim(Asset $asset, AssetCoverage $coverage, int $actorId, array $data): AssetCoverageClaim
    {
        abort_unless((int)$coverage->asset_id === (int)$asset->id && $coverage->status === 'ACTIVE', 422, 'Claim coverage is not active for this asset.');
        abort_if($coverage->expires_at->isBefore(today()), 422, 'Cannot submit a claim against expired coverage.');
        if (!empty($data['claimed_amount']) && $coverage->coverage_amount !== null) {
            abort_if((float)$data['claimed_amount'] > (float)$coverage->coverage_amount, 422, 'Claim amount exceeds coverage amount.');
        }

        $claim = AssetCoverageClaim::create([
            'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'asset_coverage_id'=>$coverage->id,
            'claim_no'=>$data['claim_no']??null,'claim_date'=>$data['claim_date'],'claimed_amount'=>$data['claimed_amount']??null,
            'status'=>'SUBMITTED','description'=>$data['description'],'submitted_by'=>$actorId,
        ]);
        $this->event($asset, 'COVERAGE_CLAIM_SUBMITTED', 'Warranty / insurance claim submitted', $data['description'], $actorId, ['coverage_id'=>$coverage->id,'claim_id'=>$claim->id]);
        return $claim;
    }

    public function reviewClaim(Asset $asset, AssetCoverageClaim $claim, int $reviewerId, string $decision, array $data): AssetCoverageClaim
    {
        abort_unless((int)$claim->asset_id === (int)$asset->id && $claim->status === 'SUBMITTED', 422, 'Claim is not pending review.');
        abort_if((int)$claim->submitted_by === $reviewerId, 422, 'Maker/checker control: claim submitter cannot review their own claim.');
        if ($decision === 'APPROVE' && isset($data['approved_amount'])) {
            abort_if((float)$data['approved_amount'] > (float)($claim->claimed_amount ?? 0), 422, 'Approved amount cannot exceed claimed amount.');
        }
        $status = $decision === 'APPROVE' ? 'APPROVED' : 'REJECTED';
        $claim->update(['status'=>$status,'approved_amount'=>$decision === 'APPROVE' ? ($data['approved_amount']??$claim->claimed_amount) : null,'resolution_notes'=>$data['resolution_notes']??null,'reviewed_by'=>$reviewerId,'reviewed_at'=>now()]);
        $this->event($asset, 'COVERAGE_CLAIM_'.$status, 'Warranty / insurance claim '.strtolower($status), $data['resolution_notes']??null, $reviewerId, ['claim_id'=>$claim->id]);
        return $claim->refresh();
    }

    private function event(Asset $asset, string $type, string $title, ?string $notes, int $actorId, array $metadata): void
    {
        AssetLifecycleEvent::create(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'event_type'=>$type,'from_status'=>$asset->lifecycle_status,'to_status'=>$asset->lifecycle_status,'title'=>$title,'notes'=>$notes,'metadata'=>$metadata,'performed_by'=>$actorId,'performed_at'=>now()]);
    }
}
