<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MaterializeBrandLogo extends Command
{
    protected $signature = 'brand:materialize';
    protected $description = 'Materialize the official UNIFCO logo as a real public WebP asset.';

    public function handle(): int
    {
        $parts = glob(resource_path('brand/unifco-logo.webp.b64.*')) ?: [];
        sort($parts, SORT_NATURAL);

        if (count($parts) !== 4) {
            $this->error('UNIFCO brand asset is incomplete.');
            return self::FAILURE;
        }

        $encoded = implode('', array_map(
            static fn (string $path): string => trim((string) file_get_contents($path)),
            $parts,
        ));
        $binary = base64_decode($encoded, true);

        if ($binary === false || substr($binary, 0, 4) !== 'RIFF' || substr($binary, 8, 4) !== 'WEBP') {
            $this->error('UNIFCO brand asset is invalid.');
            return self::FAILURE;
        }

        $image = @getimagesizefromstring($binary);
        if (! is_array($image) || ($image[0] ?? 0) < 500 || ($image[1] ?? 0) < 500) {
            $this->error('UNIFCO brand asset dimensions are invalid.');
            return self::FAILURE;
        }

        $directory = public_path('images');
        File::ensureDirectoryExists($directory);
        $target = $directory.'/unifco-logo.webp';
        File::put($target, $binary);

        $this->info(sprintf('Materialized %s (%dx%d, %d bytes).', $target, $image[0], $image[1], strlen($binary)));
        return self::SUCCESS;
    }
}
