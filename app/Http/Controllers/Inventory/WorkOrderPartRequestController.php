<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\{Warehouse,WorkOrder,WorkOrderPartRequest};
use App\Services\Inventory\WorkOrderPartRequestService;
use Illuminate\Http\{RedirectResponse,Request};

class WorkOrderPartRequestController extends Controller
{
    public function store(Request $request, WorkOrder $workOrder, WorkOrderPartRequestService $service): RedirectResponse
    {
        abort_unless($workOrder->tenant_id===auth()->user()->tenant_id,404);
        $data=$request->validate([
            'source_warehouse_id'=>['required','integer'],'destination_warehouse_id'=>['required','integer','different:source_warehouse_id'],
            'priority'=>['required','in:NORMAL,HIGH,CRITICAL,EMERGENCY'],'reason'=>['nullable','string','max:500'],
            'item_id'=>['required','array','min:1'],'item_id.*'=>['required','integer'],
            'quantity'=>['required','array','min:1'],'quantity.*'=>['required','numeric','gt:0'],
        ]);
        $source=Warehouse::findOrFail($data['source_warehouse_id']);$destination=Warehouse::findOrFail($data['destination_warehouse_id']);
        abort_unless($source->tenant_id===auth()->user()->tenant_id && $destination->tenant_id===auth()->user()->tenant_id,404);
        $lines=[];foreach($data['item_id'] as $i=>$itemId)$lines[]=['item_id'=>$itemId,'quantity'=>$data['quantity'][$i]??0];
        $partRequest=$service->create($workOrder,$source,$destination,$lines,$data['priority'],$data['reason']??null);
        return back()->with('status','Part request '.$partRequest->request_no.' submitted to warehouse.');
    }

    public function approve(Request $request, WorkOrderPartRequest $partRequest, WorkOrderPartRequestService $service): RedirectResponse
    {
        abort_unless($partRequest->tenant_id===auth()->user()->tenant_id,404);
        $data=$request->validate(['decision_note'=>['nullable','string','max:1000']]);
        $service->approve($partRequest,$data['decision_note']??null);
        return back()->with('status','Part request approved and stock reserved.');
    }

    public function reject(Request $request, WorkOrderPartRequest $partRequest, WorkOrderPartRequestService $service): RedirectResponse
    {
        abort_unless($partRequest->tenant_id===auth()->user()->tenant_id,404);
        $data=$request->validate(['decision_note'=>['required','string','max:1000']]);
        $service->reject($partRequest,$data['decision_note']);
        return back()->with('status','Part request rejected.');
    }

    public function pick(WorkOrderPartRequest $partRequest, WorkOrderPartRequestService $service): RedirectResponse
    {
        abort_unless($partRequest->tenant_id===auth()->user()->tenant_id,404);
        $service->pick($partRequest);
        return back()->with('status','Parts picked and ready for issue.');
    }

    public function issue(WorkOrderPartRequest $partRequest, WorkOrderPartRequestService $service): RedirectResponse
    {
        abort_unless($partRequest->tenant_id===auth()->user()->tenant_id,404);
        $service->issue($partRequest);
        return back()->with('status','Parts issued from source stock and awaiting technician receipt.');
    }

    public function receive(WorkOrderPartRequest $partRequest, WorkOrderPartRequestService $service): RedirectResponse
    {
        abort_unless($partRequest->tenant_id===auth()->user()->tenant_id,404);
        $service->receive($partRequest);
        return back()->with('status','Parts receipt confirmed into destination stock.');
    }
}
