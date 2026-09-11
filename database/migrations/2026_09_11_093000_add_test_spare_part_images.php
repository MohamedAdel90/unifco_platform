<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        if (! Schema::hasColumn('items', 'image_path')) {
            Schema::table('items', function (Blueprint $table): void {
                $table->text('image_path')->nullable();
            });
        }

        $parts = [
            'UAT-PMP-BRG-001' => [
                'uom' => 'EA',
                'image_path' => 'https://pulsarwater.mx/cdn/shop/files/003A5054-Mejorado-NR_1200x800.jpg?v=1737761912',
            ],
            'UAT-PMP-ORING-001' => [
                'uom' => 'SET',
                'image_path' => 'https://dmt-onlineshop.de/images/product_images/popup_images/grundfos-o-ring-fuer-hauswasserwerk-jp5-jp6-194-x-3-0-mm-quadring-365556-0.webp',
            ],
            'UAT-PMP-SEAL-001' => [
                'uom' => 'SET',
                'image_path' => 'https://image.made-in-china.com/226f3j00MUNaZtBGZVkw/Cr-Mechanical-Seal-12mm-32mm-for-Grundfos-Pump.jpg',
            ],
            'UAT-PMP-FLTR-001' => [
                'uom' => 'EA',
                'image_path' => 'https://pexuniverse.com/uploads/products/99/99725185/9166/images/458x458/99725185_1.jpg',
            ],
        ];

        foreach ($parts as $itemCode => $data) {
            DB::table('items')->where('item_code', $itemCode)->update([
                'uom' => $data['uom'],
                'image_path' => $data['image_path'],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        if (Schema::hasColumn('items', 'image_path')) {
            DB::table('items')->whereIn('item_code', [
                'UAT-PMP-BRG-001',
                'UAT-PMP-ORING-001',
                'UAT-PMP-SEAL-001',
                'UAT-PMP-FLTR-001',
            ])->update([
                'image_path' => null,
                'updated_at' => now(),
            ]);
        }
    }
};
