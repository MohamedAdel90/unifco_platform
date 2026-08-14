<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\{CrmLead,CrmOpportunity,CrmQuotation,Customer,Employee,Project,ProjectTask};
use App\Services\AuditService;
use App\Services\CRM\CrmProjectFlowService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CrmOperationsController extends Controller
{
    public function index(): View
    {
        return view('crm.operations.index',[
            'leads'=>CrmLead::latest()->limit(20)->get(),'opportunities'=>CrmOpportunity::latest()->limit(20)->get(),
            'quotations'=>CrmQuotation::latest()->limit(20)->get(),'customers'=>Customer::orderBy('customer_code')->get(),
            'projects'=>Project::latest()->limit(20)->get(),'employees'=>Employee::where('status','ACTIVE')->orderBy('employee_no')->get(),
            'tasks'=>ProjectTask::latest()->limit(30)->get(),'timesheets'=>DB::table('project_timesheets')->latest('id')->limit(30)->get(),
        ]);
    }

    public function storeLead(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['lead_no'=>['required','string','max:50'],'name'=>['required','string','max:160'],'company'=>['nullable','string','max:160'],'email'=>['nullable','email']]);
        $lead=CrmLead::create([...$d,'organization_id'=>$r->user()->organization_id,'created_by'=>$r->user()->id,'status'=>'NEW']);
        $audit->record('crm.lead.created',$lead,[],$lead->toArray()); return back()->with('status','Lead created.');
    }

    public function storeOpportunity(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['opportunity_no'=>['required','string','max:50'],'name'=>['required','string','max:160'],'lead_id'=>['nullable','integer'],'customer_id'=>['nullable','integer'],'stage'=>['required','in:QUALIFICATION,PROPOSAL,NEGOTIATION'],'expected_value'=>['required','numeric','min:0'],'probability'=>['required','integer','between:0,99'],'expected_close'=>['nullable','date']]);
        $o=CrmOpportunity::create([...$d,'organization_id'=>$r->user()->organization_id,'created_by'=>$r->user()->id,'status'=>'OPEN']);
        $audit->record('crm.opportunity.created',$o,[],$o->toArray()); return back()->with('status','Opportunity created.');
    }

    public function storeQuotation(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['opportunity_id'=>['required','integer'],'quotation_no'=>['required','string','max:50'],'quotation_date'=>['required','date'],'currency'=>['required','string','size:3'],'amount'=>['required','numeric','gt:0']]);
        CrmOpportunity::findOrFail($d['opportunity_id']);
        $q=CrmQuotation::create([...$d,'organization_id'=>$r->user()->organization_id,'created_by'=>$r->user()->id,'status'=>'ISSUED']);
        $audit->record('crm.quotation.issued',$q,[],$q->toArray()); return back()->with('status','Quotation issued.');
    }

    public function win(Request $r, CrmOpportunity $opportunity, CrmProjectFlowService $svc): RedirectResponse
    {
        $d=$r->validate(['customer_id'=>['required','integer'],'project_no'=>['required','string','max:50'],'project_name'=>['required','string','max:160'],'planned_start'=>['nullable','date'],'planned_finish'=>['nullable','date','after_or_equal:planned_start'],'budget'=>['nullable','numeric','min:0']]);
        $svc->markWonAndCreateProject($opportunity,$d); return back()->with('status','Opportunity won and project created.');
    }

    public function storeTask(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['project_id'=>['required','integer'],'wbs_code'=>['required','string','max:50'],'name'=>['required','string','max:160'],'planned_start'=>['nullable','date'],'planned_finish'=>['nullable','date','after_or_equal:planned_start'],'budget'=>['required','numeric','min:0']]);
        Project::findOrFail($d['project_id']);
        $task=ProjectTask::create([...$d,'status'=>'PLANNED']); $audit->record('projects.task.created',$task,[],$task->toArray()); return back()->with('status','WBS task created.');
    }

    public function assignResource(Request $r, Project $project, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['employee_id'=>['required','integer'],'role'=>['nullable','string','max:100'],'planned_hours'=>['required','numeric','min:0']]); Employee::findOrFail($d['employee_id']);
        DB::table('project_resource_assignments')->updateOrInsert(['tenant_id'=>$r->user()->tenant_id,'project_id'=>$project->id,'employee_id'=>$d['employee_id']],['role'=>$d['role']??null,'planned_hours'=>$d['planned_hours'],'created_at'=>now(),'updated_at'=>now()]);
        $audit->record('projects.resource.assigned',$project,[], $d); return back()->with('status','Resource assigned.');
    }

    public function postTimesheet(Request $r, Project $project, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['project_task_id'=>['nullable','integer'],'employee_id'=>['required','integer'],'work_date'=>['required','date'],'hours'=>['required','numeric','gt:0','max:24'],'hourly_cost'=>['required','numeric','min:0']]); Employee::findOrFail($d['employee_id']);
        if (!empty($d['project_task_id'])) ProjectTask::where('project_id',$project->id)->findOrFail($d['project_task_id']);
        DB::transaction(function () use ($r,$project,$d,$audit) {
            $cost=round((float)$d['hours']*(float)$d['hourly_cost'],2);
            DB::table('project_timesheets')->insert(['tenant_id'=>$r->user()->tenant_id,'project_id'=>$project->id,'project_task_id'=>$d['project_task_id']??null,'employee_id'=>$d['employee_id'],'work_date'=>$d['work_date'],'hours'=>$d['hours'],'hourly_cost'=>$d['hourly_cost'],'status'=>'POSTED','created_by'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()]);
            $project->increment('actual_cost',$cost); $audit->record('projects.timesheet.posted',$project,[],['hours'=>$d['hours'],'cost'=>$cost]);
        });
        return back()->with('status','Timesheet posted and project cost updated.');
    }
}
