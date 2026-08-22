<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\{Customer,CustomerSite,Employee,InventoryTransferOrder,Item,Warehouse,WarehouseBin,WorkOrderPartRequest};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WarehouseFieldInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $user=auth()->user();
        $restricted=!in_array($user->role,['ADMIN','STOREKEEPER'],true);
        $assignedIds=$restricted ? DB::table('warehouse_user_assignments')->where('tenant_id',$user->tenant_id)->where('user_id',$user->id)->pluck('warehouse_id') : collect();

        $warehouses=Warehouse::with(['assignedEmployee','site','bins'])
            ->when($restricted && $assignedIds->isNotEmpty(),fn($q)=>$q->whereIn('id',$assignedIds))
            ->when($restricted && $assignedIds->isEmpty(),fn($q)=>$q->whereRaw('1=0'))
            ->orderByRaw("CASE location_type WHEN 'CENTRAL' THEN 1 WHEN 'SITE' THEN 2 WHEN 'VEHICLE' THEN 3 ELSE 4 END")
            ->orderBy('code')->get();
        $codes=$warehouses->pluck('code');
        $balances=DB::table('stock_balances as sb')->join('items as i','i.id','=','sb.item_id')
            ->where('sb.tenant_id',$user->tenant_id)->where('i.tenant_id',$user->tenant_id)->whereIn('sb.warehouse_code',$codes)
            ->select('sb.*','i.item_code','i.name as item_name','i.uom')->orderBy('sb.warehouse_code')->orderBy('i.item_code')->get();
        $balanceByWarehouse=$balances->groupBy('warehouse_code');
        $transfers=InventoryTransferOrder::with(['fromWarehouse','toWarehouse','requestedBy','lines.item'])
            ->when($restricted && $assignedIds->isNotEmpty(),fn($q)=>$q->where(fn($x)=>$x->whereIn('from_warehouse_id',$assignedIds)->orWhereIn('to_warehouse_id',$assignedIds)))
            ->when($restricted && $assignedIds->isEmpty(),fn($q)=>$q->whereRaw('1=0'))
            ->latest()->limit(25)->get();
        $partRequests=WorkOrderPartRequest::with(['workOrder.asset','sourceWarehouse','destinationWarehouse','requestedBy','lines.item'])
            ->when($restricted && $assignedIds->isNotEmpty(),fn($q)=>$q->where(fn($x)=>$x->whereIn('source_warehouse_id',$assignedIds)->orWhereIn('destination_warehouse_id',$assignedIds)))
            ->when($restricted && $assignedIds->isEmpty(),fn($q)=>$q->whereRaw('1=0'))
            ->latest()->limit(40)->get();

        $metrics=[
            'locations'=>$warehouses->count(),
            'vehicle_stores'=>$warehouses->where('location_type','VEHICLE')->count(),
            'skus'=>$balances->where('quantity','>',0)->unique(fn($x)=>$x->warehouse_code.'-'.$x->item_id)->count(),
            'on_hand'=>(float)$balances->sum('quantity'),
            'reserved'=>(float)$balances->sum('reserved_quantity'),
            'in_transit'=>$transfers->where('status','IN_TRANSIT')->count(),
            'pending'=>$transfers->where('status','REQUESTED')->count(),
            'part_requests_pending'=>$partRequests->where('status','REQUESTED')->count(),
            'part_requests_ready'=>$partRequests->whereIn('status',['APPROVED','PICKED'])->count(),
            'part_requests_issued'=>$partRequests->where('status','ISSUED')->count(),
        ];

        $customerIds=Customer::pluck('id');
        return view('inventory.warehouse.index',[
            'warehouses'=>$warehouses,'balanceByWarehouse'=>$balanceByWarehouse,'transfers'=>$transfers,'partRequests'=>$partRequests,'metrics'=>$metrics,
            'items'=>Item::where('status','ACTIVE')->orderBy('item_code')->get(),
            'employees'=>Employee::where('status','ACTIVE')->orderBy('name')->get(),
            'sites'=>CustomerSite::whereIn('customer_id',$customerIds)->orderBy('name')->get(),
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
        $tenantId=auth()->user()->tenant_id;
        if(!empty($data['parent_warehouse_id'])) abort_unless(Warehouse::whereKey($data['parent_warehouse_id'])->exists(),404);
        if(!empty($data['assigned_employee_id'])) abort_unless(Employee::whereKey($data['assigned_employee_id'])->exists(),404);
        if(!empty($data['customer_site_id'])) {
            $customerIds=Customer::pluck('id');
            abort_unless(CustomerSite::whereKey($data['customer_site_id'])->whereIn('customer_id',$customerIds)->exists(),404);
        }
        $data['tenant_id']=$tenantId;$data['organization_id']=auth()->user()->organization_id;
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
