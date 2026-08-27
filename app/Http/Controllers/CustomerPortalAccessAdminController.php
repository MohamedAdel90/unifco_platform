<?php

namespace App\Http\Controllers;

use App\Models\{Asset,Customer,CustomerSite,ServiceContract,User};
use App\Services\CustomerPortalAccessService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerPortalAccessAdminController extends Controller
{
    private function admin(Request $request, CustomerPortalAccessService $access): User
    {
        $user=$request->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);
        abort_unless($access->role($user)==='CUSTOMER_ADMIN',403,'Customer Admin access is required.');
        return $user;
    }

    public function index(Request $request, CustomerPortalAccessService $access): View
    {
        $admin=$this->admin($request,$access);
        $customer=Customer::findOrFail($admin->customer_id);
        $users=User::where('role','CUSTOMER')->where('customer_id',$customer->id)->orderBy('name')->get();
        $sites=CustomerSite::where('customer_id',$customer->id)->where('status','ACTIVE')->orderBy('name')->get();
        $contracts=ServiceContract::where('customer_id',$customer->id)->orderByDesc('starts_on')->get();
        $assets=Asset::with('site')->where('customer_id',$customer->id)->orderBy('asset_code')->get();
        $scopes=DB::table('customer_portal_user_scopes')->whereIn('user_id',$users->pluck('id'))->get()->groupBy('user_id');

        return view('customer.users-access',compact('admin','customer','users','sites','contracts','assets','scopes'));
    }

    public function store(Request $request, CustomerPortalAccessService $access): RedirectResponse
    {
        $admin=$this->admin($request,$access);
        $data=$request->validate([
            'name'=>['required','string','max:180'],
            'email'=>['required','email','max:180','unique:users,email'],
            'customer_portal_role'=>['required',Rule::in(CustomerPortalAccessService::ROLES)],
            'password'=>['nullable','string','min:10','max:100'],
            'site_ids'=>['nullable','array'],'site_ids.*'=>['integer'],
            'contract_ids'=>['nullable','array'],'contract_ids.*'=>['integer'],
            'asset_ids'=>['nullable','array'],'asset_ids.*'=>['integer'],
        ]);

        $password=$data['password']?:'UnifcoCustomer!'.random_int(1000,9999);
        $user=DB::transaction(function() use($admin,$data,$password){
            $user=User::create([
                'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'customer_id'=>$admin->customer_id,
                'name'=>$data['name'],'email'=>$data['email'],'password'=>Hash::make($password),'role'=>'CUSTOMER',
                'customer_portal_role'=>$data['customer_portal_role'],'status'=>'ACTIVE','force_password_change'=>true,
            ]);
            $this->replaceScopes($user,$data);
            return $user;
        });

        return back()->with('status','Customer portal user created successfully.')
            ->with('temporary_password',$password)->with('created_user_email',$user->email);
    }

    public function update(Request $request, User $user, CustomerPortalAccessService $access): RedirectResponse
    {
        $admin=$this->admin($request,$access);
        abort_unless($user->role==='CUSTOMER' && (int)$user->customer_id===(int)$admin->customer_id,404);
        $data=$request->validate([
            'name'=>['required','string','max:180'],
            'customer_portal_role'=>['required',Rule::in(CustomerPortalAccessService::ROLES)],
            'status'=>['required',Rule::in(['ACTIVE','INACTIVE'])],
            'site_ids'=>['nullable','array'],'site_ids.*'=>['integer'],
            'contract_ids'=>['nullable','array'],'contract_ids.*'=>['integer'],
            'asset_ids'=>['nullable','array'],'asset_ids.*'=>['integer'],
        ]);

        if($user->is($admin) && ($data['customer_portal_role']!=='CUSTOMER_ADMIN' || $data['status']!=='ACTIVE')){
            return back()->withErrors(['user'=>'You cannot remove your own Customer Admin access or deactivate your own account.']);
        }

        DB::transaction(function() use($user,$data){
            $user->update(['name'=>$data['name'],'customer_portal_role'=>$data['customer_portal_role'],'status'=>$data['status']]);
            $this->replaceScopes($user,$data);
        });
        return back()->with('status','User access updated.');
    }

    public function resetPassword(Request $request, User $user, CustomerPortalAccessService $access): RedirectResponse
    {
        $admin=$this->admin($request,$access);
        abort_unless($user->role==='CUSTOMER' && (int)$user->customer_id===(int)$admin->customer_id,404);
        $data=$request->validate(['password'=>['nullable','string','min:10','max:100']]);
        $password=$data['password']?:'UnifcoCustomer!'.random_int(1000,9999);
        $user->update(['password'=>Hash::make($password),'force_password_change'=>true,'session_version'=>(int)$user->session_version+1]);
        return back()->with('status','Temporary password generated and active sessions revoked.')
            ->with('temporary_password',$password)->with('created_user_email',$user->email);
    }

    private function replaceScopes(User $user,array $data): void
    {
        DB::table('customer_portal_user_scopes')->where('user_id',$user->id)->delete();
        if(($data['customer_portal_role']??'')==='CUSTOMER_ADMIN') return;

        $validSites=CustomerSite::where('customer_id',$user->customer_id)->whereIn('id',$data['site_ids']??[])->pluck('id');
        $validContracts=ServiceContract::where('customer_id',$user->customer_id)->whereIn('id',$data['contract_ids']??[])->pluck('id');
        $validAssets=Asset::where('customer_id',$user->customer_id)->whereIn('id',$data['asset_ids']??[])->pluck('id');
        $rows=collect();
        foreach($validSites as $id)$rows->push(['user_id'=>$user->id,'scope_type'=>'SITE','scope_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        foreach($validContracts as $id)$rows->push(['user_id'=>$user->id,'scope_type'=>'CONTRACT','scope_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        foreach($validAssets as $id)$rows->push(['user_id'=>$user->id,'scope_type'=>'ASSET','scope_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        if($rows->isNotEmpty()) DB::table('customer_portal_user_scopes')->insert($rows->all());
    }
}
