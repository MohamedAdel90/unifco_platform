<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductionOrderController extends Controller
{
    public function index(): View { return view('manufacturing.production-orders.index',['orders'=>ProductionOrder::orderByDesc('id')->paginate(25)]); }
    public function create(): View { return view('manufacturing.production-orders.form',['order'=>new ProductionOrder()]); }
    public function edit(ProductionOrder $productionOrder): View { return view('manufacturing.production-orders.form',['order'=>$productionOrder]); }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $order=ProductionOrder::create([...$this->validated($request),'organization_id'=>Auth::user()->organization_id,'produced_quantity'=>0,'status'=>'CREATED']);
        $audit->record('manufacturing.production_order.created',$order,[],$order->toArray());
        return redirect()->route('manufacturing.production-orders.index')->with('status','Production order created.');
    }

    public function update(Request $request, ProductionOrder $productionOrder, AuditService $audit): RedirectResponse
    {
        if ($productionOrder->status !== 'CREATED') throw ValidationException::withMessages(['production_order'=>'Only CREATED production orders can be edited.']);
        $before=$productionOrder->toArray(); $productionOrder->update($this->validated($request,$productionOrder));
        $audit->record('manufacturing.production_order.updated',$productionOrder,$before,$productionOrder->fresh()->toArray());
        return redirect()->route('manufacturing.production-orders.index')->with('status','Production order updated.');
    }

    public function release(ProductionOrder $productionOrder, AuditService $audit): RedirectResponse
    {
        if ($productionOrder->status !== 'CREATED') throw ValidationException::withMessages(['production_order'=>'Only CREATED production orders can be released.']);
        $before=$productionOrder->toArray(); $productionOrder->update(['status'=>'RELEASED']);
        $audit->record('manufacturing.production_order.released',$productionOrder,$before,$productionOrder->fresh()->toArray());
        return back()->with('status','Production order released.');
    }

    public function complete(Request $request, ProductionOrder $productionOrder, AuditService $audit): RedirectResponse
    {
        if ($productionOrder->status !== 'RELEASED') throw ValidationException::withMessages(['production_order'=>'Only RELEASED production orders can be completed.']);
        $data=$request->validate(['produced_quantity'=>['required','numeric','gt:0','lte:'.$productionOrder->planned_quantity]]);
        $before=$productionOrder->toArray();
        $productionOrder->update(['produced_quantity'=>$data['produced_quantity'],'status'=>'COMPLETED']);
        $audit->record('manufacturing.production_order.completed',$productionOrder,$before,$productionOrder->fresh()->toArray());
        return back()->with('status','Production order completed.');
    }

    private function validated(Request $request, ?ProductionOrder $order=null): array
    {
        return $request->validate([
            'order_no'=>['required','string','max:50',Rule::unique('production_orders')->where(fn($q)=>$q->where('tenant_id',Auth::user()->tenant_id))->ignore($order?->id)],
            'product_name'=>['required','string','max:180'],'planned_quantity'=>['required','numeric','gt:0'],
        ]);
    }
}
