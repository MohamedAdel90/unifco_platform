<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{SecurityEvent,User,UserInvitation,UserSession};
use App\Services\{AuditService,AuthorizationService};
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Artisan,DB,Schema};
use Illuminate\View\View;

class SystemOperationsController extends Controller
{
    public function __construct(private AuthorizationService $authorization) {}

    public function sessions(Request $request): View
    {
        $this->authorization->authorize($request->user(),'sessions.view');
        $tenant=$request->user()->tenant_id;
        $query=UserSession::query()->where('tenant_id',$tenant)->with('user')->latest('last_activity_at');
        $query->when($request->filled('status'),fn($q)=>$q->where('status',$request->string('status')))
            ->when($request->filled('user_id'),fn($q)=>$q->where('user_id',$request->integer('user_id')))
            ->when($request->filled('ip'),fn($q)=>$q->where('ip_address','like','%'.trim((string)$request->query('ip')).'%'));
        return view('admin.system.sessions',[
            'sessions'=>$query->paginate(50)->withQueryString(),
            'users'=>User::where('tenant_id',$tenant)->orderBy('name')->get(['id','name','email']),
        ]);
    }

    public function revokeSession(Request $request,int $session,AuditService $audit): RedirectResponse
    {
        $this->authorization->authorize($request->user(),'sessions.manage');
        $row=UserSession::where('tenant_id',$request->user()->tenant_id)->findOrFail($session);
        abort_if($row->session_id===$request->session()->getId(),422,'You cannot revoke your current session.');
        $before=$row->toArray();
        $row->update(['status'=>'REVOKED','revoked_at'=>now(),'revoked_by'=>$request->user()->id,'revoke_reason'=>'Administrator force logout']);
        $audit->record('security.session.revoked',$row,$before,$row->fresh()->toArray(),reason:'Administrator force logout');
        return back()->with('status','Session revoked.');
    }

    public function securityEvents(Request $request): View
    {
        $this->authorization->authorize($request->user(),'security.events.view');
        $tenant=$request->user()->tenant_id;
        $query=SecurityEvent::query()->where('tenant_id',$tenant)->with('user')->latest('occurred_at');
        foreach(['severity','status','event_type'] as $field) {
            $query->when($request->filled($field),fn($q)=>$q->where($field,$request->string($field)));
        }
        $query->when($request->filled('user_id'),fn($q)=>$q->where('user_id',$request->integer('user_id')))
            ->when($request->filled('ip'),fn($q)=>$q->where('ip_address','like','%'.trim((string)$request->query('ip')).'%'))
            ->when($request->filled('from'),fn($q)=>$q->whereDate('occurred_at','>=',$request->date('from')))
            ->when($request->filled('to'),fn($q)=>$q->whereDate('occurred_at','<=',$request->date('to')));
        return view('admin.system.security-events',[
            'events'=>$query->paginate(50)->withQueryString(),
            'users'=>User::where('tenant_id',$tenant)->orderBy('name')->get(['id','name','email']),
            'eventTypes'=>SecurityEvent::where('tenant_id',$tenant)->distinct()->orderBy('event_type')->pluck('event_type'),
        ]);
    }

    public function invitations(Request $request): View
    {
        $this->authorization->authorize($request->user(),'invitations.manage');
        $tenant=$request->user()->tenant_id;
        $query=UserInvitation::query()->where('tenant_id',$tenant)->with('user')->latest();
        $query->when($request->filled('status'),fn($q)=>$q->where('status',$request->string('status')))
            ->when($request->filled('q'),function($q)use($request){$term=trim((string)$request->query('q'));$q->where(fn($x)=>$x->where('email','like',"%{$term}%")->orWhereHas('user',fn($u)=>$u->where('name','like',"%{$term}%")));});
        return view('admin.system.invitations',['invitations'=>$query->paginate(50)->withQueryString()]);
    }

    public function scheduledJobs(Request $request): View
    {
        $this->authorization->authorize($request->user(),'scheduled_jobs.manage');
        $scheduled=collect(app(Schedule::class)->events())->map(fn($event)=>(object)[
            'description'=>$event->description ?: class_basename($event),
            'expression'=>$event->expression,
            'timezone'=>$event->timezone,
        ]);
        return view('admin.system.scheduled-jobs',[
            'scheduled'=>$scheduled,
            'queued'=>Schema::hasTable('jobs')?DB::table('jobs')->latest('id')->paginate(25,['*'],'queued_page'):collect(),
            'batches'=>Schema::hasTable('job_batches')?DB::table('job_batches')->orderByDesc('created_at')->limit(25)->get():collect(),
            'failed'=>Schema::hasTable('failed_jobs')?DB::table('failed_jobs')->latest('failed_at')->paginate(25,['*'],'failed_page'):collect(),
        ]);
    }

    public function retryJob(Request $request,int $job,AuditService $audit): RedirectResponse
    {
        $this->authorization->authorize($request->user(),'scheduled_jobs.manage');
        $failed=DB::table('failed_jobs')->where('id',$job)->first();
        abort_unless($failed,404);
        Artisan::call('queue:retry',['id'=>[$failed->uuid]]);
        $audit->record('system.failed_job.retried',null,(array)$failed,['retry_requested'=>true],reason:'Administrator retry');
        return back()->with('status','Failed job queued for retry.');
    }
}
