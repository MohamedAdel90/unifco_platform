<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageAboutProfileCardTest extends TestCase
{
    public function test_homepage_about_action_opens_profile_modal_in_both_locales(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $html = $this->get('/?lang='.$locale)->assertOk()->getContent();

            $this->assertStringContainsString('id="unifco-profile-modal"', $html);
            $this->assertStringContainsString('id="unifco-profile-modal-script"', $html);
            $this->assertStringContainsString("document.querySelector('#about .about-copy > .btn')", $html);
            $this->assertStringContainsString("trigger.setAttribute('href','#unifco-profile-modal')", $html);
            $this->assertStringContainsString("trigger.setAttribute('aria-haspopup','dialog')", $html);
            $this->assertStringNotContainsString('id="about-company-trigger"', $html);
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
        $this->assertStringContainsString('overflow-x:hidden', $html);
        $this->assertStringContainsString('width:100%;max-width:100%', $html);
    }
}
