<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SERVICE_IMAGES = [
        '01' => '/images/home/service-photo-v14-04.webp',
        '02' => '/images/home/ats.svg',
        '03' => '/images/home/generator-maintenance-card.svg',
        '04' => '/images/home/facility-power.svg',
        '05' => '/images/home/service-photo-v14-01.webp',
        '06' => '/images/home/about-technician-v14.webp',
        '07' => '/images/home/service-photo-v14-02.webp',
        '08' => '/images/home/industry-photo-v14-01.webp',
        '09' => '/images/home/industry-photo-v14-04.webp',
        '10' => '/images/home/service-photo-v14-05.webp',
        '11' => '/images/home/service-photo-v14-03.webp',
        '12' => '/images/home/industry-photo-v14-03.webp',
        '13' => '/images/home/service-photo-v14-00.webp',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        $section = DB::table('homepage_sections')->where('section_key', 'services')->first();
        if (! $section) {
            return;
        }

        $updates = [];
        foreach (['data_ar', 'data_en'] as $column) {
            $data = json_decode((string) ($section->{$column} ?? ''), true);
            if (! is_array($data)) {
                continue;
            }

            $changed = false;
            foreach (['items', 'services'] as $listKey) {
                if (! isset($data[$listKey]) || ! is_array($data[$listKey])) {
                    continue;
                }

                foreach ($data[$listKey] as $index => $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    if (array_is_list($row)) {
                        $number = str_pad((string) ($row[0] ?? ''), 2, '0', STR_PAD_LEFT);
                        if (isset(self::SERVICE_IMAGES[$number]) && empty($row[1])) {
                            $row[1] = self::SERVICE_IMAGES[$number];
                            $data[$listKey][$index] = $row;
                            $changed = true;
                        }
                    } else {
                        $number = str_pad((string) ($row['number'] ?? ''), 2, '0', STR_PAD_LEFT);
                        if (isset(self::SERVICE_IMAGES[$number]) && empty($row['image'])) {
                            $row['image'] = self::SERVICE_IMAGES[$number];
                            $data[$listKey][$index] = $row;
                            $changed = true;
                        }
                    }
                }
            }

            if ($changed) {
                $updates[$column] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        if ($updates !== []) {
            DB::table('homepage_sections')->where('id', $section->id)->update($updates);
        }
    }

    public function down(): void
    {
        // Restoration is intentionally non-destructive. Do not remove images on rollback.
    }
};
