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

            $this->assertStringContainsString('id="unifco-shared-site-header-style"', $html);
            $this->assertStringContainsString('data-shared-site-header="1"', $html);
            $this->assertStringContainsString('class="nav-links public-primary-nav"', $html);
            $this->assertStringContainsString('.site-header .nav-icon svg{display:block!important;width:21px!important;height:21px!important;fill:none!important;stroke:currentColor!important', $html);
            $this->assertStringContainsString('.site-header .nav-links>a{position:relative!important;display:inline-flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important', $html);
            $this->assertStringContainsString('.service-grid{display:grid;grid-template-columns:repeat(6,1fr)', $html);
            $this->assertStringNotContainsString('dynamic-brand-logo-presentation', $html);
            $this->assertStringNotContainsString('grid-template-columns:repeat(auto-fit,minmax(205px,1fr))', $html);
        }
    }
}
