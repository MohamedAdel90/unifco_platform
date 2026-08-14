<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\{Customer,Project};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View { return view('projects.projects.index',['projects'=>Project::orderBy('project_no')->paginate(25)]); }
    public function create(): View { return view('projects.projects.form',['project'=>new Project(),'customers'=>Customer::where('status','ACTIVE')->orderBy('name')->get()]); }
    public function edit(Project $project): View { return view('projects.projects.form',['project'=>$project,'customers'=>Customer::where('status','ACTIVE')->orderBy('name')->get()]); }

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
