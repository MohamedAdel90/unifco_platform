<?php

namespace App\Http\Controllers;

use App\Models\BrandingSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\{Artisan,Schema,Storage};

class BrandAssetController extends Controller
{
    public function logo(): Response
    {
        if (Schema::hasTable('branding_settings')) {
            $branding = BrandingSetting::query()->first();
            if ($branding?->logo_path && Storage::disk('local')->exists($branding->logo_path)) {
                $binary = Storage::disk('local')->get($branding->logo_path);
                $mime = $branding->logo_mime ?: Storage::disk('local')->mimeType($branding->logo_path) ?: 'image/png';

                return response($binary, 200, [
                    'Content-Type' => $mime,
                    'Content-Length' => (string) strlen($binary),
                    'Cache-Control' => 'no-cache, must-revalidate',
                    'ETag' => '"'.sha1($binary).'"',
                ]);
            }
        }

        return $this->defaultLogo();
    }

    private function defaultLogo(): Response
    {
        $target = public_path('images/unifco-logo.webp');

        if (! is_file($target)) {
            abort_unless(Artisan::call('brand:materialize') === 0, 500, 'UNIFCO brand asset could not be materialized.');
        }

        $binary = (string) file_get_contents($target);
        abort_if($binary === '' || substr($binary, 0, 4) !== 'RIFF' || substr($binary, 8, 4) !== 'WEBP', 500, 'UNIFCO brand asset is invalid.');

        return response($binary, 200, [
            'Content-Type' => 'image/webp',
            'Content-Length' => (string) strlen($binary),
            'Cache-Control' => 'no-cache, must-revalidate',
            'ETag' => '"'.sha1($binary).'"',
        ]);
    }
}
