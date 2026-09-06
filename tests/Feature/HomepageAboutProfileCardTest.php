<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageAboutProfileCardTest extends TestCase
{
    public function test_homepage_about_action_links_to_about_route_in_both_locales(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $html = $this->get('/?lang='.$locale)->assertOk()->getContent();

            $this->assertStringContainsString('/about?lang='.$locale, $html);
            $this->assertStringNotContainsString('id="about-company-trigger"', $html);
        }
    }

    public function test_legacy_about_profile_modal_is_not_rendered_anymore(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $html = $this->get('/?lang='.$locale)->assertOk()->getContent();

            $this->assertStringNotContainsString('id="unifco-profile-modal"', $html);
            $this->assertStringNotContainsString('id="unifco-profile-modal-script"', $html);
            $this->assertStringNotContainsString('/images/home/unifco-about-card-en.webp', $html);
            $this->assertStringNotContainsString('/images/home/unifco-about-card-ar.webp', $html);
        }
    }

    public function test_homepage_keeps_responsive_layout_without_legacy_profile_artwork_rules(): void
    {
        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertStringNotContainsString('width:210vw', $html);
        $this->assertStringNotContainsString('width:392vw', $html);
        $this->assertStringNotContainsString('unifco-profile-dialog', $html);
        $this->assertStringNotContainsString('unifco-profile-artwork', $html);
    }
}
