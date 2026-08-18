<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class BrandAssetController extends Controller
{
    public function logo(): Response
    {
        $parts = glob(resource_path('brand/unifco-logo.webp.b64.*')) ?: [];
        sort($parts, SORT_NATURAL);

        abort_unless(count($parts) === 4, 500, 'UNIFCO brand asset is incomplete.');

        $encoded = implode('', array_map(
            static fn (string $path): string => trim((string) file_get_contents($path)),
            $parts,
        ));
        $binary = base64_decode($encoded, true);

        abort_if($binary === false || substr($binary, 0, 4) !== 'RIFF' || substr($binary, 8, 4) !== 'WEBP', 500, 'UNIFCO brand asset is invalid.');

        return response($binary, 200, [
            'Content-Type' => 'image/webp',
            'Content-Length' => (string) strlen($binary),
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
