<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('homepage_sections')->where('section_key', 'services')->first();
        if (! $row) {
            return;
        }

        $image = '/images/home/service-generators-unifco-20260904.webp';
        $updates = [];

        foreach (['data_ar', 'data_en'] as $column) {
            $data = json_decode((string) ($row->{$column} ?? ''), true);
            if (! is_array($data)) {
                $data = [];
            }

            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            foreach ($items as &$item) {
                if (! is_array($item)) {
                    continue;
                }
                $number = str_pad((string) ($item['number'] ?? ''), 2, '0', STR_PAD_LEFT);
                if ($number === '03') {
                    $item['image'] = $image;
                }
            }
            unset($item);

            $data['items'] = $items;
            $updates[$column] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $updates['updated_at'] = now();
        DB::table('homepage_sections')->where('section_key', 'services')->update($updates);

        Cache::forget('homepage_content_ar');
        Cache::forget('homepage_content_en');
    }

    public function down(): void
    {
        // Content migration: preserve the current CMS-selected image on rollback.
    }
};
