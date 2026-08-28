<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\{Asset,AssetCoverage,AssetCoverageClaim,User};
use App\Services\AssetWarrantyInsuranceService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetWarrantyInsuranceController extends Controller
{
    private const ROLES=['ADMIN','MAINTENANCE_MANAGER','MAINTENANCE_ENGINEER','PROJECT_MANAGER'];
    private const CHECKERS=['ADMIN','MAINTENANCE_MANAGER'];

    private function asset(Request $request, Asset $asset): User
    {
        $user=$request->user();
        abort_unless($user && in_array($user->role,self::ROLES,true),403);
        abort_unless((int)$asset->tenant_id === (int)$user->tenant_id,404);
        return $user;
    }

    private function checker(Request $request, Asset $asset): User
    {
        $user=$this->asset($request,$asset);
        abort_unless(in_array($user->role,self::CHECKERS,true),403);
        return $user;
    }

    public function show(Request $request, Asset $asset): View
    {
        $user=$this->asset($request,$asset);
        $coverages=AssetCoverage::with('claims')->where('asset_id',$asset->id)->orderByDesc('expires_at')->get();
        return view('maintenance.asset-master.warranty-insurance',[
            'asset'=>$asset,'coverages'=>$coverages,
            'expiringSoon'=>$coverages->filter(fn($c)=>$c->status==='ACTIVE' && $c->expiresSoon()),
            'expired'=>$coverages->filter(fn($c)=>$c->status==='ACTIVE' && $c->isExpired()),
            'canApprove'=>in_array($user->role,self::CHECKERS,true),
        ]);
    }

    public function store(Request $request, Asset $asset, AssetWarrantyInsuranceService $service): RedirectResponse
    {
        $user=$this->asset($request,$asset);
        $data=$request->validate([
            'coverage_type'=>['required',Rule::in(['WARRANTY','INSURANCE'])],'provider'=>['required','string','max:180'],
            'reference_no'=>['nullable','string','max:120'],'starts_at'=>['required','date'],'expires_at'=>['required','date'],
            'coverage_amount'=>['nullable','numeric','min:0'],'currency'=>['nullable','string','size:3'],'scope'=>['nullable','string','max:5000'],
        ]);
        $service->createCoverage($asset,(int)$user->id,$data);
        return back()->with('status','Asset coverage recorded.');
    }

    public function renew(Request $request, Asset $asset, AssetCoverage $coverage, AssetWarrantyInsuranceService $service): RedirectResponse
    {
        $user=$this->asset($request,$asset);
        abort_unless((int)$coverage->tenant_id === (int)$user->tenant_id,404);
        $data=$request->validate([
            'provider'=>['nullable','string','max:180'],'reference_no'=>['nullable','string','max:120'],'starts_at'=>['required','date'],'expires_at'=>['required','date'],
            'coverage_amount'=>['nullable','numeric','min:0'],'currency'=>['nullable','string','size:3'],'scope'=>['nullable','string','max:5000'],
        ]);
        $service->renew($asset,$coverage,(int)$user->id,$data);
        return back()->with('status','Asset coverage renewed.');
    }

    public function submitClaim(Request $request, Asset $asset, AssetCoverage $coverage, AssetWarrantyInsuranceService $service): RedirectResponse
    {
        $user=$this->asset($request,$asset);
        abort_unless((int)$coverage->tenant_id === (int)$user->tenant_id,404);
        $data=$request->validate(['claim_no'=>['nullable','string','max:120'],'claim_date'=>['required','date'],'claimed_amount'=>['nullable','numeric','min:0'],'description'=>['required','string','max:5000']]);
        $service->submitClaim($asset,$coverage,(int)$user->id,$data);
        return back()->with('status','Coverage claim submitted for review.');
    }

    public function reviewClaim(Request $request, Asset $asset, AssetCoverageClaim $claim, AssetWarrantyInsuranceService $service): RedirectResponse
    {
        $user=$this->checker($request,$asset);
        abort_unless((int)$claim->tenant_id === (int)$user->tenant_id,404);
        $data=$request->validate(['decision'=>['required',Rule::in(['APPROVE','REJECT'])],'approved_amount'=>['nullable','numeric','min:0'],'resolution_notes'=>['nullable','string','max:5000']]);
        $service->reviewClaim($asset,$claim,(int)$user->id,$data['decision'],$data);
        return back()->with('status','Coverage claim review completed.');
    }
}
