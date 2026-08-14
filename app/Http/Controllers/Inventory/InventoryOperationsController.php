<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\{Item,Warehouse};
use App\Services\AuditService;
use App\Services\Inventory\InventoryOperationsService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryOperationsController extends Controller
{
    public function index(): View
    {
        return view('inventory.operations.index',[
            'warehouses'=>Warehouse::orderBy('code')->get(),'items'=>Item::orderBy('item_code')->get(),
            'transfers'=>DB::table('inventory_transfers')->latest('id')->limit(20)->get(),
            'counts'=>DB::table('inventory_counts')->latest('id')->limit(20)->get(),
        ]);
    }
    public function storeWarehouse(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['code'=>['required','string','max:40'],'name'=>['required','string','max:120']]);
        $w=Warehouse::create([...$d,'organization_id'=>$r->user()->organization_id,'status'=>'ACTIVE']);
        $audit->record('inventory.warehouse.created',$w,[],$w->toArray()); return back()->with('status','Warehouse created.');
    }
    public function transfer(Request $r, InventoryOperationsService $svc): RedirectResponse
    {
        $d=$r->validate(['transfer_no'=>['required','string','max:50'],'item_id'=>['required','integer'],'from_warehouse_code'=>['required','string','max:40'],'to_warehouse_code'=>['required','string','max:40'],'quantity'=>['required','numeric','gt:0']]);
        $svc->transfer(Item::findOrFail($d['item_id']),$d['transfer_no'],$d['from_warehouse_code'],$d['to_warehouse_code'],(float)$d['quantity']); return back()->with('status','Transfer posted.');
    }
    public function count(Request $r, InventoryOperationsService $svc): RedirectResponse
    {
        $d=$r->validate(['count_no'=>['required','string','max:50'],'item_id'=>['required','integer'],'warehouse_code'=>['required','string','max:40'],'counted_quantity'=>['required','numeric','min:0']]);
        $svc->postCount(Item::findOrFail($d['item_id']),$d['count_no'],$d['warehouse_code'],(float)$d['counted_quantity']); return back()->with('status','Inventory count posted.');
    }
}
