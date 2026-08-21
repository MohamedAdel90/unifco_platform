<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MaterializeBrandLogo extends Command
{
    protected $signature = 'brand:materialize';
    protected $description = 'Materialize the official UNIFCO logo as the shared public WebP asset.';

    public function handle(): int
    {
        $source = resource_path('brand/unifco-logo.webp');
        if (! is_file($source)) {
            $this->error('Official UNIFCO brand asset is missing.');
            return self::FAILURE;
        }

        $binary = (string) file_get_contents($source);
        if ($binary === '' || substr($binary, 0, 4) !== 'RIFF' || substr($binary, 8, 4) !== 'WEBP') {
            $this->error('Official UNIFCO brand asset is invalid.');
            return self::FAILURE;
        }

        $image = @getimagesizefromstring($binary);
        if (! is_array($image) || ($image[0] ?? 0) < 500 || ($image[1] ?? 0) < 500) {
            $this->error('Official UNIFCO brand asset dimensions are invalid.');
            return self::FAILURE;
        }

        $directory = public_path('images');
        File::ensureDirectoryExists($directory);
        $target = $directory.'/unifco-logo.webp';
        File::put($target, $binary);

        $this->info(sprintf('Materialized official UNIFCO logo: %s (%dx%d, %d bytes).', $target, $image[0], $image[1], strlen($binary)));
        return self::SUCCESS;
    }
}
