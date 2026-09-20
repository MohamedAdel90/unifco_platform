<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AccessScope,ApiToken,Employee,Organization,Permission,ProjectUserAssignment,Role,User};
use App\Services\{AuditService,AuthorizationService,InvitationService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdministrationController extends Controller
{
    private const LEGACY_FALLBACK_ROLES=['SYSTEM_ADMIN','MANAGER','SUPERVISOR','TECHNICIAN','STOREKEEPER','CUSTOMER_ADMIN','CUSTOMER_SITE_MANAGER','CUSTOMER_FINANCE','CUSTOMER_VIEWER'];
    private const STATUSES=['ACTIVE','INACTIVE','SUSPENDED'];

    private function admin(Request $request,string $permission='users.view'): void
    {
        app(AuthorizationService::class)->authorize($request->user(),$permission);
    }

    private function scoped(Request $request,int $id): User
    {
        return User::where('tenant_id',$request->user()->tenant_id)->findOrFail($id);
    }

    private function lookups(Request $request): array
    {
        $tenant=$request->user()->tenant_id;
        return [
            'organizations'=>Organization::where('tenant_id',$tenant)->orderBy('name')->get(),
            'employees'=>Employee::where('tenant_id',$tenant)->orderBy('name')->get(),
            'roles'=>Schema::hasTable('roles') ? Role::where('is_active',true)->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->get()->sortByDesc(fn($role)=>$role->tenant_id!==null)->unique('code')->sortBy('code')->values() : collect(self::LEGACY_FALLBACK_ROLES)->map(fn($code)=>(object)['code'=>$code,'name_en'=>str($code)->headline(),'name_ar'=>null,'grants_business_authority'=>$code!=='SYSTEM_ADMIN']),
            'scopes'=>Schema::hasTable('access_scopes') ? AccessScope::where('tenant_id',$tenant)->where('is_active',true)->orderBy('scope_type')->orderBy('name')->get() : collect(),
            'statuses'=>self::STATUSES,
        ];
    }

    public function create(Request $request): View
    {
        $this->admin($request,'users.create');
        return view('navigation.users-form',array_merge($this->lookups($request),['managedUser'=>new User(),'mode'=>'create','selectedRoleCodes'=>[],'selectedPrimaryRole'=>null,'selectedScopeIds'=>[]]));
    }

    public function store(Request $request,AuditService $audit,InvitationService $invitations): RedirectResponse
    {
        $this->admin($request,'users.create');
        $tenant=$request->user()->tenant_id;
        $data=$request->validate([
            'name'=>['nullable','string','max:120','required_without_all:name_ar,name_en'],
            'name_ar'=>['nullable','string','max:160'],
            'name_en'=>['nullable','string','max:160'],
            'email'=>['required','email','max:190',Rule::unique('users','email')],
            'mobile'=>['nullable','string','max:40'],
            'password'=>['nullable','string','min:8','confirmed'],
            'role'=>['nullable','string'],
            'roles'=>['nullable','array','min:1'], 'roles.*'=>['string','max:80'],
            'primary_role'=>['nullable','string','max:80'],
            'scope_ids'=>['nullable','array'], 'scope_ids.*'=>['integer'],
            'send_invitation'=>['nullable','boolean'],
            'status'=>['required',Rule::in(self::STATUSES)],
            'organization_id'=>['nullable','integer'],
            'employee_id'=>['nullable','integer'],
        ]);
        $organizationId=$data['organization_id']??null;
        $employeeId=$data['employee_id']??null;
        if($organizationId) abort_unless(Organization::where('tenant_id',$tenant)->whereKey($organizationId)->exists(),422);
        if($employeeId) abort_unless(Employee::where('tenant_id',$tenant)->whereKey($employeeId)->exists(),422);
        $roleCodes=$this->validatedRoleCodes($tenant,$data);
        $roleCodes=$this->primaryFirst($roleCodes,$data['primary_role']??null);
        $legacyRole=$this->legacyRole($roleCodes[0]);
        $displayName=trim((string)($data['name_en']??'')) ?: trim((string)($data['name_ar']??'')) ?: trim((string)($data['name']??''));
        $user=User::create([
            'tenant_id'=>$tenant,'organization_id'=>$organizationId,'employee_id'=>$employeeId,
            'name'=>$displayName,'name_ar'=>$data['name_ar']??null,'name_en'=>$data['name_en']??null,
            'email'=>$data['email'],'mobile'=>$data['mobile']??null,'password'=>$data['password']??str()->random(48),
            'role'=>$legacyRole,'user_type'=>($roleCodes[0]==='CUSTOMER'||str_starts_with($roleCodes[0],'CUSTOMER_'))?'EXTERNAL':'INTERNAL',
            'status'=>$data['status'],'force_password_change'=>true,
        ]);
        $this->syncRoles($request,$user,$roleCodes,$audit,'Initial assignment');
        $this->syncScopes($request,$user,$data['scope_ids']??[],$audit,'Initial assignment');
        if($request->boolean('send_invitation') && Schema::hasTable('user_invitations')) {
            $invitation=$invitations->issue($user,$request->user());
            $audit->record('security.invitation.sent',$user,[],['email'=>$user->email,'invitation_id'=>$invitation->id,'expires_at'=>$invitation->expires_at->toISOString()]);
        }
        $audit->record('security.user.created',$user,[],$user->toArray(),reason:'User onboarding');
        return redirect()->route('admin.users.show',$user)->with('status','User created.');
    }

    public function show(Request $request,int $user,AuthorizationService $authorization): View
    {
        $this->admin($request,'users.view');
        $managedUser=$this->scoped($request,$user);
        $lookups=$this->lookups($request);
        $permissions=$authorization->effectivePermissions($managedUser);
        $overrides=DB::table('user_permission_overrides')->where('tenant_id',$managedUser->tenant_id)->where('user_id',$managedUser->id)->orderBy('permission_code')->get();
        $auditTimeline=DB::table('audit_logs')->where('tenant_id',$managedUser->tenant_id)->where('entity_type',User::class)->where('entity_id',$managedUser->id)->latest()->limit(25)->get();
        $apiTokens=ApiToken::where('tenant_id',$managedUser->tenant_id)->where('user_id',$managedUser->id)->latest()->get();
        $assignedRoles=Schema::hasTable('user_roles') ? $managedUser->activeRoles()->get() : collect();
        $assignedScopes=Schema::hasTable('user_scopes') ? $managedUser->accessScopes()->get() : collect();
        $grantorIds=$assignedRoles->pluck('pivot.granted_by')->merge($assignedScopes->pluck('pivot.granted_by'))->filter()->unique();
        $grantors=User::where('tenant_id',$managedUser->tenant_id)->whereIn('id',$grantorIds)->pluck('name','id');
        $activeSessions=Schema::hasTable('user_sessions') ? DB::table('user_sessions')->where('user_id',$managedUser->id)->latest('last_activity_at')->get() : collect();
        $projectAssignments=Schema::hasTable('project_user_assignments')
            ? ProjectUserAssignment::with('project')->where('tenant_id',$managedUser->tenant_id)->where('user_id',$managedUser->id)->orderByRaw("CASE WHEN status = 'ACTIVE' THEN 0 ELSE 1 END")->latest('id')->get()
            : collect();
        return view('navigation.users-show',array_merge($lookups,compact('managedUser','permissions','overrides','auditTimeline','apiTokens','assignedRoles','assignedScopes','grantors','activeSessions','projectAssignments')));
    }

    public function edit(Request $request,int $user): View
    {
        $this->admin($request,'users.edit');
        $managedUser=$this->scoped($request,$user);
        $selectedRoleCodes=Schema::hasTable('user_roles') ? $managedUser->activeRoles()->pluck('code')->all() : [$managedUser->role];
        $selectedPrimaryRole=Schema::hasTable('user_roles') ? DB::table('user_roles')->join('roles','roles.id','=','user_roles.role_id')->where('user_roles.user_id',$managedUser->id)->whereNull('user_roles.revoked_at')->where('user_roles.is_primary',true)->value('roles.code') : $managedUser->role;
        $selectedScopeIds=Schema::hasTable('user_scopes') ? DB::table('user_scopes')->where('user_id',$managedUser->id)->pluck('access_scope_id')->all() : [];
        return view('navigation.users-form',array_merge($this->lookups($request),compact('managedUser','selectedRoleCodes','selectedPrimaryRole','selectedScopeIds'),['mode'=>'edit']));
    }

    public function update(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request,'users.edit');
        $managed=$this->scoped($request,$user);
        $before=$managed->toArray();
        $tenant=$request->user()->tenant_id;
        $data=$request->validate([
            'name'=>['nullable','string','max:120','required_without_all:name_ar,name_en'],
            'name_ar'=>['nullable','string','max:160'],
            'name_en'=>['nullable','string','max:160'],
            'email'=>['required','email','max:190',Rule::unique('users','email')->ignore($managed->id)],
            'mobile'=>['nullable','string','max:40'],
            'role'=>['nullable','string'],
            'roles'=>['nullable','array','min:1'], 'roles.*'=>['string','max:80'],
            'primary_role'=>['nullable','string','max:80'],
            'scope_ids'=>['nullable','array'], 'scope_ids.*'=>['integer'],
            'status'=>['required',Rule::in(self::STATUSES)],
            'organization_id'=>['nullable','integer'],
            'employee_id'=>['nullable','integer'],
        ]);
        $roleCodes=$this->validatedRoleCodes($tenant,$data);
        $roleCodes=$this->primaryFirst($roleCodes,$data['primary_role']??null);
        if($managed->id===$request->user()->id && (!in_array('SYSTEM_ADMIN',$roleCodes,true)||$data['status']!=='ACTIVE')) {
            return back()->withErrors(['status'=>'You cannot remove your own administrator access or deactivate your current account.']);
        }
        $organizationId=$data['organization_id']??null;
        $employeeId=$data['employee_id']??null;
        if($organizationId) abort_unless(Organization::where('tenant_id',$tenant)->whereKey($organizationId)->exists(),422);
        if($employeeId) abort_unless(Employee::where('tenant_id',$tenant)->whereKey($employeeId)->exists(),422);
        $displayName=trim((string)($data['name_en']??'')) ?: trim((string)($data['name_ar']??'')) ?: trim((string)($data['name']??''));
        $managed->update([
            'name'=>$displayName,'name_ar'=>$data['name_ar']??null,'name_en'=>$data['name_en']??null,
            'email'=>$data['email'],'mobile'=>$data['mobile']??null,'role'=>$this->legacyRole($roleCodes[0]),
            'user_type'=>($roleCodes[0]==='CUSTOMER'||str_starts_with($roleCodes[0],'CUSTOMER_'))?'EXTERNAL':'INTERNAL','status'=>$data['status'],
            'organization_id'=>$organizationId,'employee_id'=>$employeeId,
        ]);
        $this->syncRoles($request,$managed,$roleCodes,$audit,'User access update');
        $this->syncScopes($request,$managed,$data['scope_ids']??[],$audit,'User access update');
        $audit->record('security.user.updated',$managed,$before,$managed->fresh()->toArray());
        return redirect()->route('admin.users.show',$managed)->with('status','User updated.');
    }

    public function status(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request,'users.edit');
        $managed=$this->scoped($request,$user);
        $data=$request->validate(['status'=>['required',Rule::in(self::STATUSES)]]);
        if($managed->id===$request->user()->id && $data['status']!=='ACTIVE') return back()->withErrors(['status'=>'You cannot deactivate or suspend your current account.']);
        $before=['status'=>$managed->status,'session_version'=>$managed->session_version];
        $managed->status=$data['status'];
        if($data['status']!=='ACTIVE') $managed->session_version++;
        $managed->save();
        if($data['status']!=='ACTIVE' && Schema::hasTable('user_sessions')) DB::table('user_sessions')->where('user_id',$managed->id)->where('status','ACTIVE')->update(['status'=>'REVOKED','revoked_at'=>now(),'revoked_by'=>$request->user()->id,'revoke_reason'=>'Account status changed to '.$data['status'],'updated_at'=>now()]);
        $audit->record('security.user.status_changed',$managed,$before,['status'=>$managed->status,'session_version'=>$managed->session_version]);
        return back()->with('status','User status updated.');
    }

    public function resetPassword(Request $request,int $user,AuditService $audit,InvitationService $invitations): RedirectResponse
    {
        $this->admin($request,'users.reset_password');
        $managed=$this->scoped($request,$user);
        $request->validate(['password'=>['nullable','string']]); // accepted for old clients, never stored
        $managed->update(['password'=>str()->random(64),'force_password_change'=>true,'session_version'=>$managed->session_version+1]);
        if(Schema::hasTable('user_sessions')) DB::table('user_sessions')->where('user_id',$managed->id)->where('status','ACTIVE')->update(['status'=>'REVOKED','revoked_at'=>now(),'revoked_by'=>$request->user()->id,'revoke_reason'=>'Administrator password reset','updated_at'=>now()]);
        $invitation=$invitations->issue($managed,$request->user(),2);
        $audit->record('security.user.password_reset',$managed,[],['reset_by'=>$request->user()->id,'force_password_change'=>true,'sessions_revoked'=>true,'invitation_id'=>$invitation->id]);
        return back()->with('status','Password reset invitation sent and existing sessions revoked.');
    }

    public function permission(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request,'roles.assign');
        $managed=$this->scoped($request,$user);
        $data=$request->validate(['permission_code'=>['required','string','max:120'],'effect'=>['required',Rule::in(['ALLOW','DENY','INHERIT'])],'reason'=>['required_unless:effect,INHERIT','nullable','string','max:500'],'expires_at'=>['nullable','date','after:now']]);
        $businessPermission=Permission::where('code',$data['permission_code'])->where('is_business_authority',true)->exists();
        $isSystemAdministrator=$managed->role==='SYSTEM_ADMIN' || (Schema::hasTable('user_roles') && $managed->activeRoles()->where('roles.code','SYSTEM_ADMIN')->exists());
        abort_if($data['effect']==='ALLOW' && $businessPermission && $isSystemAdministrator,422,'Assign a separate business role instead of a business permission exception.');
        $key=['tenant_id'=>$managed->tenant_id,'user_id'=>$managed->id,'permission_code'=>$data['permission_code']];
        if($data['effect']==='INHERIT') DB::table('user_permission_overrides')->where($key)->delete();
        else DB::table('user_permission_overrides')->updateOrInsert($key,['allowed'=>$data['effect']==='ALLOW','updated_by'=>$request->user()->id,'reason'=>$data['reason'],'expires_at'=>$data['expires_at']??null,'created_at'=>now(),'updated_at'=>now()]);
        $audit->record('security.user.permission_override',$managed,[],['permission_code'=>$data['permission_code'],'effect'=>$data['effect'],'expires_at'=>$data['expires_at']??null],reason:$data['reason']??'Returned to role inheritance');
        return back()->with('status','User permission updated.');
    }

    public function security(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request,'sessions.manage');
        $managed=$this->scoped($request,$user);
        $data=$request->validate(['action'=>['required',Rule::in(['LOCK','UNLOCK','REQUIRE_PASSWORD_CHANGE','CLEAR_PASSWORD_CHANGE','REVOKE_SESSIONS'])]]);
        if($managed->id===$request->user()->id && in_array($data['action'],['LOCK','REVOKE_SESSIONS'],true)) return back()->withErrors(['security'=>'You cannot lock or revoke the current administrator session.']);
        $before=['locked_at'=>$managed->locked_at,'force_password_change'=>$managed->force_password_change,'session_version'=>$managed->session_version];
        if($data['action']==='LOCK') { $managed->locked_at=now(); $managed->session_version++; }
        if($data['action']==='UNLOCK') $managed->locked_at=null;
        if($data['action']==='REQUIRE_PASSWORD_CHANGE') $managed->force_password_change=true;
        if($data['action']==='CLEAR_PASSWORD_CHANGE') $managed->force_password_change=false;
        if($data['action']==='REVOKE_SESSIONS') {
            $managed->session_version++;
            if(Schema::hasTable('user_sessions')) DB::table('user_sessions')->where('user_id',$managed->id)->where('status','ACTIVE')->update(['status'=>'REVOKED','revoked_at'=>now(),'revoked_by'=>$request->user()->id,'revoke_reason'=>'Administrator force logout','updated_at'=>now()]);
        }
        $managed->save();
        $audit->record('security.user.security_action',$managed,$before,['action'=>$data['action'],'locked_at'=>$managed->locked_at,'force_password_change'=>$managed->force_password_change,'session_version'=>$managed->session_version]);
        return back()->with('status','Security action applied.');
    }

    public function bulk(Request $request,AuditService $audit): RedirectResponse
    {
        $this->admin($request,'users.edit');
        $data=$request->validate(['user_ids'=>['required','array','min:1'],'user_ids.*'=>['integer'],'action'=>['required',Rule::in(['ACTIVATE','DEACTIVATE','SUSPEND'])]]);
        $users=User::where('tenant_id',$request->user()->tenant_id)->whereIn('id',$data['user_ids'])->get();
        foreach($users as $managed){
            if($managed->id===$request->user()->id && $data['action']!=='ACTIVATE') continue;
            $before=['status'=>$managed->status,'session_version'=>$managed->session_version];
            $managed->status=$data['action']==='ACTIVATE'?'ACTIVE':($data['action']==='SUSPEND'?'SUSPENDED':'INACTIVE');
            if($managed->status!=='ACTIVE') $managed->session_version++;
            $managed->save();
            $audit->record('security.user.bulk_status',$managed,$before,['status'=>$managed->status,'session_version'=>$managed->session_version]);
        }
        return back()->with('status','Bulk action completed.');
    }

    public function export(Request $request)
    {
        $this->admin($request,'users.view');
        $tenant=$request->user()->tenant_id;
        $organizations=Organization::where('tenant_id',$tenant)->pluck('code','id');
        $employees=Employee::where('tenant_id',$tenant)->pluck('employee_no','id');
        $users=User::where('tenant_id',$tenant)->orderBy('name')->get();
        return response()->streamDownload(function()use($users,$organizations,$employees){
            $handle=fopen('php://output','w');
            fputcsv($handle,['name','email','role','status','organization_code','employee_no','last_login_at','mfa_status','locked','force_password_change']);
            foreach($users as $user) fputcsv($handle,[$user->name,$user->email,$user->role,$user->status,$organizations[$user->organization_id]??'',$employees[$user->employee_id]??'',optional($user->last_login_at)->toIso8601String(),$user->mfa_status,$user->locked_at?'yes':'no',$user->force_password_change?'yes':'no']);
            fclose($handle);
        },'unifco-users.csv',['Content-Type'=>'text/csv']);
    }

    public function import(Request $request,AuditService $audit): RedirectResponse
    {
        $this->admin($request,'users.create');
        $request->validate(['file'=>['required','file','mimes:csv,txt','max:2048']]);
        $tenant=$request->user()->tenant_id;
        $organizations=Organization::where('tenant_id',$tenant)->get()->keyBy(fn($x)=>strtolower($x->code));
        $employees=Employee::where('tenant_id',$tenant)->get()->keyBy(fn($x)=>strtolower($x->employee_no));
        $validRoleCodes=Schema::hasTable('roles')
            ? Role::where('is_active',true)->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->pluck('code')->map(fn($code)=>strtoupper($code))->unique()
            : collect(self::LEGACY_FALLBACK_ROLES);
        $handle=fopen($request->file('file')->getRealPath(),'r');
        $header=fgetcsv($handle);
        if(!$header) return back()->withErrors(['file'=>'CSV file is empty.']);
        $header=array_map(fn($v)=>strtolower(trim((string)$v)),$header);
        foreach(['name','email','role','status'] as $required) if(!in_array($required,$header,true)) return back()->withErrors(['file'=>"Missing required column: {$required}"]);
        $imported=0;$skipped=0;$errors=[];$line=1;
        while(($values=fgetcsv($handle))!==false){
            $line++;
            if(count($values)!==count($header)){ $skipped++;$errors[]="Line {$line}: column count mismatch";continue; }
            $row=array_combine($header,$values);
            $name=trim((string)$row['name']);$email=strtolower(trim((string)$row['email']));$role=strtoupper(trim((string)$row['role']));$status=strtoupper(trim((string)$row['status']));
            if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$validRoleCodes->contains($role)||!in_array($status,self::STATUSES,true)){ $skipped++;$errors[]="Line {$line}: invalid identity, role, or status";continue; }
            $existing=User::where('email',$email)->first();
            if($existing && $existing->tenant_id!==$tenant){ $skipped++;$errors[]="Line {$line}: email belongs to another tenant";continue; }
            $password=trim((string)($row['password']??''));
            if(!$existing && strlen($password)<8){ $skipped++;$errors[]="Line {$line}: new users require password (8+ characters)";continue; }
            $organizationId=null;$employeeId=null;
            $organizationCode=strtolower(trim((string)($row['organization_code']??'')));
            $employeeNo=strtolower(trim((string)($row['employee_no']??'')));
            if($organizationCode){ if(!isset($organizations[$organizationCode])){$skipped++;$errors[]="Line {$line}: unknown organization_code";continue;} $organizationId=$organizations[$organizationCode]->id; }
            if($employeeNo){ if(!isset($employees[$employeeNo])){$skipped++;$errors[]="Line {$line}: unknown employee_no";continue;} $employeeId=$employees[$employeeNo]->id; }
            $managed=$existing?:new User(['tenant_id'=>$tenant]);
            $before=$existing?$managed->toArray():[];
            $managed->fill(['name'=>$name,'name_en'=>$name,'email'=>$email,'role'=>$this->legacyRole($role),'user_type'=>str_starts_with($role,'CUSTOMER_')?'EXTERNAL':'INTERNAL','status'=>$status,'organization_id'=>$organizationId,'employee_id'=>$employeeId]);
            if($password!==''){ $managed->password=$password;$managed->force_password_change=true;$managed->session_version=($managed->session_version?:1)+($existing?1:0); }
            $managed->save();
            $this->syncRoles($request,$managed,[$role],$audit,'CSV import');
            $audit->record($existing?'security.user.import_updated':'security.user.import_created',$managed,$before,$managed->toArray());
            $imported++;
        }
        fclose($handle);
        return back()->with('status',"Import complete: {$imported} processed, {$skipped} skipped.")->with('import_errors',array_slice($errors,0,20));
    }

    public function revokeToken(Request $request,int $user,int $token,AuditService $audit): RedirectResponse
    {
        $this->admin($request,'integrations.manage');
        $managed=$this->scoped($request,$user);
        $apiToken=ApiToken::where('tenant_id',$managed->tenant_id)->where('user_id',$managed->id)->findOrFail($token);
        $apiToken->update(['revoked_at'=>now()]);
        $audit->record('security.api_token.admin_revoked',$apiToken,[],['user_id'=>$managed->id,'name'=>$apiToken->name]);
        return back()->with('status','API token revoked.');
    }

    public function revokeSession(Request $request,int $user,int $session,AuditService $audit): RedirectResponse
    {
        $this->admin($request,'sessions.manage'); $managed=$this->scoped($request,$user);
        $row=DB::table('user_sessions')->where('tenant_id',$managed->tenant_id)->where('user_id',$managed->id)->where('id',$session)->first();
        abort_unless($row,404);
        abort_if($row->session_id===$request->session()->getId(),422,'You cannot revoke your current session.');
        DB::table('user_sessions')->where('id',$row->id)->update(['status'=>'REVOKED','revoked_at'=>now(),'revoked_by'=>$request->user()->id,'revoke_reason'=>'Administrator revoked individual session','updated_at'=>now()]);
        $audit->record('security.user.session_revoked',$managed,(array)$row,['status'=>'REVOKED'],reason:'Administrator revoked individual session');
        return back()->with('status','Session revoked.');
    }

    private function validatedRoleCodes(int $tenant,array $data): array
    {
        $codes=array_values(array_unique(array_map('strtoupper',$data['roles']??(isset($data['role'])?[$data['role']]:[]))));
        abort_if($codes===[],422,'At least one role is required.');
        if(Schema::hasTable('roles')) {
            $valid=Role::where('is_active',true)->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->whereIn('code',$codes)->pluck('code')->unique()->all();
            abort_unless(count($valid)===count($codes),422,'One or more roles are invalid.');
        } else abort_unless(collect($codes)->every(fn($code)=>in_array($code,self::LEGACY_FALLBACK_ROLES,true)||$code==='ADMIN'),422,'One or more roles are invalid.');
        return $codes;
    }

    private function primaryFirst(array $codes,?string $primary): array
    {
        $primary=strtoupper(trim((string)$primary));
        if($primary==='' || !in_array($primary,$codes,true)) return $codes;
        return array_values(array_unique([$primary,...$codes]));
    }

    private function legacyRole(string $code): string
    {
        if($code==='CUSTOMER' || str_starts_with($code,'CUSTOMER_')) return 'CUSTOMER';
        return $code;
    }

    private function syncRoles(Request $request,User $user,array $codes,AuditService $audit,string $reason): void
    {
        if(!Schema::hasTable('user_roles')) return;
        $catalog=Role::where('is_active',true)->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$user->tenant_id))->whereIn('code',$codes)->get()->sortByDesc(fn($role)=>$role->tenant_id!==null)->unique('code')->keyBy('code');
        $roles=collect($codes)->map(fn($code)=>$catalog->get($code))->filter()->values();
        $before=DB::table('user_roles')->join('roles','roles.id','=','user_roles.role_id')->where('user_roles.user_id',$user->id)->whereNull('user_roles.revoked_at')->pluck('roles.code')->all();
        DB::table('user_roles')->where('user_id',$user->id)->whereNull('revoked_at')->whereNotIn('role_id',$roles->pluck('id'))->update(['revoked_at'=>now(),'updated_at'=>now()]);
        foreach($roles as $index=>$role) DB::table('user_roles')->updateOrInsert(['user_id'=>$user->id,'role_id'=>$role->id],['tenant_id'=>$user->tenant_id,'is_primary'=>$index===0,'granted_by'=>$request->user()->id,'granted_at'=>now(),'revoked_at'=>null,'reason'=>$reason,'created_at'=>now(),'updated_at'=>now()]);
        if($before!==$codes) $audit->record('security.user.roles_changed',$user,['roles'=>$before],['roles'=>$codes],reason:$reason);
    }

    private function syncScopes(Request $request,User $user,array $scopeIds,AuditService $audit,string $reason): void
    {
        if(!Schema::hasTable('user_scopes')) return;
        $valid=AccessScope::where('tenant_id',$user->tenant_id)->where('is_active',true)->whereIn('id',$scopeIds)->pluck('id')->all();
        abort_unless(count($valid)===count(array_unique($scopeIds)),422,'One or more scopes are invalid.');
        $before=DB::table('user_scopes')->where('user_id',$user->id)->pluck('access_scope_id')->all();
        DB::table('user_scopes')->where('user_id',$user->id)->whereNotIn('access_scope_id',$valid)->delete();
        foreach($valid as $id) DB::table('user_scopes')->updateOrInsert(['user_id'=>$user->id,'access_scope_id'=>$id],['tenant_id'=>$user->tenant_id,'source'=>'USER','granted_by'=>$request->user()->id,'reason'=>$reason,'created_at'=>now(),'updated_at'=>now()]);
        if($before!==$valid) $audit->record('security.user.scopes_changed',$user,['scope_ids'=>$before],['scope_ids'=>$valid],reason:$reason);
    }
}
