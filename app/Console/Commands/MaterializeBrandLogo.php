<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MaterializeBrandLogo extends Command
{
    protected $signature = 'brand:materialize';
    protected $description = 'Materialize the official UNIFCO logo as a valid shared public WebP asset.';

    public function handle(): int
    {
        $source = resource_path('brand/unifco-logo.webp');
        $directory = public_path('images');
        $target = $directory.'/unifco-logo.webp';

        File::ensureDirectoryExists($directory);

        $binary = $this->validWebpBinary($source);
        if ($binary === null) {
            $binary = $this->decodeChunkedSource();
        }
        if ($binary === null) {
            $binary = $this->validWebpBinary($target);
        }

        if ($binary === null) {
            $this->error('No valid UNIFCO WebP source is available.');
            return self::FAILURE;
        }

        File::put($target, $binary);
        $image = getimagesizefromstring($binary);
        $this->info(sprintf(
            'Materialized official UNIFCO logo: %s (%dx%d, %d bytes).',
            $target,
            (int) $image[0],
            (int) $image[1],
            strlen($binary)
        ));

        return self::SUCCESS;
    }

    private function decodeChunkedSource(): ?string
    {
        $parts = glob(resource_path('brand/unifco-logo.webp.b64.*')) ?: [];
        if ($parts === []) return null;

        natsort($parts);
        $encoded = '';
        foreach ($parts as $part) {
            $encoded .= preg_replace('/\s+/', '', (string) file_get_contents($part));
        }

        $binary = base64_decode($encoded, true);
        return is_string($binary) && $this->isValidWebpBinary($binary) ? $binary : null;
    }

    private function validWebpBinary(string $path): ?string
    {
        if (! is_file($path) || filesize($path) < 16) return null;
        $binary = (string) file_get_contents($path);
        return $this->isValidWebpBinary($binary) ? $binary : null;
    }

    private function isValidWebpBinary(string $binary): bool
    {
        if (strlen($binary) < 16 || substr($binary, 0, 4) !== 'RIFF' || substr($binary, 8, 4) !== 'WEBP') return false;
        $image = @getimagesizefromstring($binary);
        return is_array($image) && ($image[0] ?? 0) >= 500 && ($image[1] ?? 0) >= 500;
    }
}
