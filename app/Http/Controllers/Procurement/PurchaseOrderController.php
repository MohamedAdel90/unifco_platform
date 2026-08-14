<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index(): View { return view('procurement.purchase-orders.index',['orders'=>PurchaseOrder::latest()->paginate(20)]); }

    public function approve(PurchaseOrder $purchaseOrder, AuditService $audit): RedirectResponse
    {
        if ($purchaseOrder->status !== 'DRAFT') throw ValidationException::withMessages(['purchase_order'=>'Only DRAFT purchase orders can be approved.']);
        if ((int)$purchaseOrder->created_by === (int)Auth::id()) throw ValidationException::withMessages(['purchase_order'=>'Segregation of duties: creator cannot approve their own purchase order.']);

        DB::transaction(function () use ($purchaseOrder,$audit) {
            $before=$purchaseOrder->toArray();
            $purchaseOrder->update(['status'=>'APPROVED','approved_by'=>Auth::id()]);
            DB::table('outbox_events')->insert([
                'id'=>(string)Str::uuid(),'tenant_id'=>$purchaseOrder->tenant_id,'event_type'=>'PurchaseOrderApproved',
                'aggregate_type'=>PurchaseOrder::class,'aggregate_id'=>$purchaseOrder->id,'correlation_id'=>(string)Str::uuid(),
                'payload'=>json_encode($purchaseOrder->fresh()->toArray()),'created_at'=>now(),'updated_at'=>now(),
            ]);
            $audit->record('procurement.purchase_order.approved',$purchaseOrder,$before,$purchaseOrder->fresh()->toArray());
        });
        return back()->with('status','Purchase order approved and queued for integration.');
    }
}
