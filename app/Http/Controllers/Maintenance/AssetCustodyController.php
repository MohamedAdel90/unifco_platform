<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\{Asset,AssetCustody,AssetTransfer,User};
use App\Services\AssetCustodyService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetCustodyController extends Controller
{
    private const ROLES=['ADMIN','MAINTENANCE_MANAGER','MAINTENANCE_ENGINEER','PROJECT_MANAGER'];
    private const CHECKERS=['ADMIN','MAINTENANCE_MANAGER'];

    private function actor(Request $request): User
    {
        $user=$request->user(); abort_unless($user && in_array($user->role,self::ROLES,true),403); return $user;
    }
    private function checker(Request $request): User
    {
        $user=$this->actor($request); abort_unless(in_array($user->role,self::CHECKERS,true),403); return $user;
    }
    private function asset(Request $request,Asset $asset): User
    {
        $user=$this->actor($request); abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404); return $user;
    }

    public function show(Request $request,Asset $asset): View
    {
        $user=$this->asset($request,$asset);
        return view('maintenance.asset-master.custody',[
            'asset'=>$asset,'custodies'=>AssetCustody::where('asset_id',$asset->id)->latest()->get(),
            'transfers'=>AssetTransfer::where('asset_id',$asset->id)->latest()->get(),
            'users'=>User::where('tenant_id',$user->tenant_id)->where('status','ACTIVE')->orderBy('name')->get(),
            'canApprove'=>in_array($user->role,self::CHECKERS,true),
        ]);
    }

    public function assign(Request $request,Asset $asset,AssetCustodyService $service): RedirectResponse
    {
        $user=$this->asset($request,$asset); $service->assign($asset,$user->id,$this->target($request)); return back()->with('status','Asset custody assigned.');
    }

    public function return(Request $request,Asset $asset,AssetCustody $custody,AssetCustodyService $service): RedirectResponse
    {
        $user=$this->asset($request,$asset); abort_unless((int)$custody->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['notes'=>['nullable','string','max:3000']]); $service->return($asset,$custody,$user->id,$data['notes']??null); return back()->with('status','Asset custody returned.');
    }

    public function requestTransfer(Request $request,Asset $asset,AssetCustodyService $service): RedirectResponse
    {
        $user=$this->asset($request,$asset); $data=$this->target($request,true); $service->requestTransfer($asset,$user->id,$data); return back()->with('status','Transfer submitted for independent approval.');
    }

    public function review(Request $request,Asset $asset,AssetTransfer $transfer,AssetCustodyService $service): RedirectResponse
    {
        $user=$this->checker($request); abort_unless((int)$asset->tenant_id===(int)$user->tenant_id && (int)$transfer->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['decision'=>['required',Rule::in(['APPROVE','REJECT'])],'notes'=>['nullable','string','max:3000']]);
        $service->reviewTransfer($asset,$transfer,$user->id,$data['decision']==='APPROVE',$data['notes']??null); return back()->with('status','Transfer review completed.');
    }

    private function target(Request $request,bool $transfer=false): array
    {
        $rules=[
            'custodian_user_id'=>['nullable','integer'],'custodian_name'=>['nullable','string','max:160'],'department'=>['nullable','string','max:160'],'branch'=>['nullable','string','max:160'],'notes'=>['nullable','string','max:3000'],
        ];
        if($transfer){ $rules['reason']=['required','string','max:3000']; $rules['to_customer_site_id']=['nullable','integer']; }
        return $request->validate($rules);
    }
}
