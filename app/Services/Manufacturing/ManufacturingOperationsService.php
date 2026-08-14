<?php

namespace App\Services\Manufacturing;

use App\Models\{Bom,Item,ProductionOrder,Routing,WorkCenter};
use App\Services\AuditService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class ManufacturingOperationsService
{
    public function __construct(private StockService $stock, private AuditService $audit) {}

    public function release(ProductionOrder $order, Bom $bom, Routing $routing, string $warehouse): ProductionOrder
    {
        if ($order->status !== 'CREATED') throw ValidationException::withMessages(['production_order'=>'Only CREATED production orders can be released.']);
        if ($bom->status !== 'ACTIVE' || $routing->status !== 'ACTIVE') throw ValidationException::withMessages(['master_data'=>'BOM and routing must be active.']);
        if ((int)$bom->product_item_id !== (int)$routing->product_item_id) throw ValidationException::withMessages(['master_data'=>'BOM and routing must reference the same product.']);

        return DB::transaction(function () use ($order,$bom,$routing,$warehouse) {
            $bom->load('lines'); $routing->load('operations');
            if ($bom->lines->isEmpty()) throw ValidationException::withMessages(['bom'=>'BOM requires at least one component.']);
            $planned=(float)$order->planned_quantity;
            $materialCost=0;
            foreach ($bom->lines as $line) {
                $qty=$planned*(float)$line->quantity_per;
                DB::table('production_materials')->insert([
                    'tenant_id'=>Auth::user()->tenant_id,'production_order_id'=>$order->id,'item_id'=>$line->component_item_id,
                    'planned_quantity'=>$qty,'issued_quantity'=>0,'unit_cost'=>$line->standard_unit_cost,'created_at'=>now(),'updated_at'=>now(),
                ]);
                $materialCost += $qty*(float)$line->standard_unit_cost;
            }
            $laborCost=$routing->operations->sum(function ($op) {
                $rate=(float)(WorkCenter::whereKey($op->work_center_id)->value('hourly_rate') ?? 0);
                return $rate*(float)$op->standard_hours;
            });
            $before=$order->toArray();
            $order->update(['item_id'=>$bom->product_item_id,'bom_id'=>$bom->id,'routing_id'=>$routing->id,'warehouse_code'=>$warehouse,'standard_cost'=>round($materialCost+$laborCost,2),'status'=>'RELEASED']);
            $this->audit->record('manufacturing.production_order.released',$order,$before,$order->fresh()->toArray());
            return $order->fresh();
        });
    }

    public function issueMaterials(ProductionOrder $order): void
    {
        if ($order->status !== 'RELEASED') throw ValidationException::withMessages(['production_order'=>'Materials can only be issued to RELEASED orders.']);
        DB::transaction(function () use ($order) {
            $rows=DB::table('production_materials')->where('tenant_id',Auth::user()->tenant_id)->where('production_order_id',$order->id)->get();
            foreach ($rows as $row) {
                $remaining=(float)$row->planned_quantity-(float)$row->issued_quantity;
                if ($remaining <= 0) continue;
                $item=Item::findOrFail($row->item_id);
                $this->stock->move($item,$order->warehouse_code,'ISSUE',$remaining,"production:{$order->id}:material:{$row->id}",'ProductionOrder',$order->id);
                DB::table('production_materials')->where('id',$row->id)->update(['issued_quantity'=>$row->planned_quantity,'updated_at'=>now()]);
            }
            $this->audit->record('manufacturing.materials.issued',$order,[],['production_order_id'=>$order->id]);
        });
    }

    public function confirm(ProductionOrder $order, array $data): void
    {
        if ($order->status !== 'RELEASED') throw ValidationException::withMessages(['production_order'=>'Only RELEASED orders can be confirmed.']);
        DB::table('production_confirmations')->insert([
            'tenant_id'=>Auth::user()->tenant_id,'production_order_id'=>$order->id,'work_center_id'=>$data['work_center_id'] ?? null,
            'hours'=>$data['hours'],'good_quantity'=>$data['good_quantity'],'scrap_quantity'=>$data['scrap_quantity'] ?? 0,
            'created_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->audit->record('manufacturing.production.confirmed',$order,[],$data);
    }

    public function inspect(ProductionOrder $order, string $result, ?string $notes): void
    {
        if (!in_array($result,['PASS','FAIL'],true)) throw ValidationException::withMessages(['result'=>'Unsupported inspection result.']);
        DB::table('quality_inspections')->insert(['tenant_id'=>Auth::user()->tenant_id,'production_order_id'=>$order->id,'result'=>$result,'notes'=>$notes,'inspected_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);
        $this->audit->record('manufacturing.quality.inspected',$order,[],compact('result','notes'));
    }

    public function complete(ProductionOrder $order, float $quantity): ProductionOrder
    {
        if ($order->status !== 'RELEASED') throw ValidationException::withMessages(['production_order'=>'Only RELEASED orders can be completed.']);
        if (!DB::table('quality_inspections')->where('tenant_id',Auth::user()->tenant_id)->where('production_order_id',$order->id)->where('result','PASS')->exists())
            throw ValidationException::withMessages(['quality'=>'A passing quality inspection is required before completion.']);
        if ($quantity <= 0 || $quantity > (float)$order->planned_quantity) throw ValidationException::withMessages(['produced_quantity'=>'Produced quantity is invalid.']);

        return DB::transaction(function () use ($order,$quantity) {
            $product=Item::findOrFail($order->item_id);
            $this->stock->move($product,$order->warehouse_code,'RECEIPT',$quantity,"production:{$order->id}:finished",'ProductionOrder',$order->id);
            $material=(float)DB::table('production_materials')->where('production_order_id',$order->id)->selectRaw('COALESCE(SUM(issued_quantity * unit_cost),0) total')->value('total');
            $labor=(float)DB::table('production_confirmations as c')->leftJoin('work_centers as w','w.id','=','c.work_center_id')->where('c.production_order_id',$order->id)->selectRaw('COALESCE(SUM(c.hours * COALESCE(w.hourly_rate,0)),0) total')->value('total');
            $actual=round($material+$labor,2); $variance=round($actual-(float)$order->standard_cost,2);
            $before=$order->toArray();
            $order->update(['produced_quantity'=>$quantity,'actual_cost'=>$actual,'cost_variance'=>$variance,'status'=>'COMPLETED']);
            $this->audit->record('manufacturing.production_order.completed',$order,$before,$order->fresh()->toArray());
            return $order->fresh();
        });
    }
}
