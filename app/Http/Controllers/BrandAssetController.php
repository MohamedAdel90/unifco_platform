<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

class BrandAssetController extends Controller
{
    public function logo(): Response
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
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
