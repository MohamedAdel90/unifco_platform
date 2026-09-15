<?php

namespace App\Http\Controllers;

use App\Models\{AccessScope,Asset,Customer,CustomerSite,Role,ServiceContract,User};
use App\Services\{AuditService,CustomerPortalAccessService,InvitationService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerPortalAccessAdminController extends Controller
{
    private function admin(Request $request, CustomerPortalAccessService $access): User
    {
        $user=$request->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);
        abort_unless($access->canManageUsers($user),403,'UNIFCO Customer Portal uses one login per customer. Additional portal users cannot be created from the customer account.');
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

    public function store(Request $request, CustomerPortalAccessService $access,AuditService $audit,InvitationService $invitations): RedirectResponse
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

        $password=str()->random(64);
        $user=DB::transaction(function() use($admin,$data,$password){
            $user=User::create([
                'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'customer_id'=>$admin->customer_id,
                'name'=>$data['name'],'name_en'=>$data['name'],'email'=>$data['email'],'password'=>$password,'role'=>'CUSTOMER','user_type'=>'EXTERNAL',
                'customer_portal_role'=>$data['customer_portal_role'],'status'=>'ACTIVE','force_password_change'=>true,
            ]);
            $this->replaceScopes($user,$data);
            $this->syncStructuredAccess($user,$data,$admin);
            return $user;
        });
        $invitation=$invitations->issue($user,$admin);
        $audit->record('security.customer_portal_user.created',$user,[],['customer_id'=>$user->customer_id,'portal_role'=>$user->customer_portal_role,'invitation_id'=>$invitation->id],reason:'Customer Admin invitation');
        return back()->with('status','Customer portal user created and invitation sent.');
    }

    public function update(Request $request, User $user, CustomerPortalAccessService $access,AuditService $audit): RedirectResponse
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

        $before=['name'=>$user->name,'portal_role'=>$user->customer_portal_role,'status'=>$user->status];
        DB::transaction(function() use($user,$data,$admin){
            $user->update(['name'=>$data['name'],'customer_portal_role'=>$data['customer_portal_role'],'status'=>$data['status']]);
            $this->replaceScopes($user,$data);
            $this->syncStructuredAccess($user,$data,$admin);
        });
        $audit->record('security.customer_portal_user.access_changed',$user,$before,['name'=>$user->name,'portal_role'=>$user->customer_portal_role,'status'=>$user->status],reason:'Customer Admin access update');
        return back()->with('status','User access updated.');
    }

    public function resetPassword(Request $request, User $user, CustomerPortalAccessService $access,AuditService $audit,InvitationService $invitations): RedirectResponse
    {
        $admin=$this->admin($request,$access);
        abort_unless($user->role==='CUSTOMER' && (int)$user->customer_id===(int)$admin->customer_id,404);
        $request->validate(['password'=>['nullable','string','max:100']]);
        $user->update(['password'=>str()->random(64),'force_password_change'=>true,'session_version'=>(int)$user->session_version+1]);
        DB::table('user_sessions')->where('user_id',$user->id)->where('status','ACTIVE')->update(['status'=>'REVOKED','revoked_at'=>now(),'revoked_by'=>$admin->id,'revoke_reason'=>'Customer portal password reset','updated_at'=>now()]);
        $invitation=$invitations->issue($user,$admin,2);
        $audit->record('security.customer_portal_user.password_reset',$user,[],['sessions_revoked'=>true,'reset_invitation'=>$invitation->id],reason:'Customer Admin password reset');
        return back()->with('status','Secure password reset invitation sent and active sessions revoked.');
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

    private function syncStructuredAccess(User $user,array $data,User $actor): void
    {
        $code=match($data['customer_portal_role']){'SITE_MANAGER'=>'CUSTOMER_SITE_MANAGER','FINANCE'=>'CUSTOMER_FINANCE','VIEWER'=>'CUSTOMER_VIEWER',default=>'CUSTOMER_ADMIN'};
        $role=Role::where('code',$code)->where(fn($q)=>$q->where('tenant_id',$user->tenant_id)->orWhereNull('tenant_id'))->orderByRaw('tenant_id is null')->firstOrFail();
        DB::table('user_roles')->where('user_id',$user->id)->whereNull('revoked_at')->where('role_id','!=',$role->id)->update(['revoked_at'=>now(),'updated_at'=>now()]);
        DB::table('user_roles')->updateOrInsert(['user_id'=>$user->id,'role_id'=>$role->id],['tenant_id'=>$user->tenant_id,'is_primary'=>true,'granted_by'=>$actor->id,'granted_at'=>now(),'revoked_at'=>null,'reason'=>'Customer portal access management','created_at'=>now(),'updated_at'=>now()]);

        DB::table('user_scopes')->where('user_id',$user->id)->delete();
        $scopeRows=$data['customer_portal_role']==='CUSTOMER_ADMIN' ? [['type'=>'CUSTOMER','id'=>$user->customer_id]] : collect(['SITE'=>$data['site_ids']??[],'CONTRACT'=>$data['contract_ids']??[],'ASSET'=>$data['asset_ids']??[]])->flatMap(fn($ids,$type)=>collect($ids)->map(fn($id)=>['type'=>$type,'id'=>(int)$id]))->all();
        foreach($scopeRows as $item){
            $scope=AccessScope::firstOrCreate(['tenant_id'=>$user->tenant_id,'scope_type'=>$item['type'],'scope_id'=>$item['id']],['name'=>$item['type'].' #'.$item['id'],'is_active'=>true]);
            DB::table('user_scopes')->insert(['tenant_id'=>$user->tenant_id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'USER','granted_by'=>$actor->id,'reason'=>'Customer portal access management','created_at'=>now(),'updated_at'=>now()]);
        }
    }
}
