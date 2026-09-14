<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AccessScope,Customer,CustomerSite,Employee,Organization,Role,User};
use App\Services\{AuditService,AuthorizationService,InvitationService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GovernedUserCreationController extends Controller
{
    private const STATUSES=['ACTIVE','INACTIVE','SUSPENDED'];
    private const PORTAL_ROLE_CODES=['CUSTOMER_ADMIN','CUSTOMER_SITE_MANAGER','CUSTOMER_FINANCE','CUSTOMER_VIEWER'];

    private function authorize(Request $request,string $permission): void
    {
        app(AuthorizationService::class)->authorize($request->user(),$permission);
    }

    private function lookups(Request $request): array
    {
        $tenant=$request->user()->tenant_id;
        $customers=Customer::where('tenant_id',$tenant)->where('status','ACTIVE')->with(['sites'=>fn($q)=>$q->where('status','ACTIVE')->orderBy('name')])->orderBy('name')->get();
        return [
            'organizations'=>Organization::where('tenant_id',$tenant)->orderBy('name')->get(),
            'employees'=>Employee::where('tenant_id',$tenant)->orderBy('name')->get(),
            'roles'=>Role::where('is_active',true)->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->get()->sortByDesc(fn($role)=>$role->tenant_id!==null)->unique('code')->sortBy('code')->values(),
            'scopes'=>AccessScope::where('tenant_id',$tenant)->where('is_active',true)->orderBy('scope_type')->orderBy('name')->get(),
            'statuses'=>self::STATUSES,
            'customers'=>$customers,
            'portalRoleCodes'=>self::PORTAL_ROLE_CODES,
        ];
    }

    public function create(Request $request): View
    {
        $this->authorize($request,'users.create');
        $portalMode=$request->boolean('portal');
        return view('navigation.users-form',array_merge($this->lookups($request),[
            'managedUser'=>new User(),
            'mode'=>'create',
            'portalMode'=>$portalMode,
            'selectedRoleCodes'=>$portalMode?['CUSTOMER_ADMIN']:[],
            'selectedPrimaryRole'=>$portalMode?'CUSTOMER_ADMIN':null,
            'selectedScopeIds'=>[],
            'selectedCustomerId'=>$request->integer('customer_id') ?: null,
            'selectedSiteIds'=>[],
        ]));
    }

    public function store(Request $request,AuditService $audit,InvitationService $invitations): RedirectResponse
    {
        $this->authorize($request,'users.create');
        $tenant=$request->user()->tenant_id;
        $data=$request->validate([
            'name'=>['nullable','string','max:120','required_without_all:name_ar,name_en'],
            'name_ar'=>['nullable','string','max:160'],
            'name_en'=>['nullable','string','max:160'],
            'email'=>['required','email','max:190',Rule::unique('users','email')],
            'mobile'=>['nullable','string','max:40'],
            'password'=>['nullable','string','min:10','confirmed'],
            'roles'=>['required','array','min:1'],'roles.*'=>['string','max:80'],
            'primary_role'=>['nullable','string','max:80'],
            'scope_ids'=>['nullable','array'],'scope_ids.*'=>['integer'],
            'customer_id'=>['nullable','integer'],
            'site_ids'=>['nullable','array'],'site_ids.*'=>['integer'],
            'send_invitation'=>['nullable','boolean'],
            'status'=>['required',Rule::in(self::STATUSES)],
            'organization_id'=>['nullable','integer'],
            'employee_id'=>['nullable','integer'],
        ]);

        $roleCodes=array_values(array_unique(array_map('strtoupper',$data['roles'])));
        $validRoles=Role::where('is_active',true)->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->whereIn('code',$roleCodes)->pluck('code')->unique()->all();
        abort_unless(count($validRoles)===count($roleCodes),422,'One or more roles are invalid.');
        $primary=strtoupper(trim((string)($data['primary_role']??'')));
        if($primary && in_array($primary,$roleCodes,true)) $roleCodes=array_values(array_unique([$primary,...$roleCodes]));

        $portalRoles=array_values(array_intersect($roleCodes,self::PORTAL_ROLE_CODES));
        $isPortal=$portalRoles!==[];
        abort_if($isPortal && count($portalRoles)!==count($roleCodes),422,'Customer Portal users cannot be mixed with internal business or system roles.');
        abort_if($isPortal && count($portalRoles)!==1,422,'Choose exactly one Customer Portal role.');

        $customer=null;$siteIds=[];
        if($isPortal){
            $customer=Customer::where('tenant_id',$tenant)->where('status','ACTIVE')->findOrFail($data['customer_id']??0);
            $siteIds=CustomerSite::where('customer_id',$customer->id)->where('status','ACTIVE')->whereIn('id',$data['site_ids']??[])->pluck('id')->map(fn($id)=>(int)$id)->all();
            abort_unless(count($siteIds)===count(array_unique(array_map('intval',$data['site_ids']??[]))),422,'One or more allowed sites do not belong to the selected customer.');
            if($portalRoles[0]==='CUSTOMER_SITE_MANAGER') abort_if($siteIds===[],422,'Site Manager requires at least one allowed site.');
        }

        $organizationId=$isPortal?($customer->organization_id ?: ($data['organization_id']??null)):($data['organization_id']??null);
        $employeeId=$isPortal?null:($data['employee_id']??null);
        if($organizationId) abort_unless(Organization::where('tenant_id',$tenant)->whereKey($organizationId)->exists(),422);
        if($employeeId) abort_unless(Employee::where('tenant_id',$tenant)->whereKey($employeeId)->exists(),422);

        $displayName=trim((string)($data['name_en']??'')) ?: trim((string)($data['name_ar']??'')) ?: trim((string)($data['name']??''));
        $portalRole=$isPortal?$this->portalRole($portalRoles[0]):null;
        $password=$data['password']??str()->random(64);

        $user=DB::transaction(function() use($request,$audit,$tenant,$data,$roleCodes,$isPortal,$customer,$siteIds,$organizationId,$employeeId,$displayName,$portalRole,$password){
            $user=User::create([
                'tenant_id'=>$tenant,
                'organization_id'=>$organizationId,
                'employee_id'=>$employeeId,
                'customer_id'=>$customer?->id,
                'name'=>$displayName,
                'name_ar'=>$data['name_ar']??null,
                'name_en'=>$data['name_en']??null,
                'email'=>strtolower($data['email']),
                'mobile'=>$data['mobile']??null,
                'password'=>$password,
                'role'=>$isPortal?'CUSTOMER':$roleCodes[0],
                'user_type'=>$isPortal?'EXTERNAL':'INTERNAL',
                'customer_portal_role'=>$portalRole,
                'status'=>$data['status'],
                'force_password_change'=>true,
            ]);
            $this->syncRoles($request,$user,$roleCodes);
            if($isPortal) $this->syncPortalScopes($request,$user,$siteIds,$portalRole);
            else $this->syncInternalScopes($request,$user,$data['scope_ids']??[]);
            $audit->record('security.user.created',$user,[],['email'=>$user->email,'customer_id'=>$user->customer_id,'portal_role'=>$user->customer_portal_role,'roles'=>$roleCodes],reason:$isPortal?'Customer Portal onboarding':'User onboarding');
            return $user;
        });

        if($request->boolean('send_invitation') && Schema::hasTable('user_invitations')){
            $invitation=$invitations->issue($user,$request->user());
            $audit->record('security.invitation.sent',$user,[],['email'=>$user->email,'invitation_id'=>$invitation->id,'expires_at'=>$invitation->expires_at->toISOString()]);
        }

        return redirect()->route($isPortal?'admin.customer-portal-users.index':'admin.users.show',$isPortal?[]:$user)->with('status',$isPortal?'Customer Portal user created and linked to '.$customer->name.'.':'User created.');
    }

    private function portalRole(string $roleCode): string
    {
        return match($roleCode){
            'CUSTOMER_SITE_MANAGER'=>'SITE_MANAGER',
            'CUSTOMER_FINANCE'=>'FINANCE',
            'CUSTOMER_VIEWER'=>'VIEWER',
            default=>'CUSTOMER_ADMIN',
        };
    }

    private function syncRoles(Request $request,User $user,array $codes): void
    {
        if(!Schema::hasTable('user_roles')) return;
        $catalog=Role::where('is_active',true)->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$user->tenant_id))->whereIn('code',$codes)->get()->sortByDesc(fn($r)=>$r->tenant_id!==null)->unique('code')->keyBy('code');
        foreach($codes as $index=>$code){
            $role=$catalog->get($code); abort_unless($role,422,'Role not available.');
            DB::table('user_roles')->updateOrInsert(['user_id'=>$user->id,'role_id'=>$role->id],[
                'tenant_id'=>$user->tenant_id,'is_primary'=>$index===0,'granted_by'=>$request->user()->id,'granted_at'=>now(),'revoked_at'=>null,'reason'=>'Governed user creation','created_at'=>now(),'updated_at'=>now(),
            ]);
        }
    }

    private function syncInternalScopes(Request $request,User $user,array $scopeIds): void
    {
        if(!Schema::hasTable('user_scopes')) return;
        $ids=array_values(array_unique(array_map('intval',$scopeIds)));
        $valid=AccessScope::where('tenant_id',$user->tenant_id)->where('is_active',true)->whereIn('id',$ids)->pluck('id')->all();
        abort_unless(count($valid)===count($ids),422,'One or more scopes are invalid.');
        foreach($valid as $id) DB::table('user_scopes')->insert(['tenant_id'=>$user->tenant_id,'user_id'=>$user->id,'access_scope_id'=>$id,'source'=>'USER','granted_by'=>$request->user()->id,'reason'=>'Governed user creation','created_at'=>now(),'updated_at'=>now()]);
    }

    private function syncPortalScopes(Request $request,User $user,array $siteIds,string $portalRole): void
    {
        if(Schema::hasTable('customer_portal_user_scopes')){
            foreach($portalRole==='CUSTOMER_ADMIN'?[]:$siteIds as $id) DB::table('customer_portal_user_scopes')->insert(['user_id'=>$user->id,'scope_type'=>'SITE','scope_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        }
        if(!Schema::hasTable('user_scopes')) return;
        $items=$portalRole==='CUSTOMER_ADMIN'?[['type'=>'CUSTOMER','id'=>$user->customer_id]]:array_map(fn($id)=>['type'=>'SITE','id'=>$id],$siteIds);
        foreach($items as $item){
            $scope=AccessScope::firstOrCreate(['tenant_id'=>$user->tenant_id,'scope_type'=>$item['type'],'scope_id'=>$item['id']],['name'=>$item['type'].' #'.$item['id'],'is_active'=>true]);
            DB::table('user_scopes')->insert(['tenant_id'=>$user->tenant_id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'USER','granted_by'=>$request->user()->id,'reason'=>'Customer Portal onboarding','created_at'=>now(),'updated_at'=>now()]);
        }
    }
}
