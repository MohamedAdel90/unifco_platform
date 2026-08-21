<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MaterializeBrandLogo extends Command
{
    protected $signature = 'brand:materialize';
    protected $description = 'Materialize the official UNIFCO logo as the shared public WebP asset without replacing a valid deployed logo with an invalid source.';

    public function handle(): int
    {
        $source = resource_path('brand/unifco-logo.webp');
        $directory = public_path('images');
        $target = $directory.'/unifco-logo.webp';

        File::ensureDirectoryExists($directory);

        if ($this->isValidWebp($source)) {
            $binary = (string) file_get_contents($source);
            $image = getimagesizefromstring($binary);
            File::put($target, $binary);
            $this->info(sprintf('Materialized official UNIFCO logo: %s (%dx%d, %d bytes).', $target, $image[0], $image[1], strlen($binary)));
            return self::SUCCESS;
        }

        if ($this->isValidWebp($target)) {
            $this->warn('Repository UNIFCO logo source is invalid; preserving the existing valid deployed logo.');
            return self::SUCCESS;
        }

        // The application serves the shared brand through the brand.logo route as well.
        // Do not destroy or overwrite any existing file and do not block an unrelated
        // application deployment because a binary brand source in the repository is corrupt.
        $this->warn('Repository UNIFCO logo source is invalid and no valid public WebP target is available. Existing brand route/fallback will remain in use.');
        return self::SUCCESS;
    }

    private function isValidWebp(string $path): bool
    {
        if (! is_file($path) || filesize($path) < 16) return false;

        $binary = (string) file_get_contents($path);
        if ($binary === '' || substr($binary, 0, 4) !== 'RIFF' || substr($binary, 8, 4) !== 'WEBP') return false;

        $image = @getimagesizefromstring($binary);
        return is_array($image) && ($image[0] ?? 0) >= 500 && ($image[1] ?? 0) >= 500;
    }
}
