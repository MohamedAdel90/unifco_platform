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
            'asset_id' => ['required','string','max:160'],
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

        $assetKey = trim((string) $data['asset_id']);
        $assetQuery = DB::table('assets')->where('customer_id', $customer->id);
        $assetQuery->where(function ($q) use ($assetKey) {
            if (ctype_digit($assetKey)) {
                $q->where('id', (int) $assetKey);
            } else {
                $q->where('asset_code', $assetKey)
                    ->orWhere('customer_asset_code', $assetKey)
                    ->orWhere('manufacturer_asset_number', $assetKey)
                    ->orWhere('serial_no', $assetKey);
            }
        });
        if (! empty($data['contract_no'])) {
            $assetQuery->where('contract_reference', $data['contract_no']);
        }

        $asset = $assetQuery->first(['id','asset_code','name','manufacturer','model_no']);
        if (! $asset) {
            return response()->json(['message' => 'الأصل المحدد غير مرتبط بالعميل أو العقد الحالي.'], 404);
        }

        $parts = collect();
        $imageColumn = $this->itemImageColumn();

        if (Schema::hasTable('asset_spare_parts') && Schema::hasTable('items')) {
            $columns = [
                'asp.id','asp.item_id','asp.manufacturer_part_no','asp.recommended_quantity','asp.preferred_supplier',
                'i.item_code','i.name as item_name','i.uom',
            ];
            if ($imageColumn) {
                $columns[] = DB::raw('i.'.$imageColumn.' as item_image');
            }

            $parts = DB::table('asset_spare_parts as asp')
                ->join('items as i', 'i.id', '=', 'asp.item_id')
                ->where('asp.asset_id', $asset->id)
                ->when(Schema::hasColumn('items', 'status'), function ($q) {
                    $q->where(function ($status) {
                        $status->whereNull('i.status')->orWhere('i.status', 'ACTIVE');
                    });
                })
                ->orderBy('i.name')
                ->get($columns)
                ->map(function ($row) use ($asset) {
                    return [
                        'id' => $row->id,
                        'item_id' => $row->item_id,
                        'name' => $row->item_name ?: 'قطعة غيار',
                        'part_no' => $row->manufacturer_part_no ?: $row->item_code,
                        'manufacturer' => $row->preferred_supplier ?: $asset->manufacturer,
                        'uom' => $row->uom ?: 'EA',
                        'image_url' => $this->imageUrl($row->item_image ?? null),
                        'recommended_quantity' => max(1, (float) $row->recommended_quantity),
                        'asset_id' => $asset->id,
                        'asset_code' => $asset->asset_code,
                        'asset_name' => $asset->name,
                    ];
                });
        }

        if ($parts->isEmpty() && Schema::hasTable('maintenance_materials') && Schema::hasTable('work_orders') && Schema::hasTable('items')) {
            $columns = ['i.id as item_id','i.item_code','i.name as item_name','i.uom'];
            if ($imageColumn) {
                $columns[] = DB::raw('i.'.$imageColumn.' as item_image');
            }

            $rows = DB::table('maintenance_materials as mm')
                ->join('work_orders as wo', 'wo.id', '=', 'mm.work_order_id')
                ->join('items as i', 'i.id', '=', 'mm.item_id')
                ->where('wo.asset_id', $asset->id)
                ->when(Schema::hasColumn('items', 'status'), function ($q) {
                    $q->where(function ($status) {
                        $status->whereNull('i.status')->orWhere('i.status', 'ACTIVE');
                    });
                })
                ->orderBy('i.name')
                ->distinct()
                ->get($columns);

            $parts = $rows->map(function ($row) use ($asset) {
                return [
                    'id' => 'history-'.$row->item_id,
                    'item_id' => $row->item_id,
                    'name' => $row->item_name ?: 'قطعة غيار',
                    'part_no' => $row->item_code,
                    'manufacturer' => $asset->manufacturer,
                    'uom' => $row->uom ?: 'EA',
                    'image_url' => $this->imageUrl($row->item_image ?? null),
                    'recommended_quantity' => 1,
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

    private function itemImageColumn(): ?string
    {
        if (! Schema::hasTable('items')) {
            return null;
        }

        foreach (['image_path','image','photo_path','thumbnail_path','thumbnail','photo'] as $column) {
            if (Schema::hasColumn('items', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function imageUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $value) || str_starts_with($value, 'data:') || str_starts_with($value, '/')) {
            return $value;
        }

        return asset('storage/'.ltrim($value, '/'));
    }
}
