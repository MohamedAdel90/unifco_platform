<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicRequestChromeCleanupTest extends TestCase
{
    public function test_maintenance_page_contains_one_shared_upper_chrome_only(): void
    {
        $response = $this->get('/maintenance?lang=ar');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'id="unifco-request-chrome"'));
        $this->assertSame(1, substr_count($html, 'id="request-selector-bar"'));
        $this->assertStringNotContainsString('class="current-service-nav"', $html);
        $this->assertStringNotContainsString('class="request-clarity-hero"', $html);
        $this->assertStringNotContainsString('class="request-ticket-note"', $html);
        $this->assertStringNotContainsString('class="panel request-selector-panel"', $html);
    }

    public function test_spare_parts_page_contains_one_shared_upper_chrome_only(): void
    {
        $response = $this->get('/maintenance/spare-parts?lang=ar');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'id="unifco-request-chrome"'));
        $this->assertSame(1, substr_count($html, 'id="request-selector-bar"'));
        $this->assertStringNotContainsString('class="current-service-nav"', $html);
        $this->assertStringNotContainsString('class="request-clarity-hero"', $html);
        $this->assertStringNotContainsString('class="request-ticket-note"', $html);
    }
}
