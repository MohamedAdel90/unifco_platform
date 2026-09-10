<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('asset_spare_parts') || ! Schema::hasTable('items')) {
            return;
        }

        DB::transaction(function (): void {
            $asset = DB::table('assets')->where('asset_code', 'TEST-AST-002')->first(['id','tenant_id']);
            if (! $asset) {
                return;
            }

            $now = now();
            $parts = [
                ['item_code' => 'UAT-PMP-FLTR-001', 'name' => 'فلتر مضخة', 'part_no' => 'GR-96517844', 'qty' => 1],
                ['item_code' => 'UAT-PMP-SEAL-001', 'name' => 'طقم ختم ميكانيكي', 'part_no' => 'GR-96455087', 'qty' => 1],
                ['item_code' => 'UAT-PMP-BRG-001', 'name' => 'رولمان بلي للمضخة', 'part_no' => 'GR-96080514', 'qty' => 2],
                ['item_code' => 'UAT-PMP-ORING-001', 'name' => 'طقم O-Ring', 'part_no' => 'GR-96539458', 'qty' => 1],
            ];

            foreach ($parts as $part) {
                DB::table('items')->updateOrInsert(
                    ['tenant_id' => $asset->tenant_id, 'item_code' => $part['item_code']],
                    [
                        'name' => $part['name'],
                        'uom' => 'EA',
                        'status' => 'ACTIVE',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $itemId = DB::table('items')
                    ->where('tenant_id', $asset->tenant_id)
                    ->where('item_code', $part['item_code'])
                    ->value('id');

                DB::table('asset_spare_parts')->updateOrInsert(
                    ['asset_id' => $asset->id, 'item_id' => $itemId],
                    [
                        'manufacturer_part_no' => $part['part_no'],
                        'alternative_part_no' => null,
                        'recommended_quantity' => $part['qty'],
                        'min_stock' => 0,
                        'max_stock' => 0,
                        'reorder_level' => 0,
                        'lead_time_days' => null,
                        'preferred_supplier' => 'Grundfos',
                        'critical_spare' => in_array($part['item_code'], ['UAT-PMP-SEAL-001','UAT-PMP-BRG-001'], true),
                        'notes' => 'UAT spare part linked to TEST-AST-002 for quotation testing.',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        DB::transaction(function (): void {
            $assetId = DB::table('assets')->where('asset_code', 'TEST-AST-002')->value('id');
            $itemIds = DB::table('items')->whereIn('item_code', [
                'UAT-PMP-FLTR-001','UAT-PMP-SEAL-001','UAT-PMP-BRG-001','UAT-PMP-ORING-001'
            ])->pluck('id');

            if ($assetId && Schema::hasTable('asset_spare_parts')) {
                DB::table('asset_spare_parts')->where('asset_id', $assetId)->whereIn('item_id', $itemIds)->delete();
            }
            DB::table('items')->whereIn('id', $itemIds)->delete();
        });
    }
};
