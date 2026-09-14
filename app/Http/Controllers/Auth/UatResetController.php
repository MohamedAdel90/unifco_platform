<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{User,UserInvitation};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
use Illuminate\View\View;

class UatResetController extends Controller
{
    public function show(string $token): View
    {
        $invitation=$this->resolve($token);

        return view('auth.uat-reset',[
            'token'=>$token,
            'email'=>$invitation->email,
            'expiresAt'=>$invitation->expires_at,
        ]);
    }

    public function update(Request $request,string $token,AuditService $audit): RedirectResponse
    {
        $invitation=$this->resolve($token);
        $data=$request->validate([
            'password'=>['required','string','min:10','confirmed'],
        ]);
        $user=User::whereKey($invitation->user_id)->where('tenant_id',$invitation->tenant_id)->firstOrFail();

        DB::transaction(function() use($audit,$user,$invitation,$data){
            $user->forceFill([
                'password'=>$data['password'],
                'force_password_change'=>false,
                'locked_at'=>null,
                'session_version'=>$user->session_version+1,
            ])->save();

            $invitation->forceFill(['status'=>'ACCEPTED','accepted_at'=>now()])->save();
            UserInvitation::where('user_id',$user->id)->where('id','<>',$invitation->id)->where('status','PENDING')->update(['status'=>'REVOKED','revoked_at'=>now()]);

            if(Schema::hasTable('user_sessions')) {
                DB::table('user_sessions')->where('user_id',$user->id)->where('status','ACTIVE')->update([
                    'status'=>'REVOKED','revoked_at'=>now(),'revoke_reason'=>'UAT one-time password reset','updated_at'=>now(),
                ]);
            }

            $audit->record('security.user.uat_password_set',$user,[],[
                'invitation_id'=>$invitation->id,
                'sessions_revoked'=>true,
                'password_change_required'=>false,
            ],reason:'UAT one-time reset link completed');
        });

        return redirect()->route('login')->with('status','Password set successfully. You can now sign in.');
    }

    private function resolve(string $token): UserInvitation
    {
        abort_unless(strlen($token)>=40,404);
        $invitation=UserInvitation::where('token_hash',hash('sha256',$token))->where('status','PENDING')->firstOrFail();
        abort_if(!$invitation->expires_at || $invitation->expires_at->isPast(),410,'This reset link has expired.');
        abort_unless(Str::endsWith(strtolower($invitation->email),'@unifco.local'),403,'UAT reset links are limited to local test identities.');
        return $invitation;
    }
}
