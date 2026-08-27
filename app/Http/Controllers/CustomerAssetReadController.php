<?php

namespace App\Http\Controllers;

use App\Models\{Asset,WorkOrder};
use App\Services\CustomerPortalAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerAssetReadController extends Controller
{
    private function user()
    {
        $user=auth()->user();
        abort_unless($user&&$user->role==='CUSTOMER'&&$user->customer_id,403);
        return $user;
    }

    public function asset(Asset $asset, CustomerPortalAccessService $access): View
    {
        $user=$this->user();abort_unless($access->canSection($user,'assets'),403);abort_unless((int)$asset->customer_id===(int)$user->customer_id,404);$access->assertAsset($user,$asset->id);
        $asset->load(['customer','site','parent','children']);
        $plans=DB::table('maintenance_plans')->where('asset_id',$asset->id)->orderBy('next_due_date')->get();
        $workOrders=WorkOrder::where('asset_id',$asset->id)->latest('created_at')->limit(100)->get();
        $failures=DB::table('asset_failures')->where('asset_id',$asset->id)->orderByDesc('failed_at')->get();
        $documents=DB::table('asset_documents')->where('asset_id',$asset->id)->whereIn('document_type',['OM_MANUAL','DATASHEET','WARRANTY','INSTALLATION_REPORT','COMMISSIONING_REPORT','CERTIFICATE','PHOTO'])->latest()->get();
        $specifications=DB::table('asset_specifications')->where('asset_id',$asset->id)->orderBy('spec_label')->get();
        $attachments=DB::table('work_order_attachments')->whereIn('work_order_id',$workOrders->pluck('id'))->whereIn('attachment_type',['PHOTO','BEFORE_PHOTO','AFTER_PHOTO','THERMAL_IMAGE'])->latest()->limit(60)->get();
        $failureCount=$failures->count();$totalDowntime=(int)$failures->sum('downtime_minutes');$mttr=$failureCount?round($totalDowntime/$failureCount,1):null;$chronological=$failures->sortBy('failed_at')->values();$intervals=[];
        for($i=1;$i<$chronological->count();$i++){if(!$chronological[$i-1]->restored_at)continue;$start=\Carbon\Carbon::parse($chronological[$i-1]->restored_at);$end=\Carbon\Carbon::parse($chronological[$i]->failed_at);if($end->greaterThan($start))$intervals[]=$start->diffInMinutes($end);}
        $mtbf=count($intervals)?round(array_sum($intervals)/count($intervals),1):null;$completed=$workOrders->where('status','COMPLETED')->count();$pm=$workOrders->where('maintenance_type','PREVENTIVE');$pmCompliance=$pm->count()?round($pm->where('status','COMPLETED')->count()/$pm->count()*100,1):100;$metrics=compact('failureCount','totalDowntime','mttr','mtbf','completed','pmCompliance');
        return view('customer.asset-detail',compact('asset','plans','workOrders','failures','documents','specifications','attachments','metrics'));
    }

    public function workOrder(WorkOrder $workOrder, CustomerPortalAccessService $access): View
    {
        $user=$this->user();abort_unless($access->canSection($user,'work-orders'),403);$workOrder->load(['asset.customer','asset.site','plan','contract']);abort_unless($workOrder->asset&&(int)$workOrder->asset->customer_id===(int)$user->customer_id,404);$access->assertAsset($user,$workOrder->asset->id);
        if($workOrder->service_contract_id)$access->assertContract($user,(int)$workOrder->service_contract_id);
        $tasks=collect();if($workOrder->maintenance_plan_id){$tasks=DB::table('maintenance_plan_tasks')->leftJoin('work_order_checklist_results',function($join)use($workOrder){$join->on('work_order_checklist_results.maintenance_plan_task_id','=','maintenance_plan_tasks.id')->where('work_order_checklist_results.work_order_id','=',$workOrder->id);})->where('maintenance_plan_tasks.maintenance_plan_id',$workOrder->maintenance_plan_id)->orderBy('maintenance_plan_tasks.sort_order')->select('maintenance_plan_tasks.task_title','maintenance_plan_tasks.response_type','maintenance_plan_tasks.unit','work_order_checklist_results.result_status','work_order_checklist_results.numeric_value','work_order_checklist_results.text_value','work_order_checklist_results.completed_at')->get();}
        $attachments=DB::table('work_order_attachments')->where('work_order_id',$workOrder->id)->whereIn('attachment_type',['PHOTO','BEFORE_PHOTO','AFTER_PHOTO','THERMAL_IMAGE'])->latest()->get();
        $materials=DB::table('maintenance_materials')->join('items','items.id','=','maintenance_materials.item_id')->where('maintenance_materials.work_order_id',$workOrder->id)->select('maintenance_materials.quantity','items.item_code','items.name as item_name','items.uom')->get();
        $failures=DB::table('asset_failures')->where('work_order_id',$workOrder->id)->select('failure_mode','failure_effect','corrective_action','failed_at','restored_at','downtime_minutes','severity','status')->get();
        return view('customer.work-order-detail',compact('workOrder','tasks','attachments','materials','failures'));
    }

    public function attachment(WorkOrder $workOrder,int $attachment, CustomerPortalAccessService $access)
    {
        $user=$this->user();$workOrder->load('asset');abort_unless($workOrder->asset&&(int)$workOrder->asset->customer_id===(int)$user->customer_id,404);$access->assertAsset($user,$workOrder->asset->id);
        $file=DB::table('work_order_attachments')->where('id',$attachment)->where('work_order_id',$workOrder->id)->whereIn('attachment_type',['PHOTO','BEFORE_PHOTO','AFTER_PHOTO','THERMAL_IMAGE'])->first();abort_unless($file,404);return response()->download(storage_path('app/public/'.$file->file_path),$file->original_name);
    }
}
