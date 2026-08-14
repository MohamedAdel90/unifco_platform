<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.permissions.index',['rows'=>DB::table('role_permissions')->where('tenant_id',$request->user()->tenant_id)->orderBy('role_code')->orderBy('permission_code')->get()]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$request->validate(['role_code'=>['required','string','max:60'],'permission_code'=>['required','string','max:120']]);
        DB::table('role_permissions')->updateOrInsert(
            ['tenant_id'=>$request->user()->tenant_id,'role_code'=>strtoupper($data['role_code']),'permission_code'=>$data['permission_code']],
            ['created_at'=>now(),'updated_at'=>now()]
        );
        $audit->record('security.permission.granted',null,[],['role_code'=>strtoupper($data['role_code']),'permission_code'=>$data['permission_code']]);
        return back()->with('status','Permission granted.');
    }

    public function destroy(Request $request, int $id, AuditService $audit): RedirectResponse
    {
        $row=DB::table('role_permissions')->where('tenant_id',$request->user()->tenant_id)->where('id',$id)->first();
        abort_unless($row,404); DB::table('role_permissions')->where('id',$id)->delete();
        $audit->record('security.permission.revoked',null,(array)$row,[]);
        return back()->with('status','Permission revoked.');
    }
}
