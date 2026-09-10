<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CurrentCustomerAssetPartsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_number' => ['required','string','max:80'],
            'contract_no' => ['nullable','string','max:120'],
            'asset_id' => ['required','integer'],
        ]);

        $customerNumber = trim($data['customer_number']);
        $customerQuery = DB::table('customers')->where('customer_code', $customerNumber);
        if (ctype_digit($customerNumber)) {
            $customerQuery->orWhere('id', (int) $customerNumber);
        }
        $customer = $customerQuery->first(['id']);
        if (! $customer) {
            return response()->json(['message' => 'لم يتم العثور على العميل الحالي.'], 404);
        }

        $asset = DB::table('assets')
            ->where('id', $data['asset_id'])
            ->where('customer_id', $customer->id)
            ->when(! empty($data['contract_no']), fn ($q) => $q->where('contract_reference', $data['contract_no']))
            ->first(['id','asset_code','name','manufacturer','model_no']);

        if (! $asset) {
            return response()->json(['message' => 'الأصل المحدد غير مرتبط بالعميل أو العقد الحالي.'], 404);
        }

        $parts = collect();

        if (Schema::hasTable('asset_spare_parts')) {
            $parts = DB::table('asset_spare_parts as asp')
                ->join('items as i', 'i.id', '=', 'asp.item_id')
                ->where('asp.asset_id', $asset->id)
                ->where(function ($q) {
                    $q->whereNull('i.status')->orWhere('i.status', 'ACTIVE');
                })
                ->orderBy('i.name')
                ->get([
                    'asp.id',
                    'asp.item_id',
                    'asp.manufacturer_part_no',
                    'asp.recommended_quantity',
                    'asp.preferred_supplier',
                    'i.item_code',
                    'i.name as item_name',
                    'i.uom',
                ])
                ->map(function ($row) use ($asset) {
                    return [
                        'id' => $row->id,
                        'item_id' => $row->item_id,
                        'name' => $row->item_name ?: 'قطعة غيار',
                        'part_no' => $row->manufacturer_part_no ?: $row->item_code,
                        'manufacturer' => $row->preferred_supplier ?: $asset->manufacturer,
                        'uom' => $row->uom ?: 'EA',
                        'recommended_quantity' => (float) $row->recommended_quantity,
                        'asset_id' => $asset->id,
                        'asset_code' => $asset->asset_code,
                        'asset_name' => $asset->name,
                    ];
                });
        }

        return response()->json([
            'asset' => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
            ],
            'parts' => $parts->values(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
