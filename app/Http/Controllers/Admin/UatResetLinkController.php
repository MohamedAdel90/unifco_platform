<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\{AuditService,AuthorizationService,InvitationService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;

class UatResetLinkController extends Controller
{
    public function __invoke(Request $request,int $user,InvitationService $invitations,AuditService $audit): RedirectResponse
    {
        app(AuthorizationService::class)->authorize($request->user(),'users.reset_password');
        $managed=User::where('tenant_id',$request->user()->tenant_id)->findOrFail($user);

        abort_if($managed->id===$request->user()->id,422,'UAT reset links cannot be generated for the current administrator account.');
        abort_unless(($managed->user_type??'')==='EXTERNAL',422,'UAT reset links are limited to external test identities.');
        abort_unless(Str::endsWith(strtolower($managed->email),'@unifco.local'),422,'UAT reset links are limited to @unifco.local test identities.');

        ['invitation'=>$invitation,'token'=>$token]=$invitations->issueOneTimeLink($managed,$request->user(),30);

        $before=['locked_at'=>$managed->locked_at,'force_password_change'=>$managed->force_password_change,'session_version'=>$managed->session_version];
        $managed->forceFill([
            'force_password_change'=>true,
            'session_version'=>$managed->session_version+1,
        ])->save();

        if(Schema::hasTable('user_sessions')) {
            DB::table('user_sessions')->where('user_id',$managed->id)->where('status','ACTIVE')->update([
                'status'=>'REVOKED','revoked_at'=>now(),'revoked_by'=>$request->user()->id,
                'revoke_reason'=>'UAT reset link generated','updated_at'=>now(),
            ]);
        }

        $audit->record('security.user.uat_reset_link_generated',$managed,$before,[
            'invitation_id'=>$invitation->id,
            'expires_at'=>$invitation->expires_at->toISOString(),
            'sessions_revoked'=>true,
            'force_password_change'=>true,
        ],reason:'Controlled UAT password recovery without email delivery');

        return back()->with([
            'status'=>'One-time UAT reset link generated. It expires in 30 minutes and is shown only on this response.',
            'uat_reset_url'=>route('uat-reset.show',['token'=>$token]),
            'uat_reset_expires_at'=>$invitation->expires_at->toDayDateTimeString(),
        ]);
    }
}
