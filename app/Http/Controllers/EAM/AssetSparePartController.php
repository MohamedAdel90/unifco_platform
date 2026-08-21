<?php

namespace App\Http\Controllers\EAM;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Item};
use App\Services\EAM\AssetSparePartReorderService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetSparePartController extends Controller
{
    public function show(Asset $asset, AssetSparePartReorderService $reorder): View
    {
        $asset->load(['customer','site']);
        $items=Item::where('status','ACTIVE')->orderBy('item_code')->get();
        $parts=$reorder->alerts($asset->customer_id,$asset->customer_site_id)->where('asset_id',$asset->id)->values();
        $warehouses=DB::table('stock_balances')->where('tenant_id',$asset->tenant_id)->distinct()->orderBy('warehouse_code')->pluck('warehouse_code');
        return view('eam.assets.spare-parts',compact('asset','items','parts','warehouses'));
    }

    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $data=$request->validate([
            'item_id'=>['required','integer','exists:items,id'],
            'manufacturer_part_no'=>['nullable','string','max:120'],
            'alternative_part_no'=>['nullable','string','max:120'],
            'recommended_quantity'=>['required','numeric','min:0.0001'],
            'min_stock'=>['nullable','numeric','min:0'],
            'max_stock'=>['nullable','numeric','min:0'],
            'reorder_level'=>['nullable','numeric','min:0'],
            'lead_time_days'=>['nullable','integer','min:0'],
            'preferred_supplier'=>['nullable','string','max:180'],
            'preferred_warehouse_code'=>['nullable','string','max:50'],
            'critical_spare'=>['nullable','boolean'],
            'notes'=>['nullable','string','max:3000'],
        ]);

        if(($data['max_stock']??0)>0 && ($data['min_stock']??0)>($data['max_stock']??0)){
            return back()->withErrors(['min_stock'=>'Minimum stock cannot exceed maximum stock.'])->withInput();
        }
        if(($data['max_stock']??0)>0 && ($data['reorder_level']??0)>($data['max_stock']??0)){
            return back()->withErrors(['reorder_level'=>'Reorder level cannot exceed maximum stock.'])->withInput();
        }

        DB::table('asset_spare_parts')->updateOrInsert(
            ['asset_id'=>$asset->id,'item_id'=>$data['item_id']],
            [
                'asset_id'=>$asset->id,'item_id'=>$data['item_id'],'manufacturer_part_no'=>$data['manufacturer_part_no']??null,
                'alternative_part_no'=>$data['alternative_part_no']??null,'recommended_quantity'=>$data['recommended_quantity'],
                'min_stock'=>$data['min_stock']??0,'max_stock'=>$data['max_stock']??0,'reorder_level'=>$data['reorder_level']??0,
                'lead_time_days'=>$data['lead_time_days']??null,'preferred_supplier'=>$data['preferred_supplier']??null,
                'preferred_warehouse_code'=>$data['preferred_warehouse_code']??null,'critical_spare'=>$request->boolean('critical_spare'),
                'notes'=>$data['notes']??null,'updated_at'=>now(),'created_at'=>now(),
            ]
        );
        return back()->with('status','Compatible spare part and reorder policy saved.');
    }

    public function destroy(Asset $asset, int $part): RedirectResponse
    {
        DB::table('asset_spare_parts')->where('id',$part)->where('asset_id',$asset->id)->delete();
        return back()->with('status','Spare part compatibility removed.');
    }
}
