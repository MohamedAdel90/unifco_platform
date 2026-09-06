<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageAboutProfileCardTest extends TestCase
{
    public function test_homepage_about_action_opens_profile_modal_in_both_locales(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $html = $this->get('/?lang='.$locale)->assertOk()->getContent();

            $this->assertStringContainsString('id="about-company-trigger"', $html);
            $this->assertStringContainsString('id="unifco-profile-modal"', $html);
            $this->assertStringContainsString('id="unifco-profile-modal-script"', $html);
        }
    }

    public function test_homepage_about_profile_keeps_locale_specific_artwork(): void
    {
        $ar = $this->get('/?lang=ar')->assertOk()->getContent();
        $en = $this->get('/?lang=en')->assertOk()->getContent();

        $this->assertStringContainsString('/images/home/unifco-about-card-ar.webp', $ar);
        $this->assertStringContainsString('/images/home/unifco-about-card-en.webp', $en);
    }

    public function test_homepage_profile_modal_keeps_responsive_dialog_rules(): void
    {
        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('unifco-profile-dialog', $html);
        $this->assertStringContainsString('unifco-profile-artwork', $html);
    }
}
