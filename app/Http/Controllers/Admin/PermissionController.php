<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Permission,Role};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $tenant=$request->user()->tenant_id;
        return view('admin.permissions.index',[
            'rows'=>DB::table('role_permissions')->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->orderBy('role_code')->orderBy('permission_code')->get(),
            'roles'=>Role::where('is_active',true)->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->orderBy('code')->get(),
            'permissions'=>Permission::orderBy('module')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$request->validate(['role_code'=>['required','string','max:80'],'permission_code'=>['required','string','max:140'],'effect'=>['required',Rule::in(['ALLOW','DENY'])],'reason'=>['required','string','max:500']]);
        $tenant=$request->user()->tenant_id; $roleCode=strtoupper($data['role_code']);
        $role=Role::where('code',$roleCode)->where(fn($q)=>$q->where('tenant_id',$tenant)->orWhereNull('tenant_id'))->orderByRaw('tenant_id is null')->firstOrFail();
        $permission=Permission::where('code',$data['permission_code'])->firstOrFail();
        abort_if($role->is_system_role && $permission->is_business_authority && $data['effect']==='ALLOW',422,'Business authority must be granted through a separate business role.');
        DB::table('role_permissions')->updateOrInsert(
            ['tenant_id'=>$tenant,'role_code'=>$roleCode,'permission_code'=>$data['permission_code']],
            ['role_id'=>$role->id,'permission_id'=>$permission->id,'effect'=>$data['effect'],'granted_by'=>$request->user()->id,'created_at'=>now(),'updated_at'=>now()]
        );
        $audit->record('security.role_permission.changed',null,[],['role_code'=>$roleCode,'permission_code'=>$data['permission_code'],'effect'=>$data['effect']],reason:$data['reason']);
        return back()->with('status','Role permission updated.');
    }

    public function destroy(Request $request, int $id, AuditService $audit): RedirectResponse
    {
        $row=DB::table('role_permissions')->where('tenant_id',$request->user()->tenant_id)->where('id',$id)->first();
        abort_unless($row,404); DB::table('role_permissions')->where('id',$id)->delete();
        $audit->record('security.permission.revoked',null,(array)$row,[]);
        return back()->with('status','Permission revoked.');
    }
}
