<?php

namespace App\Http\Controllers;

use App\Models\{User,UserInvitation};
use App\Services\{AuditService,AuthorizationService,UserSessionService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(string $token): View
    {
        return view('auth.accept-invitation',['invitation'=>$this->resolve($token),'token'=>$token]);
    }

    public function accept(Request $request,string $token,AuditService $audit,UserSessionService $sessions,AuthorizationService $authorization): RedirectResponse
    {
        $data=$request->validate(['password'=>['required','confirmed',Password::min(12)->letters()->mixedCase()->numbers()]]);
        $invitation=$this->resolve($token);
        $user=User::whereKey($invitation->user_id)->where('tenant_id',$invitation->tenant_id)->firstOrFail();
        DB::transaction(function() use($user,$invitation,$data): void {
            $user->update(['password'=>$data['password'],'status'=>'ACTIVE','force_password_change'=>false,'locked_at'=>null,'session_version'=>$user->session_version+1]);
            $invitation->update(['status'=>'ACCEPTED','accepted_at'=>now()]);
        });
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('account_session_version',(int)$user->fresh()->session_version);
        $sessions->register($user,$request);
        $audit->record('security.invitation.accepted',$invitation,[],['user_id'=>$user->id,'status'=>'ACCEPTED']);
        if($authorization->allows($user,'system.dashboard.view')) return redirect()->route('system-admin.dashboard')->with('status','Your account is ready.');
        if($user->role==='CUSTOMER') return redirect()->route('customer.portal')->with('status','Your account is ready.');
        return redirect()->route('dashboard')->with('status','Your account is ready.');
    }

    private function resolve(string $token): UserInvitation
    {
        return UserInvitation::where('token_hash',hash('sha256',$token))->where('status','PENDING')->where('expires_at','>',now())->firstOrFail();
    }
}
