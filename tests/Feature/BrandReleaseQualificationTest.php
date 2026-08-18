<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandReleaseQualificationTest extends TestCase
{
    public function test_official_unifco_logo_asset_decodes_to_a_real_image(): void
    {
        $response = $this->get('/brand/unifco-logo.webp');
        $response->assertOk()->assertHeader('Content-Type', 'image/webp');

        $content = $response->getContent();
        $this->assertSame('RIFF', substr($content, 0, 4));
        $this->assertSame('WEBP', substr($content, 8, 4));
        $this->assertGreaterThan(20000, strlen($content));

        $image = getimagesizefromstring($content);
        $this->assertIsArray($image);
        $this->assertGreaterThanOrEqual(500, $image[0]);
        $this->assertGreaterThanOrEqual(500, $image[1]);
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
