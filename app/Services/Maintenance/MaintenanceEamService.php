<?php

namespace App\Services\Maintenance;

use App\Models\{Asset,AssetMeterReading,Item,MaintenancePlan,WorkOrder};
use App\Services\AuditService;
use App\Services\Inventory\StockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class MaintenanceEamService
{
    public function __construct(private StockService $stock, private AuditService $audit) {}

    public function recordMeter(Asset $asset, float $reading, string $date, ?string $notes=null): AssetMeterReading
    {
        if ($reading < (float)$asset->meter_value) throw ValidationException::withMessages(['reading'=>'Meter reading cannot move backwards.']);
        return DB::transaction(function () use ($asset,$reading,$date,$notes) {
            $entry=AssetMeterReading::create(['organization_id'=>Auth::user()->organization_id,'asset_id'=>$asset->id,'reading'=>$reading,'reading_date'=>$date,'notes'=>$notes,'recorded_by'=>Auth::id()]);
            $before=$asset->toArray(); $asset->update(['meter_value'=>$reading]);
            $this->audit->record('eam.asset.meter_recorded',$asset,$before,$asset->fresh()->toArray());
            return $entry;
        });
    }

    public function generateDueWorkOrders(): int
    {
        $count=0;
        foreach (MaintenancePlan::where('status','ACTIVE')->get() as $plan) {
            $asset=$plan->asset;
            $dateDue=$plan->next_due_date && $plan->next_due_date->lte(today());
            $meterDue=$plan->next_due_meter !== null && (float)$asset->meter_value >= (float)$plan->next_due_meter;
            if (! $dateDue && ! $meterDue) continue;
            if (WorkOrder::where('maintenance_plan_id',$plan->id)->whereIn('status',['OPEN','IN_PROGRESS'])->exists()) continue;
            WorkOrder::create(['organization_id'=>$plan->organization_id,'asset_id'=>$asset->id,'maintenance_plan_id'=>$plan->id,'work_order_no'=>'PM-'.$plan->id.'-'.now()->format('YmdHis'),'maintenance_type'=>'PREVENTIVE','priority'=>$plan->priority,'planned_start'=>now(),'status'=>'OPEN']);
            $this->advancePlan($plan,$asset);
            $count++;
        }
        return $count;
    }

    private function advancePlan(MaintenancePlan $plan, Asset $asset): void
    {
        if ($plan->frequency_type === 'DAYS') $plan->next_due_date=Carbon::parse($plan->next_due_date ?? today())->addDays($plan->frequency_value);
        elseif ($plan->frequency_type === 'MONTHS') $plan->next_due_date=Carbon::parse($plan->next_due_date ?? today())->addMonths($plan->frequency_value);
        elseif ($plan->frequency_type === 'METER') $plan->next_due_meter=(float)$asset->meter_value + $plan->frequency_value;
        $plan->save();
    }

    public function start(WorkOrder $order): WorkOrder
    {
        if ($order->status !== 'OPEN') throw ValidationException::withMessages(['work_order'=>'Only OPEN work orders can be started.']);
        $before=$order->toArray(); $order->update(['status'=>'IN_PROGRESS','started_at'=>now()]);
        $this->audit->record('maintenance.work_order.started',$order,$before,$order->fresh()->toArray());
        return $order->fresh();
    }

    public function addLabor(WorkOrder $order, float $hours, float $hourlyRate): WorkOrder
    {
        if ($order->status !== 'IN_PROGRESS') throw ValidationException::withMessages(['work_order'=>'Work order must be IN_PROGRESS.']);
        $cost=round($hours*$hourlyRate,2);
        $order->increment('labor_hours',$hours); $order->increment('labor_cost',$cost); $this->recalculate($order);
        return $order->fresh();
    }

    public function issueMaterial(WorkOrder $order, Item $item, string $warehouse, float $quantity, float $unitCost): WorkOrder
    {
        if ($order->status !== 'IN_PROGRESS') throw ValidationException::withMessages(['work_order'=>'Work order must be IN_PROGRESS.']);
        return DB::transaction(function () use ($order,$item,$warehouse,$quantity,$unitCost) {
            $this->stock->move($item,$warehouse,'ISSUE',$quantity,'maintenance-'.$order->id.'-'.$item->id.'-'.$warehouse.'-'.DB::table('maintenance_materials')->where('work_order_id',$order->id)->count(),'maintenance_work_order',$order->id);
            $cost=round($quantity*$unitCost,2);
            DB::table('maintenance_materials')->insert(['tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,'work_order_id'=>$order->id,'item_id'=>$item->id,'warehouse_code'=>$warehouse,'quantity'=>$quantity,'unit_cost'=>$unitCost,'total_cost'=>$cost,'created_at'=>now(),'updated_at'=>now()]);
            $order->increment('material_cost',$cost); $this->recalculate($order);
            return $order->fresh();
        });
    }

    public function complete(WorkOrder $order, int $downtimeMinutes=0, ?string $failureCode=null, float $externalCost=0): WorkOrder
    {
        if ($order->status !== 'IN_PROGRESS') throw ValidationException::withMessages(['work_order'=>'Only IN_PROGRESS work orders can be completed.']);
        $before=$order->toArray();
        $order->update(['external_cost'=>$externalCost,'downtime_minutes'=>$downtimeMinutes,'failure_code'=>$failureCode,'completed_at'=>now(),'status'=>'COMPLETED']);
        $this->recalculate($order); $this->audit->record('maintenance.work_order.completed',$order,$before,$order->fresh()->toArray());
        return $order->fresh();
    }

    public function transferAsset(Asset $asset, string $toLocation, string $date): Asset
    {
        return DB::transaction(function () use ($asset,$toLocation,$date) {
            $from=$asset->location_code;
            DB::table('asset_transfers')->insert(['tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,'asset_id'=>$asset->id,'from_location'=>$from,'to_location'=>$toLocation,'transfer_date'=>$date,'transferred_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);
            $asset->update(['location_code'=>$toLocation]); $this->audit->record('eam.asset.transferred',$asset,['location_code'=>$from],['location_code'=>$toLocation]);
            return $asset->fresh();
        });
    }

    public function depreciate(Asset $asset, string $postingDate): Asset
    {
        if ($asset->status !== 'CAPITALIZED' || ! $asset->useful_life_months) throw ValidationException::withMessages(['asset'=>'Capitalized asset with useful life is required.']);
        $depreciable=max(0,(float)$asset->acquisition_cost-(float)$asset->salvage_value);
        $monthly=round($depreciable/$asset->useful_life_months,2);
        $remaining=max(0,$depreciable-(float)$asset->accumulated_depreciation); $amount=min($monthly,$remaining);
        if ($amount <= 0) throw ValidationException::withMessages(['asset'=>'Asset is fully depreciated.']);
        $acc=round((float)$asset->accumulated_depreciation+$amount,2); $nbv=round((float)$asset->acquisition_cost-$acc,2);
        DB::transaction(function () use ($asset,$postingDate,$amount,$acc,$nbv) {
            DB::table('asset_depreciation_entries')->insert(['tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,'asset_id'=>$asset->id,'posting_date'=>$postingDate,'amount'=>$amount,'accumulated_after'=>$acc,'nbv_after'=>$nbv,'created_at'=>now(),'updated_at'=>now()]);
            $asset->update(['accumulated_depreciation'=>$acc,'net_book_value'=>$nbv]);
        });
        return $asset->fresh();
    }

    public function dispose(Asset $asset): Asset
    {
        if ($asset->status === 'DISPOSED') throw ValidationException::withMessages(['asset'=>'Asset is already disposed.']);
        $before=$asset->toArray(); $asset->update(['status'=>'DISPOSED','disposed_at'=>now()]);
        $this->audit->record('eam.asset.disposed',$asset,$before,$asset->fresh()->toArray()); return $asset->fresh();
    }

    private function recalculate(WorkOrder $order): void
    {
        $order->update(['total_cost'=>round((float)$order->labor_cost+(float)$order->material_cost+(float)$order->external_cost,2)]);
    }
}
