<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BrandReleaseQualificationTest extends TestCase
{
    public function test_official_unifco_logo_materializes_as_a_real_public_file(): void
    {
        $target = public_path('images/unifco-logo.webp');
        File::delete($target);

        $this->assertSame(0, Artisan::call('brand:materialize'));
        $this->assertFileExists($target);

        $content = (string) file_get_contents($target);
        $this->assertSame('RIFF', substr($content, 0, 4));
        $this->assertSame('WEBP', substr($content, 8, 4));
        $this->assertGreaterThan(20000, strlen($content));

        $image = getimagesizefromstring($content);
        $this->assertIsArray($image);
        $this->assertGreaterThanOrEqual(500, $image[0]);
        $this->assertGreaterThanOrEqual(500, $image[1]);
    }

    public function test_official_brand_route_serves_the_materialized_file(): void
    {
        $response = $this->get('/brand/unifco-logo.webp');
        $response->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->assertFileExists(public_path('images/unifco-logo.webp'));
    }

    public function test_public_and_login_pages_use_the_official_logo_and_palette(): void
    {
        foreach (['/', '/login'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('/brand/unifco-logo.webp', false)
                ->assertSee('#1e315b', false)
                ->assertSee('#132137', false)
                ->assertSee('#ce122d', false);
        }
    }
}
