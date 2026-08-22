<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\{WorkOrderPartRequest,WorkOrderPartRequestLine};
use App\Services\Inventory\WorkOrderPartConsumptionService;
use Illuminate\Http\{RedirectResponse,Request};

class WorkOrderPartConsumptionController extends Controller
{
    public function consume(Request $request, WorkOrderPartRequest $partRequest, WorkOrderPartRequestLine $line, WorkOrderPartConsumptionService $service): RedirectResponse
    {
        abort_unless($partRequest->tenant_id===auth()->user()->tenant_id,404);
        $data=$request->validate([
            'quantity'=>['required','numeric','gt:0'],
            'removed_item_id'=>['nullable','integer','exists:items,id'],
            'removed_serial'=>['nullable','string','max:160'],
            'removed_disposition'=>['nullable','in:SCRAP,REPAIRABLE,RETURN_TO_VENDOR,WARRANTY,WORKSHOP,OTHER'],
            'notes'=>['nullable','string','max:2000'],
        ]);
        $service->consume($partRequest,$line,(float)$data['quantity'],$data);
        return back()->with('status','Part consumed on asset and recorded in Asset 360.');
    }

    public function returnUnused(Request $request, WorkOrderPartRequest $partRequest, WorkOrderPartRequestLine $line, WorkOrderPartConsumptionService $service): RedirectResponse
    {
        abort_unless($partRequest->tenant_id===auth()->user()->tenant_id,404);
        $data=$request->validate(['quantity'=>['required','numeric','gt:0'],'reason'=>['nullable','string','max:500']]);
        $service->returnUnused($partRequest,$line,(float)$data['quantity'],$data['reason']??null);
        return back()->with('status','Unused part returned to source warehouse.');
    }
}
