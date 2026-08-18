<?php

namespace App\Http\Controllers;

use App\Models\{AiInteraction,Asset,Employee,Inspection,InspectionTemplate,PlatformNotification,WorkOrder,WorkOrderAssignment};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FieldServiceController extends Controller
{
    public function operations(): View
    {
        $this->managerOnly();
        return view('field.operations', [
            'workOrders'=>WorkOrder::latest()->limit(100)->get(),
            'employees'=>Employee::where('status','ACTIVE')->orderBy('name')->get(),
            'assignments'=>WorkOrderAssignment::latest('scheduled_start')->limit(100)->get(),
            'templates'=>InspectionTemplate::where('status','ACTIVE')->orderBy('name')->get(),
            'inspections'=>Inspection::latest()->limit(100)->get(),
        ]);
    }

    public function assign(Request $request): RedirectResponse
    {
        $this->managerOnly();
        $data=$request->validate([
            'work_order_id'=>['required','integer','exists:work_orders,id'],
            'employee_id'=>['required','integer','exists:employees,id'],
            'scheduled_start'=>['required','date'],'scheduled_end'=>['nullable','date','after:scheduled_start'],
            'dispatcher_notes'=>['nullable','string','max:2000'],
        ]);
        $assignment=WorkOrderAssignment::updateOrCreate(
            ['work_order_id'=>$data['work_order_id'],'employee_id'=>$data['employee_id']],
            $data+['tenant_id'=>auth()->user()->tenant_id,'organization_id'=>auth()->user()->organization_id,'dispatch_status'=>'DISPATCHED','dispatched_at'=>now()]
        );
        WorkOrder::whereKey($data['work_order_id'])->update(['planned_start'=>$data['scheduled_start']]);
        $technician=DB::table('users')->where('employee_id',$data['employee_id'])->where('status','ACTIVE')->first();
        if($technician){ PlatformNotification::create(['tenant_id'=>auth()->user()->tenant_id,'user_id'=>$technician->id,'type'=>'WORK_ORDER_DISPATCH','title'=>'New work order assignment','message'=>'A work order has been dispatched to you.','action_url'=>route('field.technician')]); }
        return back()->with('status','Work order dispatched to technician.');
    }

    public function technician(): View
    {
        $user=auth()->user();
        abort_unless($user->employee_id,403,'Technician profile is not linked to this user.');
        $assignments=WorkOrderAssignment::where('employee_id',$user->employee_id)->latest('scheduled_start')->limit(100)->get();
        $workOrders=WorkOrder::whereIn('id',$assignments->pluck('work_order_id'))->get()->keyBy('id');
        $inspections=Inspection::where('employee_id',$user->employee_id)->latest()->limit(100)->get();
        return view('field.technician',compact('assignments','workOrders','inspections'));
    }

    public function technicianStatus(Request $request, WorkOrderAssignment $assignment): RedirectResponse
    {
        $user=auth()->user(); abort_unless($user->employee_id && (int)$assignment->employee_id===(int)$user->employee_id,403);
        $data=$request->validate(['status'=>['required','in:ACCEPTED,ARRIVED,IN_PROGRESS,COMPLETED']]);
        $updates=['dispatch_status'=>$data['status']];
        if($data['status']==='ACCEPTED')$updates['accepted_at']=now();
        if($data['status']==='ARRIVED')$updates['arrived_at']=now();
        $assignment->update($updates);
        $wo=WorkOrder::findOrFail($assignment->work_order_id);
        if($data['status']==='IN_PROGRESS')$wo->update(['status'=>'IN_PROGRESS','started_at'=>$wo->started_at?:now()]);
        if($data['status']==='COMPLETED')$wo->update(['status'=>'COMPLETED','completed_at'=>now()]);
        return back()->with('status','Work status updated.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->managerOnly();
        $data=$request->validate(['template_no'=>['required','string','max:60'],'name'=>['required','string','max:255'],'checklist'=>['required','string','max:5000']]);
        $items=array_values(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',$data['checklist']))));
        InspectionTemplate::create(['tenant_id'=>auth()->user()->tenant_id,'organization_id'=>auth()->user()->organization_id,'template_no'=>$data['template_no'],'name'=>$data['name'],'checklist'=>$items,'status'=>'ACTIVE']);
        return back()->with('status','Inspection template created.');
    }

    public function inspect(Request $request): RedirectResponse
    {
        $user=auth()->user(); abort_unless($user->employee_id,403);
        $data=$request->validate(['work_order_id'=>['required','integer','exists:work_orders,id'],'inspection_template_id'=>['nullable','integer','exists:inspection_templates,id'],'findings'=>['nullable','string','max:10000'],'recommendations'=>['nullable','string','max:10000'],'responses'=>['nullable','string','max:10000'],'complete'=>['nullable','boolean']]);
        abort_unless(WorkOrderAssignment::where('work_order_id',$data['work_order_id'])->where('employee_id',$user->employee_id)->exists(),403);
        $wo=WorkOrder::findOrFail($data['work_order_id']);
        Inspection::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'work_order_id'=>$wo->id,'asset_id'=>$wo->asset_id,'inspection_template_id'=>$data['inspection_template_id']??null,'employee_id'=>$user->employee_id,'inspection_no'=>'INS-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),'status'=>$request->boolean('complete')?'COMPLETED':'DRAFT','responses'=>['notes'=>$data['responses']??null],'findings'=>$data['findings']??null,'recommendations'=>$data['recommendations']??null,'completed_at'=>$request->boolean('complete')?now():null]);
        return back()->with('status','Inspection saved.');
    }

    public function assistant(Request $request): View
    {
        $history=AiInteraction::where('user_id',auth()->id())->latest()->limit(20)->get();
        return view('field.assistant',compact('history'));
    }

    public function askAssistant(Request $request): RedirectResponse
    {
        $data=$request->validate(['query'=>['required','string','max:2000']]);
        $q=Str::lower($data['query']);
        $citations=[];$actions=[];
        if(Str::contains($q,['work order','صيانة','maintenance'])){
            $open=WorkOrder::whereNotIn('status',['COMPLETED','CLOSED'])->count();
            $emergency=WorkOrder::where('priority','EMERGENCY')->whereNotIn('status',['COMPLETED','CLOSED'])->count();
            $answer="There are {$open} open work orders, including {$emergency} emergency work orders.";
            $citations[]='work_orders'; $actions[]=['label'=>'Open maintenance workspace','url'=>route('maintenance.work-orders.index')];
        }elseif(Str::contains($q,['inventory','stock','spare','قطع'])){
            $answer='Inventory controls and spare-parts movements are available in the Inventory workspace. Review current stock before committing maintenance parts.';
            $citations[]='inventory_transactions'; $actions[]=['label'=>'Open inventory','url'=>route('inventory.stock.index')];
        }else{
            $answer='I can summarize work orders, maintenance workload, inventory context, and operational status using data you are authorized to access. Sensitive changes remain human-controlled.';
            $citations[]='authorized_operational_data';
        }
        AiInteraction::create(['tenant_id'=>auth()->user()->tenant_id,'organization_id'=>auth()->user()->organization_id,'user_id'=>auth()->id(),'query'=>$data['query'],'response'=>$answer,'citations'=>$citations,'recommended_actions'=>$actions,'result'=>'ANSWERED']);
        return back()->with('status','Assistant response generated.');
    }

    private function managerOnly(): void
    {
        abort_unless(in_array(auth()->user()->role,['ADMIN','MANAGER','SUPERVISOR'],true),403);
    }
}
