<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\Procurement\GoodsReceiptService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function create(PurchaseOrder $purchaseOrder): View
    {
        abort_unless($purchaseOrder->status === 'APPROVED',422,'Purchase order must be approved before receiving.');
        return view('procurement.goods-receipts.create',['order'=>$purchaseOrder->load('lines.item')]);
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder, GoodsReceiptService $service): RedirectResponse
    {
        $data=$request->validate([
            'receipt_no'=>['required','string','max:50'],'warehouse_code'=>['required','string','max:40'],
            'quantities'=>['required','array'],'quantities.*'=>['nullable','numeric','min:0'],
        ]);
        $receipt=$service->receive($purchaseOrder,$data['receipt_no'],$data['warehouse_code'],$data['quantities']);
        return redirect()->route('procurement.purchase-orders.index')->with('status',"Receipt {$receipt->receipt_no} posted to Inventory and Finance.");
    }
}
