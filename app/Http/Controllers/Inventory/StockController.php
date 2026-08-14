<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\Inventory\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(): View
    {
        return view('inventory.stock.index', ['items'=>Item::orderBy('item_code')->get()]);
    }

    public function move(Request $request, StockService $service): RedirectResponse
    {
        $data=$request->validate([
            'item_id'=>['required','integer'],'warehouse_code'=>['required','string','max:40'],
            'movement_type'=>['required','in:RECEIPT,ISSUE'],'quantity'=>['required','numeric','gt:0'],
            'idempotency_key'=>['required','string','max:120'],
        ]);
        $item=Item::findOrFail($data['item_id']);
        $result=$service->move($item,$data['warehouse_code'],$data['movement_type'],(float)$data['quantity'],$data['idempotency_key']);
        return back()->with('status',$result['duplicate']?'Duplicate request ignored.':'Stock movement posted.');
    }
}
