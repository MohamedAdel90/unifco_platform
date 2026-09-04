<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        $hero = DB::table('homepage_sections')->where('section_key', 'hero')->first();
        if (! $hero) {
            return;
        }

        $updates = [];
        foreach (['data_ar', 'data_en'] as $column) {
            $data = json_decode((string) ($hero->{$column} ?? ''), true);
            if (! is_array($data)) {
                $data = [];
            }
            $data['render_mode'] = 'full_banner';
            $updates[$column] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        DB::table('homepage_sections')->where('id', $hero->id)->update($updates);
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        $hero = DB::table('homepage_sections')->where('section_key', 'hero')->first();
        if (! $hero) {
            return;
        }

        $updates = [];
        foreach (['data_ar', 'data_en'] as $column) {
            $data = json_decode((string) ($hero->{$column} ?? ''), true);
            if (! is_array($data)) {
                $data = [];
            }
            unset($data['render_mode']);
            $updates[$column] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        DB::table('homepage_sections')->where('id', $hero->id)->update($updates);
    }
};
