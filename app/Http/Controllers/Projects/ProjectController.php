<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\{AccessScope,Customer,Project,ProjectUserAssignment,Role,User};
use App\Services\{AuditService,ScopeService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request,ScopeService $scopes): View { return view('projects.projects.index',['projects'=>$scopes->apply(Project::query(),$request->user())->orderBy('project_no')->paginate(25)]); }
    public function create(): View { return view('projects.projects.form',['project'=>new Project(),'customers'=>Customer::where('status','ACTIVE')->orderBy('name')->get()]); }
    public function edit(Project $project): View { return view('projects.projects.form',['project'=>$project,'customers'=>Customer::where('status','ACTIVE')->orderBy('name')->get()]); }


    public function team(Request $request, Project $project, ScopeService $scopes): View
    {
        abort_unless($scopes->allows($request->user(),$project),403);
        $tenant=$request->user()->tenant_id;

        $assignments=ProjectUserAssignment::with(['user.activeRoles','assignedBy'])
            ->where('tenant_id',$tenant)->where('project_id',$project->id)
            ->orderByRaw("CASE WHEN status = 'ACTIVE' THEN 0 ELSE 1 END")
            ->orderBy('project_role')->orderBy('id')->get();

        $users=User::where('tenant_id',$tenant)
            ->where('status','ACTIVE')
            ->where(fn($q)=>$q->whereNull('customer_id')->orWhere('user_type','INTERNAL'))
            ->with('activeRoles')->orderBy('name')->get();

        $roles=Role::where('is_active',true)
            ->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))
            ->whereNotIn('code',['SYSTEM_ADMIN','CUSTOMER_ADMIN','CUSTOMER_SITE_MANAGER','CUSTOMER_FINANCE','CUSTOMER_VIEWER'])
            ->orderBy('name_en')->get()->unique('code')->values();

        return view('projects.projects.team',compact('project','assignments','users','roles'));
    }

    public function assignTeam(Request $request, Project $project, ScopeService $scopes, AuditService $audit): RedirectResponse
    {
        abort_unless($scopes->allows($request->user(),$project),403);
        $tenant=$request->user()->tenant_id;
        $data=$request->validate([
            'user_id'=>['required','integer'],
            'project_role'=>['required','string','max:80'],
            'access_level'=>['required',Rule::in(['PROJECT','ASSIGNED_RECORDS'])],
            'starts_on'=>['nullable','date'],
            'ends_on'=>['nullable','date','after_or_equal:starts_on'],
            'reason'=>['nullable','string','max:500'],
        ]);

        $user=User::where('tenant_id',$tenant)->where('status','ACTIVE')->findOrFail($data['user_id']);
        abort_if($user->customer_id || $user->user_type==='EXTERNAL',422,'Customer accounts cannot be assigned as internal project team members.');

        $role=Role::where('code',$data['project_role'])->where('is_active',true)
            ->where(fn($q)=>$q->whereNull('tenant_id')->orWhere('tenant_id',$tenant))->firstOrFail();
        abort_unless($user->activeRoles()->where('roles.code',$role->code)->exists(),422,'Assign the selected Master Role to the user before assigning that role inside the project.');

        $before=ProjectUserAssignment::where('project_id',$project->id)->where('user_id',$user->id)->first()?->toArray() ?? [];
        $assignment=ProjectUserAssignment::updateOrCreate(
            ['project_id'=>$project->id,'user_id'=>$user->id],
            [
                'tenant_id'=>$tenant,'project_role'=>$role->code,'access_level'=>$data['access_level'],
                'starts_on'=>$data['starts_on']??null,'ends_on'=>$data['ends_on']??null,'status'=>'ACTIVE',
                'assigned_by'=>$request->user()->id,'reason'=>$data['reason']??null,
            ]
        );

        $scope=AccessScope::firstOrCreate(
            ['tenant_id'=>$tenant,'scope_type'=>'PROJECT','scope_id'=>$project->id],
            ['name'=>$project->project_no.' · '.$project->name,'is_active'=>true]
        );
        $existing=DB::table('user_scopes')->where('user_id',$user->id)->where('access_scope_id',$scope->id)->first();
        if(!$existing){
            DB::table('user_scopes')->insert([
                'tenant_id'=>$tenant,'user_id'=>$user->id,'access_scope_id'=>$scope->id,
                'source'=>'PROJECT_TEAM','granted_by'=>$request->user()->id,
                'expires_at'=>$data['ends_on']??null,'reason'=>'Project team assignment: '.$role->code,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        } elseif($existing->source==='PROJECT_TEAM') {
            DB::table('user_scopes')->where('id',$existing->id)->update([
                'expires_at'=>$data['ends_on']??null,'granted_by'=>$request->user()->id,
                'reason'=>'Project team assignment: '.$role->code,'updated_at'=>now(),
            ]);
        }

        $audit->record('projects.team.assigned',$assignment,$before,$assignment->fresh()->toArray(),reason:$data['reason']??'Project team assignment');
        return back()->with('status','Project team assignment saved.');
    }

    public function removeTeam(Request $request, Project $project, ProjectUserAssignment $assignment, ScopeService $scopes, AuditService $audit): RedirectResponse
    {
        abort_unless($scopes->allows($request->user(),$project),403);
        abort_unless($assignment->project_id===$project->id && $assignment->tenant_id===$request->user()->tenant_id,404);

        $before=$assignment->toArray();
        $assignment->update(['status'=>'INACTIVE','ends_on'=>$assignment->ends_on ?: today()]);

        $scope=AccessScope::where('tenant_id',$assignment->tenant_id)->where('scope_type','PROJECT')->where('scope_id',$project->id)->first();
        if($scope){
            DB::table('user_scopes')->where('user_id',$assignment->user_id)->where('access_scope_id',$scope->id)
                ->where('source','PROJECT_TEAM')->delete();
        }

        $audit->record('projects.team.removed',$assignment,$before,$assignment->fresh()->toArray(),reason:'Project team assignment removed');
        return back()->with('status','Project team member removed from active access.');
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $project=Project::create([...$this->validated($request),'organization_id'=>Auth::user()->organization_id,'status'=>'DRAFT']);
        $audit->record('projects.project.created',$project,[],$project->toArray());
        return redirect()->route('projects.projects.index')->with('status','Project created.');
    }

    public function update(Request $request, Project $project, AuditService $audit): RedirectResponse
    {
        $before=$project->toArray(); $project->update($this->validated($request,$project));
        $audit->record('projects.project.updated',$project,$before,$project->fresh()->toArray());
        return redirect()->route('projects.projects.index')->with('status','Project updated.');
    }

    public function activate(Project $project, AuditService $audit): RedirectResponse
    {
        abort_unless($project->status==='DRAFT',422,'Only DRAFT projects can be activated.');
        $before=$project->toArray(); $project->update(['status'=>'ACTIVE']);
        $audit->record('projects.project.activated',$project,$before,$project->fresh()->toArray());
        return back()->with('status','Project activated.');
    }

    private function validated(Request $request, ?Project $project=null): array
    {
        $tenant=Auth::user()->tenant_id;
        return $request->validate([
            'project_no'=>['required','string','max:50',Rule::unique('projects')->where(fn($q)=>$q->where('tenant_id',$tenant))->ignore($project?->id)],
            'name'=>['required','string','max:180'],
            'customer_id'=>['nullable',Rule::exists('customers','id')->where(fn($q)=>$q->where('tenant_id',$tenant))],
            'planned_start'=>['nullable','date'],'planned_finish'=>['nullable','date','after_or_equal:planned_start'],'budget'=>['required','numeric','min:0'],
        ]);
    }
}
