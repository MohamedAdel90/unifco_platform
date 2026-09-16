<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\{AuditService, AuthorizationService, SecurityEventService, UserSessionService};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const WORKFLOW_ROLES = [
        'MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE','PROJECT_MANAGER','CEO',
    ];

    public function create(): View { return view('auth.login'); }

    public function store(Request $request, UserSessionService $sessions, SecurityEventService $security, AuditService $audit, AuthorizationService $authorization): RedirectResponse
    {
        $credentials=$request->validate(['email'=>['required','email'],'password'=>['required']]);
        $candidate=User::where('email',strtolower($credentials['email']))->first();
        if(!Auth::attempt($credentials,$request->boolean('remember'))){
            $security->record('FAILED_LOGIN','WARNING',$candidate,['email'=>strtolower($credentials['email'])]);
            if($candidate && \Illuminate\Support\Facades\Schema::hasTable('security_events')) {
                $failures=\Illuminate\Support\Facades\DB::table('security_events')->where('user_id',$candidate->id)->where('event_type','FAILED_LOGIN')->where('occurred_at','>=',now()->subMinutes(15))->count();
                if($failures>=5 && !$candidate->locked_at){
                    $candidate->update(['locked_at'=>now(),'session_version'=>$candidate->session_version+1]);
                    $security->record('ACCOUNT_LOCKED','HIGH',$candidate,['reason'=>'Repeated failed login','window_minutes'=>15]);
                }
            }
            return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $user=Auth::user();
        if($user->status!=='ACTIVE'||$user->locked_at){
            $security->record('BLOCKED_LOGIN','WARNING',$user,['status'=>$user->status,'locked'=>(bool)$user->locked_at]);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403,'User account is unavailable.');
        }
        $user->forceFill(['last_login_at'=>now()])->save();
        $request->session()->put('account_session_version',(int)$user->session_version);
        $sessions->register($user,$request);
        $audit->record('security.user.login',$user,[],['session_status'=>'ACTIVE']);

        if($authorization->allows($user,'system.dashboard.view')) return redirect()->intended(route('system-admin.dashboard'));
        if($user->role==='CUSTOMER') return redirect()->intended(route('customer.portal'));
        if($user->hasRole('OPERATIONS_MANAGER')) return redirect()->intended(route('operations-manager.dashboard'));
        if($user->role==='STOREKEEPER') return redirect()->intended(route('inventory.warehouse.index'));
        if(in_array($user->role,self::WORKFLOW_ROLES,true)) return redirect()->intended(route('workflow.workspace'));
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, UserSessionService $sessions, AuditService $audit): RedirectResponse
    {
        if($request->user()) $audit->record('security.user.logout',$request->user(),[],['session_status'=>'LOGGED_OUT']);
        $sessions->end($request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
