<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageNavigationPresentationTest extends TestCase
{
    public function test_homepage_navigation_keeps_approved_line_icons_and_does_not_restore_legacy_layout_overrides(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $response = $this->get('/?lang='.$locale);
            $response->assertOk();

            $html = $response->getContent();

            $this->assertStringContainsString('id="public-home-nav-presentation"', $html);
            $this->assertStringContainsString('.public-primary-nav .nav-icon svg{display:block;width:21px;height:21px;fill:none;stroke:currentColor', $html);
            $this->assertStringContainsString('.public-primary-nav>a{display:inline-flex;flex-direction:column;align-items:center;justify-content:center', $html);
            $this->assertStringContainsString('.service-grid{display:grid;grid-template-columns:repeat(6,1fr)', $html);
            $this->assertStringNotContainsString('dynamic-brand-logo-presentation', $html);
            $this->assertStringNotContainsString('grid-template-columns:repeat(auto-fit,minmax(205px,1fr))', $html);
        }
    }
}
