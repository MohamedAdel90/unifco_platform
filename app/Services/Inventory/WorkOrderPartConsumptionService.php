<?php

namespace App\Services\Inventory;

use App\Models\{AssetPartInstallation,Item,WorkOrderPartRequest,WorkOrderPartRequestLine};
use App\Services\AuditService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkOrderPartConsumptionService
{
    public function __construct(private StockService $stock, private AuditService $audit) {}

    public function consume(WorkOrderPartRequest $request, WorkOrderPartRequestLine $line, float $quantity, array $details=[]): AssetPartInstallation
    {
        $this->validateLine($request,$line,$quantity);
        if(!in_array($request->status,['RECEIVED','CLOSED'],true)) throw ValidationException::withMessages(['request'=>'Parts must be received before they can be consumed on the asset.']);

        return DB::transaction(function() use($request,$line,$quantity,$details){
            $request->load(['destinationWarehouse','workOrder']);
            $line->refresh()->load('item');
            $remaining=$this->remaining($line);
            if($quantity>$remaining) throw ValidationException::withMessages(['quantity'=>'Only '.number_format($remaining,4).' received quantity remains unallocated.']);

            $unitCost=$this->resolveUnitCost($line->item_id);
            $total=round($quantity*$unitCost,2);
            $this->stock->move($line->item,$request->destinationWarehouse->code,'ISSUE',$quantity,'asset-consume-'.Str::uuid(),'WORK_ORDER',$request->work_order_id);

            $installation=AssetPartInstallation::create([
                'tenant_id'=>$request->tenant_id,'organization_id'=>$request->organization_id,'work_order_id'=>$request->work_order_id,'asset_id'=>$request->asset_id,
                'work_order_part_request_line_id'=>$line->id,'item_id'=>$line->item_id,'warehouse_code'=>$request->destinationWarehouse->code,
                'quantity'=>$quantity,'unit_cost'=>$unitCost,'total_cost'=>$total,'installed_by'=>Auth::id(),'installed_at'=>now(),
                'removed_item_id'=>$details['removed_item_id']??null,'removed_serial'=>$details['removed_serial']??null,
                'removed_disposition'=>$details['removed_disposition']??null,'notes'=>$details['notes']??null,
            ]);

            DB::table('maintenance_materials')->insert([
                'tenant_id'=>$request->tenant_id,'organization_id'=>$request->organization_id,'work_order_id'=>$request->work_order_id,
                'item_id'=>$line->item_id,'warehouse_code'=>$request->destinationWarehouse->code,'quantity'=>$quantity,'unit_cost'=>$unitCost,'total_cost'=>$total,
                'created_at'=>now(),'updated_at'=>now(),
            ]);

            $line->update(['consumed_quantity'=>(float)$line->consumed_quantity+$quantity]);
            $materialCost=(float)DB::table('maintenance_materials')->where('work_order_id',$request->work_order_id)->sum('total_cost');
            $wo=$request->workOrder;
            $wo->update(['material_cost'=>$materialCost,'total_cost'=>$materialCost+(float)$wo->labor_cost+(float)$wo->external_cost]);
            $this->closeIfAllocated($request);
            $this->audit->record('inventory.part_request.consumed',$installation,[],['request_id'=>$request->id,'quantity'=>$quantity,'warehouse'=>$request->destinationWarehouse->code]);
            return $installation;
        });
    }

    public function returnUnused(WorkOrderPartRequest $request, WorkOrderPartRequestLine $line, float $quantity, ?string $reason=null): void
    {
        $this->validateLine($request,$line,$quantity);
        if(!in_array($request->status,['RECEIVED','CLOSED'],true)) throw ValidationException::withMessages(['request'=>'Only received parts can be returned.']);

        DB::transaction(function() use($request,$line,$quantity,$reason): void {
            $request->load(['sourceWarehouse','destinationWarehouse']);
            $line->refresh()->load('item');
            $remaining=$this->remaining($line);
            if($quantity>$remaining) throw ValidationException::withMessages(['quantity'=>'Only '.number_format($remaining,4).' received quantity remains unallocated.']);

            $operation=(string)Str::uuid();
            $this->stock->move($line->item,$request->destinationWarehouse->code,'ISSUE',$quantity,'part-return-out-'.$operation,'WORK_ORDER_PART_RETURN',$request->id);
            $this->stock->move($line->item,$request->sourceWarehouse->code,'RECEIPT',$quantity,'part-return-in-'.$operation,'WORK_ORDER_PART_RETURN',$request->id);
            DB::table('work_order_part_returns')->insert([
                'tenant_id'=>$request->tenant_id,'work_order_part_request_line_id'=>$line->id,'item_id'=>$line->item_id,
                'from_warehouse_id'=>$request->destination_warehouse_id,'to_warehouse_id'=>$request->source_warehouse_id,
                'quantity'=>$quantity,'reason'=>$reason,'returned_by'=>Auth::id(),'returned_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
            ]);
            $line->update(['returned_quantity'=>(float)$line->returned_quantity+$quantity]);
            $this->closeIfAllocated($request);
            $this->audit->record('inventory.part_request.returned',$request,['status'=>$request->status],['quantity'=>$quantity,'item_id'=>$line->item_id,'from'=>$request->destinationWarehouse->code,'to'=>$request->sourceWarehouse->code]);
        });
    }

    private function validateLine(WorkOrderPartRequest $request, WorkOrderPartRequestLine $line, float $quantity): void
    {
        if($line->work_order_part_request_id!==$request->id) throw ValidationException::withMessages(['line'=>'Part line does not belong to this request.']);
        if($quantity<=0) throw ValidationException::withMessages(['quantity'=>'Quantity must be positive.']);
    }

    private function remaining(WorkOrderPartRequestLine $line): float
    {
        return max(0,(float)$line->received_quantity-(float)$line->consumed_quantity-(float)$line->returned_quantity);
    }

    private function closeIfAllocated(WorkOrderPartRequest $request): void
    {
        $open=$request->lines()->get()->contains(fn($line)=>$this->remaining($line)>0.00001);
        if(!$open) $request->update(['status'=>'CLOSED']);
        elseif($request->status==='CLOSED') $request->update(['status'=>'RECEIVED']);
    }

    private function resolveUnitCost(int $itemId): float
    {
        $receipt=DB::table('goods_receipt_lines')->where('item_id',$itemId)->orderByDesc('id')->value('unit_cost');
        if($receipt!==null) return (float)$receipt;
        $po=DB::table('purchase_order_lines')->where('item_id',$itemId)->orderByDesc('id')->value('unit_price');
        return (float)($po??0);
    }
}
