<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\{Bom,Item,ProductionOrder,Routing,WorkCenter};
use App\Services\AuditService;
use App\Services\Manufacturing\ManufacturingOperationsService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ManufacturingOperationsController extends Controller
{
    public function index(): View
    {
        return view('manufacturing.operations.index',[
            'items'=>Item::orderBy('item_code')->get(),
            'workCenters'=>WorkCenter::orderBy('code')->get(),
            'boms'=>Bom::with('lines')->latest()->get(),
            'routings'=>Routing::with('operations')->latest()->get(),
            'orders'=>ProductionOrder::latest()->limit(30)->get(),
            'warehouses'=>DB::table('warehouses')->where('tenant_id',auth()->user()->tenant_id)->where('status','ACTIVE')->orderBy('code')->get(),
        ]);
    }

    public function storeWorkCenter(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['code'=>['required','string','max:40'],'name'=>['required','string','max:120'],'hourly_rate'=>['required','numeric','min:0']]);
        $wc=WorkCenter::create([...$d,'organization_id'=>$r->user()->organization_id,'status'=>'ACTIVE']);
        $audit->record('manufacturing.work_center.created',$wc,[],$wc->toArray());
        return back()->with('status','Work center created.');
    }

    public function storeBom(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate([
            'bom_no'=>['required','string','max:50'],'version'=>['required','integer','min:1'],'product_item_id'=>['required','integer'],
            'lines'=>['required','array','min:1'],'lines.*.component_item_id'=>['required','integer'],'lines.*.quantity_per'=>['required','numeric','gt:0'],'lines.*.standard_unit_cost'=>['required','numeric','min:0'],
        ]);
        Item::findOrFail($d['product_item_id']);
        $bom=DB::transaction(function () use ($r,$d) {
            $bom=Bom::create(['organization_id'=>$r->user()->organization_id,'product_item_id'=>$d['product_item_id'],'bom_no'=>$d['bom_no'],'version'=>$d['version'],'status'=>'ACTIVE']);
            foreach($d['lines'] as $i=>$line) { Item::findOrFail($line['component_item_id']); $bom->lines()->create([...$line,'line_no'=>$i+1]); }
            return $bom->fresh('lines');
        });
        $audit->record('manufacturing.bom.created',$bom,[],$bom->toArray());
        return back()->with('status','BOM created.');
    }

    public function storeRouting(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate([
            'routing_no'=>['required','string','max:50'],'product_item_id'=>['required','integer'],
            'operations'=>['required','array','min:1'],'operations.*.work_center_id'=>['required','integer'],'operations.*.operation_name'=>['required','string','max:120'],'operations.*.standard_hours'=>['required','numeric','gt:0'],
        ]);
        Item::findOrFail($d['product_item_id']);
        $routing=DB::transaction(function () use ($r,$d) {
            $routing=Routing::create(['organization_id'=>$r->user()->organization_id,'product_item_id'=>$d['product_item_id'],'routing_no'=>$d['routing_no'],'status'=>'ACTIVE']);
            foreach($d['operations'] as $i=>$op) { WorkCenter::findOrFail($op['work_center_id']); $routing->operations()->create([...$op,'sequence'=>($i+1)*10]); }
            return $routing->fresh('operations');
        });
        $audit->record('manufacturing.routing.created',$routing,[],$routing->toArray());
        return back()->with('status','Routing created.');
    }

    public function release(Request $r, ProductionOrder $productionOrder, ManufacturingOperationsService $svc): RedirectResponse
    {
        $d=$r->validate(['bom_id'=>['required','integer'],'routing_id'=>['required','integer'],'warehouse_code'=>['required','string','max:40']]);
        $svc->release($productionOrder,Bom::findOrFail($d['bom_id']),Routing::findOrFail($d['routing_id']),$d['warehouse_code']);
        return back()->with('status','Production order released with BOM and routing.');
    }

    public function issue(ProductionOrder $productionOrder, ManufacturingOperationsService $svc): RedirectResponse
    {
        $svc->issueMaterials($productionOrder); return back()->with('status','Production materials issued.');
    }

    public function confirm(Request $r, ProductionOrder $productionOrder, ManufacturingOperationsService $svc): RedirectResponse
    {
        $d=$r->validate(['work_center_id'=>['nullable','integer'],'hours'=>['required','numeric','min:0'],'good_quantity'=>['required','numeric','min:0'],'scrap_quantity'=>['nullable','numeric','min:0']]);
        $svc->confirm($productionOrder,$d); return back()->with('status','Production confirmation recorded.');
    }

    public function inspect(Request $r, ProductionOrder $productionOrder, ManufacturingOperationsService $svc): RedirectResponse
    {
        $d=$r->validate(['result'=>['required','in:PASS,FAIL'],'notes'=>['nullable','string','max:1000']]);
        $svc->inspect($productionOrder,$d['result'],$d['notes']??null); return back()->with('status','Quality inspection recorded.');
    }

    public function complete(Request $r, ProductionOrder $productionOrder, ManufacturingOperationsService $svc): RedirectResponse
    {
        $d=$r->validate(['produced_quantity'=>['required','numeric','gt:0']]);
        $svc->complete($productionOrder,(float)$d['produced_quantity']); return back()->with('status','Production completed and finished goods received.');
    }
}
