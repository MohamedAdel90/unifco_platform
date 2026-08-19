<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandIdentityTest extends TestCase
{
    public function test_official_brand_logo_is_public_and_valid_webp(): void
    {
        $response = $this->get('/brand/unifco-logo-v2.webp');

        $response->assertOk()->assertHeader('Content-Type', 'image/webp');
        $content = $response->getContent();

        $this->assertSame('RIFF', substr($content, 0, 4));
        $this->assertSame('WEBP', substr($content, 8, 4));
        $this->assertGreaterThan(20000, strlen($content));
    }

    public function test_login_page_uses_the_official_unifco_logo(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('/brand/unifco-logo-v2.webp', false)
            ->assertSee('UNIFCO — One Facility Shop');
    }
}
