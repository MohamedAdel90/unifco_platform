<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{EmailTemplate,JobPosition,MasterDataEntry,Organization};
use App\Services\{AuditService,AuthorizationService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemCatalogController extends Controller
{
    public function __construct(private AuthorizationService $authorization) {}

    public function organization(Request $request): View
    {
        $this->authorization->authorize($request->user(),'organization.manage'); $tenant=$request->user()->tenant_id;
        return view('admin.system.organization',[
            'organizations'=>Organization::where('tenant_id',$tenant)->orderBy('name')->get(),
            'positions'=>JobPosition::where('tenant_id',$tenant)->orderBy('department')->orderBy('title')->get(),
        ]);
    }

    public function storeOrganization(Request $request,AuditService $audit): RedirectResponse
    {
        $this->authorization->authorize($request->user(),'organization.manage'); $tenant=$request->user()->tenant_id;
        $data=$request->validate(['code'=>['required','alpha_dash','max:40',Rule::unique('organizations','code')->where('tenant_id',$tenant)],'name'=>['required','string','max:160']]);
        $row=Organization::create([...$data,'tenant_id'=>$tenant,'status'=>'ACTIVE']);
        $audit->record('system.organization.created',$row,[],$row->toArray(),reason:'Organization structure administration');
        return back()->with('status','Organization created.');
    }

    public function storePosition(Request $request,AuditService $audit): RedirectResponse
    {
        $this->authorization->authorize($request->user(),'organization.manage'); $tenant=$request->user()->tenant_id;
        $data=$request->validate(['organization_id'=>['nullable','integer'],'code'=>['required','alpha_dash','max:40',Rule::unique('job_positions','code')->where('tenant_id',$tenant)],'department'=>['required','string','max:120'],'title'=>['required','string','max:160']]);
        if(!empty($data['organization_id'])) Organization::where('tenant_id',$tenant)->findOrFail($data['organization_id']);
        $row=JobPosition::create([...$data,'tenant_id'=>$tenant,'status'=>'ACTIVE']);
        $audit->record('system.job_position.created',$row,[],$row->toArray(),reason:'Organization structure administration');
        return back()->with('status','Job title created.');
    }

    public function masterData(Request $request): View
    {
        $this->authorization->authorize($request->user(),'master_data.manage'); $tenant=$request->user()->tenant_id;
        $query=MasterDataEntry::where('tenant_id',$tenant)->orderBy('type')->orderBy('name_en');
        $query->when($request->filled('type'),fn($q)=>$q->where('type',$request->string('type')));
        return view('admin.system.master-data',['entries'=>$query->paginate(50)->withQueryString()]);
    }

    public function storeMasterData(Request $request,AuditService $audit): RedirectResponse
    {
        $this->authorization->authorize($request->user(),'master_data.manage'); $tenant=$request->user()->tenant_id;
        $data=$request->validate(['type'=>['required',Rule::in(MasterDataEntry::TYPES)],'code'=>['required','alpha_dash','max:80'],'name_ar'=>['nullable','string','max:160'],'name_en'=>['required','string','max:160'],'description'=>['nullable','string','max:1000']]);
        abort_if(MasterDataEntry::where('tenant_id',$tenant)->where('type',$data['type'])->where('code',strtoupper($data['code']))->exists(),422,'This master-data code already exists.');
        $row=MasterDataEntry::create([...$data,'tenant_id'=>$tenant,'code'=>strtoupper($data['code']),'is_active'=>true]);
        $audit->record('system.master_data.created',$row,[],$row->toArray(),reason:'Administrative master data');
        return back()->with('status','Master data entry created.');
    }

    public function masterDataStatus(Request $request,int $entry,AuditService $audit): RedirectResponse
    {
        $this->authorization->authorize($request->user(),'master_data.manage');
        $row=MasterDataEntry::where('tenant_id',$request->user()->tenant_id)->findOrFail($entry); $before=$row->toArray();
        $data=$request->validate(['is_active'=>['required','boolean'],'reason'=>['required','string','max:500']]);
        $row->update(['is_active'=>(bool)$data['is_active']]); $audit->record('system.master_data.status_changed',$row,$before,$row->fresh()->toArray(),reason:$data['reason']);
        return back()->with('status','Master data status updated.');
    }

    public function emailTemplates(Request $request): View
    {
        $this->authorization->authorize($request->user(),'email_templates.manage');
        return view('admin.system.email-templates',['templates'=>EmailTemplate::where('tenant_id',$request->user()->tenant_id)->orderBy('code')->get()]);
    }

    public function storeEmailTemplate(Request $request,AuditService $audit): RedirectResponse
    {
        $this->authorization->authorize($request->user(),'email_templates.manage'); $tenant=$request->user()->tenant_id;
        $data=$request->validate(['code'=>['required','alpha_dash','max:100'],'subject_ar'=>['nullable','string','max:255'],'subject_en'=>['required','string','max:255'],'body_ar'=>['nullable','string','max:20000'],'body_en'=>['required','string','max:20000']]);
        $row=EmailTemplate::updateOrCreate(['tenant_id'=>$tenant,'code'=>strtoupper($data['code'])],[
            'subject_ar'=>$data['subject_ar']??null,'subject_en'=>$data['subject_en'],
            'body_ar'=>$data['body_ar']??null,'body_en'=>$data['body_en'],'is_active'=>true,
        ]);
        $audit->record('system.email_template.saved',$row,[],$row->toArray(),reason:'Notification template administration');
        return back()->with('status','Email template saved.');
    }
}
