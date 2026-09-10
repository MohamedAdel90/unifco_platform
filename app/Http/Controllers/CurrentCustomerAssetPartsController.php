<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $parts = DB::table('asset_part_installations as api')
            ->leftJoin('items as i', 'i.id', '=', 'api.item_id')
            ->where('api.asset_id', $asset->id)
            ->whereNull('api.removed_at')
            ->orderByDesc('api.installed_at')
            ->get([
                'api.id',
                'api.item_id',
                'api.installed_part_number',
                'api.installed_manufacturer',
                'api.quantity',
                'i.item_code',
                'i.name as item_name',
                'i.uom',
            ])
            ->map(function ($row) use ($asset) {
                return [
                    'id' => $row->id,
                    'item_id' => $row->item_id,
                    'name' => $row->item_name ?: ($row->installed_part_number ?: 'قطعة غيار'),
                    'part_no' => $row->installed_part_number ?: $row->item_code,
                    'manufacturer' => $row->installed_manufacturer ?: $asset->manufacturer,
                    'uom' => $row->uom ?: 'EA',
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->name,
                ];
            })
            ->unique(fn ($row) => ($row['item_id'] ?: 'x').'-'.($row['part_no'] ?: 'na'))
            ->values();

        return response()->json([
            'asset' => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
            ],
            'parts' => $parts,
        ])->header('Cache-Control', 'no-store, private');
    }
}
