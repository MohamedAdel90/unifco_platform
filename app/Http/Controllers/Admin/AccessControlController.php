<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AccessScope,ApprovalAuthority,Role,UserInvitation};
use App\Services\{AuditService,InvitationService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    public function index(Request $request): View
    {
        $tenant=$request->user()->tenant_id;
        return view('admin.access-control.index',[
            'roles'=>Role::where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->withCount('users')->orderBy('code')->get(),
            'scopes'=>AccessScope::where('tenant_id',$tenant)->orderBy('scope_type')->orderBy('name')->get(),
            'authorities'=>ApprovalAuthority::where('tenant_id',$tenant)->latest()->get(),
            'invitations'=>UserInvitation::where('tenant_id',$tenant)->latest()->limit(50)->get(),
            'events'=>DB::table('security_events')->where('tenant_id',$tenant)->latest('occurred_at')->paginate(30),
        ]);
    }

    public function role(Request $request,AuditService $audit): RedirectResponse
    {
        $data=$request->validate(['code'=>['required','alpha_dash','max:80'],'name_en'=>['required','string','max:120'],'name_ar'=>['nullable','string','max:120'],'description'=>['nullable','string','max:1000'],'grants_business_authority'=>['nullable','boolean'],'requires_approval'=>['nullable','boolean']]);
        $role=Role::create(['tenant_id'=>$request->user()->tenant_id,'code'=>strtoupper($data['code']),'name_en'=>$data['name_en'],'name_ar'=>$data['name_ar']??null,'description'=>$data['description']??null,'grants_business_authority'=>$request->boolean('grants_business_authority'),'requires_approval'=>$request->boolean('requires_approval'),'is_active'=>true]);
        $audit->record('security.role.created',$role,[],$role->toArray(),reason:$data['description']??'New master role');
        return back()->with('status','Master role created.');
    }

    public function scope(Request $request,AuditService $audit): RedirectResponse
    {
        $data=$request->validate(['scope_type'=>['required',Rule::in(AccessScope::TYPES)],'scope_id'=>['nullable','integer'],'name'=>['required','string','max:160'],'reason'=>['required','string','max:500']]);
        if($data['scope_type']!=='GLOBAL') abort_unless(isset($data['scope_id']),422,'A resource ID is required for this scope type.');
        $scope=AccessScope::create(['tenant_id'=>$request->user()->tenant_id,'scope_type'=>$data['scope_type'],'scope_id'=>$data['scope_id']??null,'name'=>$data['name'],'is_active'=>true]);
        $audit->record('security.scope.created',$scope,[],$scope->toArray(),reason:$data['reason']);
        return back()->with('status','Access scope created.');
    }

    public function authority(Request $request,AuditService $audit): RedirectResponse
    {
        $tenant=$request->user()->tenant_id;
        $data=$request->validate(['approval_type'=>['required','string','max:100'],'role_id'=>['required','integer'],'level'=>['required','integer','min:1'],'access_scope_id'=>['nullable','integer'],'amount_limit'=>['nullable','numeric','min:0'],'reason'=>['required','string','max:500']]);
        $role=Role::whereKey($data['role_id'])->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->firstOrFail();
        if(isset($data['access_scope_id'])) AccessScope::where('tenant_id',$tenant)->findOrFail($data['access_scope_id']);
        $authority=ApprovalAuthority::create(['tenant_id'=>$tenant,'approval_type'=>$data['approval_type'],'role_id'=>$role->id,'level'=>$data['level'],'access_scope_id'=>$data['access_scope_id']??null,'amount_limit'=>$data['amount_limit']??null,'is_active'=>true,'configured_by'=>$request->user()->id]);
        $audit->record('security.approval_authority.created',$authority,[],$authority->toArray(),reason:$data['reason']);
        return back()->with('status','Approval authority configured. The administrator does not inherit it.');
    }

    public function revokeInvitation(Request $request,int $invitation,AuditService $audit): RedirectResponse
    {
        $row=UserInvitation::where('tenant_id',$request->user()->tenant_id)->findOrFail($invitation); $before=$row->toArray();
        $row->update(['status'=>'REVOKED','revoked_at'=>now()]);
        $audit->record('security.invitation.revoked',$row,$before,$row->fresh()->toArray(),reason:'Administrator revoked invitation');
        return back()->with('status','Invitation revoked.');
    }

    public function resendInvitation(Request $request,int $invitation,AuditService $audit,InvitationService $invitations): RedirectResponse
    {
        $old=UserInvitation::where('tenant_id',$request->user()->tenant_id)->findOrFail($invitation);
        $user=$old->user_id ? \App\Models\User::where('tenant_id',$old->tenant_id)->findOrFail($old->user_id) : null;
        abort_unless($user,422,'The invitation is not linked to an active user record.');
        $new=$invitations->issue($user,$request->user());
        $audit->record('security.invitation.resent',$user,['invitation_id'=>$old->id],['invitation_id'=>$new->id,'expires_at'=>$new->expires_at->toISOString()],reason:'Administrator resent invitation');
        return back()->with('status','Invitation resent.');
    }
}
