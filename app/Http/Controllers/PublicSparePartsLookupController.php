<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicSparePartsLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
        ]);

        $query = trim((string) ($data['q'] ?? ''));
        $assetId = $data['asset_id'] ?? null;

        $items = DB::table('items as i')
            ->where('i.status', 'ACTIVE')
            ->when($query !== '', function ($q) use ($query) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('i.item_code', 'like', $like)
                        ->orWhere('i.name', 'like', $like);
                });
            })
            ->when($assetId, function ($q) use ($assetId) {
                $q->leftJoin('asset_spare_parts as asp', function ($join) use ($assetId) {
                    $join->on('asp.item_id', '=', 'i.id')->where('asp.asset_id', '=', $assetId);
                });
            })
            ->select(['i.id', 'i.tenant_id', 'i.item_code', 'i.name', 'i.uom'])
            ->when($assetId, fn ($q) => $q->addSelect([
                'asp.id as compatibility_id',
                'asp.manufacturer_part_no',
                'asp.alternative_part_no',
                'asp.preferred_warehouse_code',
                'asp.critical_spare',
            ]))
            ->orderByRaw($assetId ? 'CASE WHEN asp.id IS NULL THEN 1 ELSE 0 END' : '0')
            ->orderBy('i.item_code')
            ->limit(30)
            ->get();

        $balances = DB::table('stock_balances')
            ->whereIn('item_id', $items->pluck('id'))
            ->selectRaw('item_id, SUM(quantity - reserved_quantity) as available_quantity')
            ->groupBy('item_id')
            ->pluck('available_quantity', 'item_id');

        $result = $items->map(function ($item) use ($balances, $assetId) {
            $available = (float) ($balances[$item->id] ?? 0);
            $status = $available > 2 ? 'AVAILABLE' : ($available > 0 ? 'LIMITED' : 'NOT_AVAILABLE');

            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'uom' => $item->uom,
                'availability' => $status,
                'compatible' => $assetId ? ! empty($item->compatibility_id) : null,
                'manufacturer_part_no' => $item->manufacturer_part_no ?? null,
                'alternative_part_no' => $item->alternative_part_no ?? null,
                'preferred_warehouse_code' => $item->preferred_warehouse_code ?? null,
                'critical_spare' => (bool) ($item->critical_spare ?? false),
            ];
        })->values();

        return response()->json(['items' => $result])->header('Cache-Control', 'no-store, private');
    }
}
