<?php

namespace App\Services\Inventory;

use App\Models\{Item,Warehouse,WorkOrder,WorkOrderPartRequest,WorkOrderPartRequestLine};
use App\Services\AuditService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class WorkOrderPartRequestService
{
    public function __construct(private StockService $stock, private AuditService $audit) {}

    public function create(WorkOrder $workOrder, Warehouse $source, Warehouse $destination, array $lines, string $priority='NORMAL', ?string $reason=null): WorkOrderPartRequest
    {
        if($workOrder->status==='COMPLETED') throw ValidationException::withMessages(['work_order'=>'Cannot request parts for a completed work order.']);
        if($source->id===$destination->id) throw ValidationException::withMessages(['destination_warehouse_id'=>'Source and destination stock locations must be different.']);
        if($source->status!=='ACTIVE' || $destination->status!=='ACTIVE') throw ValidationException::withMessages(['warehouse'=>'Both stock locations must be active.']);
        if(in_array($destination->location_type,['SCRAP','QUARANTINE'],true)) throw ValidationException::withMessages(['destination_warehouse_id'=>'Parts cannot be delivered to scrap or quarantine for work execution.']);

        return DB::transaction(function() use($workOrder,$source,$destination,$lines,$priority,$reason){
            $request=WorkOrderPartRequest::create([
                'tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,
                'request_no'=>$this->nextNumber(),'work_order_id'=>$workOrder->id,'asset_id'=>$workOrder->asset_id,
                'source_warehouse_id'=>$source->id,'destination_warehouse_id'=>$destination->id,
                'priority'=>$priority,'status'=>'REQUESTED','reason'=>$reason,'requested_by'=>Auth::id(),
            ]);
            foreach($lines as $line){
                $qty=(float)($line['quantity']??0);
                if($qty<=0) continue;
                $item=Item::findOrFail((int)$line['item_id']);
                WorkOrderPartRequestLine::create([
                    'work_order_part_request_id'=>$request->id,'item_id'=>$item->id,'requested_quantity'=>$qty,
                    'approved_quantity'=>0,'reserved_quantity'=>0,'issued_quantity'=>0,'received_quantity'=>0,
                ]);
            }
            if(!$request->lines()->exists()) throw ValidationException::withMessages(['items'=>'At least one part with a positive quantity is required.']);
            $this->audit->record('inventory.part_request.created',$request,[],['status'=>'REQUESTED','work_order_id'=>$workOrder->id,'source'=>$source->code,'destination'=>$destination->code]);
            return $request->load(['lines.item','sourceWarehouse','destinationWarehouse','workOrder.asset']);
        });
    }

    public function approve(WorkOrderPartRequest $request, ?string $note=null): WorkOrderPartRequest
    {
        if($request->status!=='REQUESTED') throw ValidationException::withMessages(['request'=>'Only requested part requests can be approved.']);
        return DB::transaction(function() use($request,$note){
            $request->load(['lines.item','sourceWarehouse']);
            foreach($request->lines as $line){
                $balance=DB::table('stock_balances')->where([
                    'tenant_id'=>$request->tenant_id,'item_id'=>$line->item_id,'warehouse_code'=>$request->sourceWarehouse->code,
                ])->lockForUpdate()->first();
                $onHand=(float)($balance->quantity??0);$reserved=(float)($balance->reserved_quantity??0);$available=max(0,$onHand-$reserved);
                $qty=(float)$line->requested_quantity;
                if($available<$qty) throw ValidationException::withMessages(['stock'=>$line->item->item_code.' has only '.number_format($available,4).' available in '.$request->sourceWarehouse->code.'.']);
                DB::table('stock_balances')->where([
                    'tenant_id'=>$request->tenant_id,'item_id'=>$line->item_id,'warehouse_code'=>$request->sourceWarehouse->code,
                ])->update(['reserved_quantity'=>$reserved+$qty,'updated_at'=>now()]);
                $line->update(['approved_quantity'=>$qty,'reserved_quantity'=>$qty]);
            }
            $request->update(['status'=>'APPROVED','approved_by'=>Auth::id(),'approved_at'=>now(),'decision_note'=>$note]);
            $this->audit->record('inventory.part_request.approved',$request,['status'=>'REQUESTED'],['status'=>'APPROVED']);
            return $request->fresh(['lines.item','sourceWarehouse','destinationWarehouse']);
        });
    }

    public function reject(WorkOrderPartRequest $request, string $note): WorkOrderPartRequest
    {
        if($request->status!=='REQUESTED') throw ValidationException::withMessages(['request'=>'Only requested part requests can be rejected.']);
        $request->update(['status'=>'REJECTED','approved_by'=>Auth::id(),'approved_at'=>now(),'decision_note'=>$note]);
        $this->audit->record('inventory.part_request.rejected',$request,['status'=>'REQUESTED'],['status'=>'REJECTED','note'=>$note]);
        return $request->fresh();
    }

    public function pick(WorkOrderPartRequest $request): WorkOrderPartRequest
    {
        if($request->status!=='APPROVED') throw ValidationException::withMessages(['request'=>'Only approved part requests can be picked.']);
        $request->update(['status'=>'PICKED','picked_by'=>Auth::id(),'picked_at'=>now()]);
        $this->audit->record('inventory.part_request.picked',$request,['status'=>'APPROVED'],['status'=>'PICKED']);
        return $request->fresh();
    }

    public function issue(WorkOrderPartRequest $request): WorkOrderPartRequest
    {
        if($request->status!=='PICKED') throw ValidationException::withMessages(['request'=>'Only picked part requests can be issued.']);
        return DB::transaction(function() use($request){
            $request->load(['lines.item','sourceWarehouse']);
            foreach($request->lines as $line){
                $qty=(float)$line->approved_quantity;
                $balance=DB::table('stock_balances')->where([
                    'tenant_id'=>$request->tenant_id,'item_id'=>$line->item_id,'warehouse_code'=>$request->sourceWarehouse->code,
                ])->lockForUpdate()->first();
                if(!$balance || (float)$balance->quantity<$qty || (float)$balance->reserved_quantity<$qty) throw ValidationException::withMessages(['stock'=>'Reserved stock is no longer consistent for '.$line->item->item_code.'.']);
                $this->stock->move($line->item,$request->sourceWarehouse->code,'ISSUE',$qty,'part-request-issue-'.$request->id.'-'.$line->id,'WORK_ORDER_PART_REQUEST',$request->id);
                DB::table('stock_balances')->where([
                    'tenant_id'=>$request->tenant_id,'item_id'=>$line->item_id,'warehouse_code'=>$request->sourceWarehouse->code,
                ])->update(['reserved_quantity'=>max(0,(float)$balance->reserved_quantity-$qty),'updated_at'=>now()]);
                $line->update(['reserved_quantity'=>0,'issued_quantity'=>$qty]);
            }
            $request->update(['status'=>'ISSUED','issued_by'=>Auth::id(),'issued_at'=>now()]);
            $this->audit->record('inventory.part_request.issued',$request,['status'=>'PICKED'],['status'=>'ISSUED']);
            return $request->fresh(['lines.item','sourceWarehouse','destinationWarehouse']);
        });
    }

    public function receive(WorkOrderPartRequest $request): WorkOrderPartRequest
    {
        if($request->status!=='ISSUED') throw ValidationException::withMessages(['request'=>'Only issued part requests can be received.']);
        return DB::transaction(function() use($request){
            $request->load(['lines.item','destinationWarehouse']);
            foreach($request->lines as $line){
                $qty=(float)$line->issued_quantity;
                $this->stock->move($line->item,$request->destinationWarehouse->code,'RECEIPT',$qty,'part-request-receipt-'.$request->id.'-'.$line->id,'WORK_ORDER_PART_REQUEST',$request->id);
                $line->update(['received_quantity'=>$qty]);
            }
            $request->update(['status'=>'RECEIVED','received_by'=>Auth::id(),'received_at'=>now()]);
            $this->audit->record('inventory.part_request.received',$request,['status'=>'ISSUED'],['status'=>'RECEIVED']);
            return $request->fresh(['lines.item','sourceWarehouse','destinationWarehouse']);
        });
    }

    private function nextNumber(): string
    {
        $prefix='PRT-'.now()->format('Ym').'-';
        $last=WorkOrderPartRequest::where('request_no','like',$prefix.'%')->orderByDesc('id')->value('request_no');
        $seq=$last?((int)substr($last,-5))+1:1;
        return $prefix.str_pad((string)$seq,5,'0',STR_PAD_LEFT);
    }
}
