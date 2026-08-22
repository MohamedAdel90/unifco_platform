<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Employee,Organization,User};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdministrationController extends Controller
{
    private const ROLES=['ADMIN','MANAGER','SUPERVISOR','TECHNICIAN','STOREKEEPER','CUSTOMER'];
    private const STATUSES=['ACTIVE','INACTIVE','SUSPENDED'];

    private function admin(Request $request): void { abort_unless($request->user()->role==='ADMIN',403); }
    private function scoped(Request $request,int $id): User { return User::where('tenant_id',$request->user()->tenant_id)->findOrFail($id); }
    private function lookups(Request $request): array {
        $tenant=$request->user()->tenant_id;
        return [
            'organizations'=>Organization::where('tenant_id',$tenant)->orderBy('name')->get(),
            'employees'=>Employee::where('tenant_id',$tenant)->orderBy('name')->get(),
            'roles'=>self::ROLES,
            'statuses'=>self::STATUSES,
        ];
    }

    public function create(Request $request): View { $this->admin($request); return view('navigation.users-form',array_merge($this->lookups($request),['managedUser'=>new User(),'mode'=>'create'])); }

    public function store(Request $request,AuditService $audit): RedirectResponse
    {
        $this->admin($request); $tenant=$request->user()->tenant_id;
        $data=$request->validate([
            'name'=>['required','string','max:120'],'email'=>['required','email','max:190',Rule::unique('users','email')],
            'password'=>['required','string','min:8','confirmed'],'role'=>['required',Rule::in(self::ROLES)],
            'status'=>['required',Rule::in(self::STATUSES)],'organization_id'=>['nullable','integer'],'employee_id'=>['nullable','integer'],
        ]);
        $organizationId=$data['organization_id']??null; $employeeId=$data['employee_id']??null;
        if($organizationId) abort_unless(Organization::where('tenant_id',$tenant)->whereKey($organizationId)->exists(),422);
        if($employeeId) abort_unless(Employee::where('tenant_id',$tenant)->whereKey($employeeId)->exists(),422);
        $user=User::create(['tenant_id'=>$tenant,'organization_id'=>$organizationId,'employee_id'=>$employeeId,'name'=>$data['name'],'email'=>$data['email'],'password'=>$data['password'],'role'=>$data['role'],'status'=>$data['status']]);
        $audit->record('security.user.created',$user,[],$user->toArray());
        return redirect()->route('admin.users.show',$user)->with('status','User created.');
    }

    public function show(Request $request,int $user): View
    {
        $this->admin($request); $managedUser=$this->scoped($request,$user); $lookups=$this->lookups($request);
        $permissions=DB::table('role_permissions')->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$managedUser->tenant_id))->where('role_code',$managedUser->role)->orderBy('permission_code')->pluck('permission_code');
        $overrides=DB::table('user_permission_overrides')->where('tenant_id',$managedUser->tenant_id)->where('user_id',$managedUser->id)->orderBy('permission_code')->get();
        return view('navigation.users-show',array_merge($lookups,compact('managedUser','permissions','overrides')));
    }

    public function edit(Request $request,int $user): View { $this->admin($request); return view('navigation.users-form',array_merge($this->lookups($request),['managedUser'=>$this->scoped($request,$user),'mode'=>'edit'])); }

    public function update(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request); $managed=$this->scoped($request,$user); $before=$managed->toArray(); $tenant=$request->user()->tenant_id;
        $data=$request->validate(['name'=>['required','string','max:120'],'email'=>['required','email','max:190',Rule::unique('users','email')->ignore($managed->id)],'role'=>['required',Rule::in(self::ROLES)],'status'=>['required',Rule::in(self::STATUSES)],'organization_id'=>['nullable','integer'],'employee_id'=>['nullable','integer']]);
        if($managed->id===$request->user()->id && ($data['role']!=='ADMIN'||$data['status']!=='ACTIVE')) return back()->withErrors(['status'=>'You cannot remove your own administrator access or deactivate your current account.']);
        $organizationId=$data['organization_id']??null; $employeeId=$data['employee_id']??null;
        if($organizationId) abort_unless(Organization::where('tenant_id',$tenant)->whereKey($organizationId)->exists(),422);
        if($employeeId) abort_unless(Employee::where('tenant_id',$tenant)->whereKey($employeeId)->exists(),422);
        $managed->update(['name'=>$data['name'],'email'=>$data['email'],'role'=>$data['role'],'status'=>$data['status'],'organization_id'=>$organizationId,'employee_id'=>$employeeId]);
        $audit->record('security.user.updated',$managed,$before,$managed->fresh()->toArray());
        return redirect()->route('admin.users.show',$managed)->with('status','User updated.');
    }

    public function status(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request); $managed=$this->scoped($request,$user); $data=$request->validate(['status'=>['required',Rule::in(self::STATUSES)]]);
        if($managed->id===$request->user()->id && $data['status']!=='ACTIVE') return back()->withErrors(['status'=>'You cannot deactivate or suspend your current account.']);
        $before=['status'=>$managed->status]; $managed->update(['status'=>$data['status']]); $audit->record('security.user.status_changed',$managed,$before,['status'=>$managed->status]);
        return back()->with('status','User status updated.');
    }

    public function resetPassword(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request); $managed=$this->scoped($request,$user); $data=$request->validate(['password'=>['required','string','min:8','confirmed']]);
        $managed->update(['password'=>$data['password']]); $audit->record('security.user.password_reset',$managed,[],['reset_by'=>$request->user()->id]);
        return back()->with('status','Password reset successfully.');
    }

    public function permission(Request $request,int $user,AuditService $audit): RedirectResponse
    {
        $this->admin($request); $managed=$this->scoped($request,$user); abort_if($managed->role==='ADMIN',422,'Administrator permissions are implicit.');
        $data=$request->validate(['permission_code'=>['required','string','max:120'],'effect'=>['required',Rule::in(['ALLOW','DENY','INHERIT'])]]);
        $key=['tenant_id'=>$managed->tenant_id,'user_id'=>$managed->id,'permission_code'=>$data['permission_code']];
        if($data['effect']==='INHERIT') DB::table('user_permission_overrides')->where($key)->delete(); else DB::table('user_permission_overrides')->updateOrInsert($key,['allowed'=>$data['effect']==='ALLOW','updated_by'=>$request->user()->id,'created_at'=>now(),'updated_at'=>now()]);
        $audit->record('security.user.permission_override',$managed,[],['permission_code'=>$data['permission_code'],'effect'=>$data['effect']]);
        return back()->with('status','User permission updated.');
    }
}
