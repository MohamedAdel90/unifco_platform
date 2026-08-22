<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\{CustomerSite,Employee,InventoryTransferOrder,Item,Warehouse,WarehouseBin};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WarehouseFieldInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $user=auth()->user();
        $assignedIds=$user->role==='ADMIN' ? collect() : DB::table('warehouse_user_assignments')->where('user_id',$user->id)->pluck('warehouse_id');
        $warehouses=Warehouse::with(['assignedEmployee','site','bins'])
            ->when($user->role!=='ADMIN' && $assignedIds->isNotEmpty(),fn($q)=>$q->whereIn('id',$assignedIds))
            ->orderByRaw("CASE location_type WHEN 'CENTRAL' THEN 1 WHEN 'SITE' THEN 2 WHEN 'VEHICLE' THEN 3 ELSE 4 END")
            ->orderBy('code')->get();
        $codes=$warehouses->pluck('code');
        $balances=DB::table('stock_balances as sb')->join('items as i','i.id','=','sb.item_id')
            ->whereIn('sb.warehouse_code',$codes)
            ->select('sb.*','i.item_code','i.name as item_name','i.uom')->orderBy('sb.warehouse_code')->orderBy('i.item_code')->get();
        $balanceByWarehouse=$balances->groupBy('warehouse_code');
        $transfers=InventoryTransferOrder::with(['fromWarehouse','toWarehouse','requestedBy','lines.item'])
            ->when($user->role!=='ADMIN' && $assignedIds->isNotEmpty(),fn($q)=>$q->where(fn($x)=>$x->whereIn('from_warehouse_id',$assignedIds)->orWhereIn('to_warehouse_id',$assignedIds)))
            ->latest()->limit(25)->get();

        $metrics=[
            'locations'=>$warehouses->count(),
            'vehicle_stores'=>$warehouses->where('location_type','VEHICLE')->count(),
            'skus'=>$balances->where('quantity','>',0)->unique(fn($x)=>$x->warehouse_code.'-'.$x->item_id)->count(),
            'on_hand'=>(float)$balances->sum('quantity'),
            'reserved'=>(float)$balances->sum('reserved_quantity'),
            'in_transit'=>$transfers->where('status','IN_TRANSIT')->count(),
            'pending'=>$transfers->where('status','REQUESTED')->count(),
        ];

        return view('inventory.warehouse.index',[
            'warehouses'=>$warehouses,'balanceByWarehouse'=>$balanceByWarehouse,'transfers'=>$transfers,'metrics'=>$metrics,
            'items'=>Item::where('status','ACTIVE')->orderBy('item_code')->get(),
            'employees'=>Employee::where('status','ACTIVE')->orderBy('name')->get(),
            'sites'=>CustomerSite::orderBy('name')->get(),
        ]);
    }

    public function storeWarehouse(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'code'=>['required','string','max:40'],'name'=>['required','string','max:160'],
            'location_type'=>['required','in:CENTRAL,SITE,VEHICLE,WORKSHOP,QUARANTINE,SCRAP'],
            'parent_warehouse_id'=>['nullable','integer'],'assigned_employee_id'=>['nullable','integer'],'customer_site_id'=>['nullable','integer'],
            'vehicle_code'=>['nullable','string','max:80'],'plate_no'=>['nullable','string','max:80'],'city'=>['nullable','string','max:100'],'address'=>['nullable','string','max:255'],
        ]);
        $data['tenant_id']=auth()->user()->tenant_id;$data['organization_id']=auth()->user()->organization_id;
        $data['is_mobile']=$data['location_type']==='VEHICLE';$data['status']='ACTIVE';
        Warehouse::create($data);
        return back()->with('status','Stock location created.');
    }

    public function storeBin(Request $request, Warehouse $warehouse): RedirectResponse
    {
        abort_unless($warehouse->tenant_id===auth()->user()->tenant_id,404);
        $data=$request->validate(['bin_code'=>['required','string','max:40'],'name'=>['nullable','string','max:100'],'zone'=>['nullable','string','max:80']]);
        WarehouseBin::create(['tenant_id'=>$warehouse->tenant_id,'warehouse_id'=>$warehouse->id,'bin_code'=>$data['bin_code'],'name'=>$data['name']??null,'zone'=>$data['zone']??null,'status'=>'ACTIVE']);
        return back()->with('status','Bin location added.');
    }
}
