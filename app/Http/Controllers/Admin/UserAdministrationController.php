<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ApiToken,Employee,Organization,User};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdministrationController extends Controller
{
    private const ROLES=['ADMIN','MANAGER','SUPERVISOR','TECHNICIAN','STOREKEEPER','CUSTOMER'];
    private const STATUSES=['ACTIVE','INACTIVE','SUSPENDED'];

    private function admin(Request $request): void
    {
        abort_unless($request->user()->role==='ADMIN',403);
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
            'roles'=>self::ROLES,
            'statuses'=>self::STATUSES,
        ];
    }

    public function create(Request $request): View
    {
        $this->admin($request);
        return view('navigation.users-form',array_merge($this->lookups($request),['managedUser'=>new User(),'mode'=>'create']));
    }

    public function store(Request $request,AuditService $audit): RedirectResponse
    {
        $this->admin($request);
        $tenant=$request->user()->tenant_id;
        $data=$request->validate([
            'name'=>['required','string','max:120'],
            'email'=>['required','email','max:190',Rule::unique('users','email')],
            'password'=>['required','string','min:8','confirmed'],
            'role'=>['required',Rule::in(self::ROLES)],
            'status'=>['required',Rule::in(self::STATUSES)],
            'organization_id'=>['nullable','integer'],
            'employee_id'=>['nullable','integer'],
        ]);
        $organizationId=$data['organization_id']??null;
        $employeeId=$data['employee_id']??null;
        if($organizationId) abort_unless(Organization::where('tenant_id',$tenant)->whereKey($organizationId)->exists(),422);
        if($employeeId) abort_unless(Employee::where('tenant_id',$tenant)->whereKey($employeeId)->exists(),422);
        $user=User::create([
            'tenant_id'=>$tenant,'organization_id'=>$organizationId,'employee_id'=>$employeeId,
            'name'=>$data['name'],'email'=>$data['email'],'password'=>$data['password'],
            'role'=>$data['role'],'status'=>$data['status'],'force_password_change'=>true,
        ]);
        $audit->record('security.user.created',$user,[],$user->toArray());
        return redirect()->route('admin.users.show',$user)->with('status','User created.');
    }

    public function show(Request $request,int $user): View
    {
        $this->admin($request);
        $managedUser=$this->scoped($request,$user);
        $lookups=$this->lookups($request);
        $permissions=DB::table('role_permissions')
            ->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$managedUser->tenant_id))
            ->where('role_code',$managedUser->role)->orderBy('permission_code')->pluck('permission_code');
        $overrides=DB::table('user_permission_overrides')->where('tenant_id',$managedUser->tenant_id)->where('user_id',$managedUser->id)->orderBy('permission_code')->get();
        $auditTimeline=DB::table('audit_logs')->where('tenant_id',$managedUser->tenant_id)->where('entity_type',User::class)->where('entity_id',$managedUser->id)->latest()->limit(25)->get();
        $apiTokens=ApiToken::where('tenant_id',$managedUser->tenant_id)->where('user_id',$managedUser->id)->latest()->get();
        return view('navigation.users-show',array_merge($lookups,compact('managedUser','permissions','overrides','auditTimeline','apiTokens')));
    }

    public function edit(Request $request,int $user): View
    {
        $this->admin($request);
        return view('navigation.users-form',array_merge($this->lookups($request),['managedUser'=>$this->scoped($request,$user),'mode'=>'edit']));
    }

    public function update(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request);
        $managed=$this->scoped($request,$user);
        $before=$managed->toArray();
        $tenant=$request->user()->tenant_id;
        $data=$request->validate([
            'name'=>['required','string','max:120'],
            'email'=>['required','email','max:190',Rule::unique('users','email')->ignore($managed->id)],
            'role'=>['required',Rule::in(self::ROLES)],
            'status'=>['required',Rule::in(self::STATUSES)],
            'organization_id'=>['nullable','integer'],
            'employee_id'=>['nullable','integer'],
        ]);
        if($managed->id===$request->user()->id && ($data['role']!=='ADMIN'||$data['status']!=='ACTIVE')) {
            return back()->withErrors(['status'=>'You cannot remove your own administrator access or deactivate your current account.']);
        }
        $organizationId=$data['organization_id']??null;
        $employeeId=$data['employee_id']??null;
        if($organizationId) abort_unless(Organization::where('tenant_id',$tenant)->whereKey($organizationId)->exists(),422);
        if($employeeId) abort_unless(Employee::where('tenant_id',$tenant)->whereKey($employeeId)->exists(),422);
        $managed->update([
            'name'=>$data['name'],'email'=>$data['email'],'role'=>$data['role'],'status'=>$data['status'],
            'organization_id'=>$organizationId,'employee_id'=>$employeeId,
        ]);
        $audit->record('security.user.updated',$managed,$before,$managed->fresh()->toArray());
        return redirect()->route('admin.users.show',$managed)->with('status','User updated.');
    }

    public function status(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request);
        $managed=$this->scoped($request,$user);
        $data=$request->validate(['status'=>['required',Rule::in(self::STATUSES)]]);
        if($managed->id===$request->user()->id && $data['status']!=='ACTIVE') return back()->withErrors(['status'=>'You cannot deactivate or suspend your current account.']);
        $before=['status'=>$managed->status,'session_version'=>$managed->session_version];
        $managed->status=$data['status'];
        if($data['status']!=='ACTIVE') $managed->session_version++;
        $managed->save();
        $audit->record('security.user.status_changed',$managed,$before,['status'=>$managed->status,'session_version'=>$managed->session_version]);
        return back()->with('status','User status updated.');
    }

    public function resetPassword(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request);
        $managed=$this->scoped($request,$user);
        $data=$request->validate(['password'=>['required','string','min:8','confirmed']]);
        $managed->update(['password'=>$data['password'],'force_password_change'=>true,'session_version'=>$managed->session_version+1]);
        $audit->record('security.user.password_reset',$managed,[],['reset_by'=>$request->user()->id,'force_password_change'=>true,'sessions_revoked'=>true]);
        return back()->with('status','Password reset and existing sessions revoked.');
    }

    public function permission(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request);
        $managed=$this->scoped($request,$user);
        abort_if($managed->role==='ADMIN',422,'Administrator permissions are implicit.');
        $data=$request->validate(['permission_code'=>['required','string','max:120'],'effect'=>['required',Rule::in(['ALLOW','DENY','INHERIT'])]]);
        $key=['tenant_id'=>$managed->tenant_id,'user_id'=>$managed->id,'permission_code'=>$data['permission_code']];
        if($data['effect']==='INHERIT') DB::table('user_permission_overrides')->where($key)->delete();
        else DB::table('user_permission_overrides')->updateOrInsert($key,['allowed'=>$data['effect']==='ALLOW','updated_by'=>$request->user()->id,'created_at'=>now(),'updated_at'=>now()]);
        $audit->record('security.user.permission_override',$managed,[],['permission_code'=>$data['permission_code'],'effect'=>$data['effect']]);
        return back()->with('status','User permission updated.');
    }

    public function security(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request);
        $managed=$this->scoped($request,$user);
        $data=$request->validate(['action'=>['required',Rule::in(['LOCK','UNLOCK','REQUIRE_PASSWORD_CHANGE','CLEAR_PASSWORD_CHANGE','REVOKE_SESSIONS'])]]);
        if($managed->id===$request->user()->id && in_array($data['action'],['LOCK','REVOKE_SESSIONS'],true)) return back()->withErrors(['security'=>'You cannot lock or revoke the current administrator session.']);
        $before=['locked_at'=>$managed->locked_at,'force_password_change'=>$managed->force_password_change,'session_version'=>$managed->session_version];
        if($data['action']==='LOCK') { $managed->locked_at=now(); $managed->session_version++; }
        if($data['action']==='UNLOCK') $managed->locked_at=null;
        if($data['action']==='REQUIRE_PASSWORD_CHANGE') $managed->force_password_change=true;
        if($data['action']==='CLEAR_PASSWORD_CHANGE') $managed->force_password_change=false;
        if($data['action']==='REVOKE_SESSIONS') $managed->session_version++;
        $managed->save();
        $audit->record('security.user.security_action',$managed,$before,['action'=>$data['action'],'locked_at'=>$managed->locked_at,'force_password_change'=>$managed->force_password_change,'session_version'=>$managed->session_version]);
        return back()->with('status','Security action applied.');
    }

    public function bulk(Request $request,AuditService $audit): RedirectResponse
    {
        $this->admin($request);
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
        $this->admin($request);
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
        $this->admin($request);
        $request->validate(['file'=>['required','file','mimes:csv,txt','max:2048']]);
        $tenant=$request->user()->tenant_id;
        $organizations=Organization::where('tenant_id',$tenant)->get()->keyBy(fn($x)=>strtolower($x->code));
        $employees=Employee::where('tenant_id',$tenant)->get()->keyBy(fn($x)=>strtolower($x->employee_no));
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
            if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($role,self::ROLES,true)||!in_array($status,self::STATUSES,true)){ $skipped++;$errors[]="Line {$line}: invalid identity, role, or status";continue; }
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
            $managed->fill(['name'=>$name,'email'=>$email,'role'=>$role,'status'=>$status,'organization_id'=>$organizationId,'employee_id'=>$employeeId]);
            if($password!==''){ $managed->password=$password;$managed->force_password_change=true;$managed->session_version=($managed->session_version?:1)+($existing?1:0); }
            $managed->save();
            $audit->record($existing?'security.user.import_updated':'security.user.import_created',$managed,$before,$managed->toArray());
            $imported++;
        }
        fclose($handle);
        return back()->with('status',"Import complete: {$imported} processed, {$skipped} skipped.")->with('import_errors',array_slice($errors,0,20));
    }

    public function revokeToken(Request $request,int $user,int $token,AuditService $audit): RedirectResponse
    {
        $this->admin($request);
        $managed=$this->scoped($request,$user);
        $apiToken=ApiToken::where('tenant_id',$managed->tenant_id)->where('user_id',$managed->id)->findOrFail($token);
        $apiToken->update(['revoked_at'=>now()]);
        $audit->record('security.api_token.admin_revoked',$apiToken,[],['user_id'=>$managed->id,'name'=>$apiToken->name]);
        return back()->with('status','API token revoked.');
    }
}
