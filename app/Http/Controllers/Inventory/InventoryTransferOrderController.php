<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\{InventoryTransferOrder,Warehouse};
use App\Services\Inventory\InventoryTransferService;
use Illuminate\Http\{RedirectResponse,Request};

class InventoryTransferOrderController extends Controller
{
    public function store(Request $request, InventoryTransferService $service): RedirectResponse
    {
        $data=$request->validate([
            'from_warehouse_id'=>['required','integer'],'to_warehouse_id'=>['required','integer','different:from_warehouse_id'],
            'purpose'=>['nullable','string','max:160'],'notes'=>['nullable','string','max:1000'],
            'item_id'=>['required','array','min:1'],'item_id.*'=>['required','integer'],
            'quantity'=>['required','array','min:1'],'quantity.*'=>['required','numeric','gt:0'],
        ]);
        $from=Warehouse::findOrFail($data['from_warehouse_id']);$to=Warehouse::findOrFail($data['to_warehouse_id']);
        abort_unless($from->tenant_id===auth()->user()->tenant_id && $to->tenant_id===auth()->user()->tenant_id,404);
        $lines=[];
        foreach($data['item_id'] as $i=>$itemId)$lines[]=['item_id'=>$itemId,'quantity'=>$data['quantity'][$i]??0];
        $transfer=$service->create($from,$to,$lines,$data['purpose']??null,$data['notes']??null);
        return back()->with('status','Transfer '.$transfer->transfer_no.' requested.');
    }

    public function issue(InventoryTransferOrder $transfer, InventoryTransferService $service): RedirectResponse
    {
        abort_unless($transfer->tenant_id===auth()->user()->tenant_id,404);
        $service->issue($transfer);
        return back()->with('status','Transfer issued and is now in transit.');
    }

    public function receive(InventoryTransferOrder $transfer, InventoryTransferService $service): RedirectResponse
    {
        abort_unless($transfer->tenant_id===auth()->user()->tenant_id,404);
        $service->receive($transfer);
        return back()->with('status','Transfer received into destination stock.');
    }
}
