<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageAboutProfileCardTest extends TestCase
{
    public function test_homepage_about_profile_modal_is_available_in_both_locales(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $html = $this->get('/?lang='.$locale)->assertOk()->getContent();

            $this->assertStringContainsString('id="unifco-profile-modal"', $html);
            $this->assertStringContainsString('id="unifco-profile-modal-script"', $html);
            $this->assertStringContainsString("document.querySelector('#about .about-copy > .btn')", $html);
            $this->assertStringContainsString("trigger.setAttribute('href','#unifco-profile-modal')", $html);
            $this->assertStringContainsString('aria-haspopup', $html);
        }
    }

    public function test_english_homepage_uses_the_exact_approved_profile_card_artwork(): void
    {
        $html = $this->get('/?lang=en')->assertOk()->getContent();

        $this->assertStringContainsString('/images/home/unifco-about-card-en.webp', $html);
        $this->assertStringContainsString('unifco-profile-dialog-artwork', $html);
        $this->assertStringContainsString('aspect-ratio:3/2', $html);
        $this->assertStringNotContainsString('<h2 class="up-title">Who We Are</h2>', $html);
    }

    public function test_arabic_homepage_keeps_the_existing_profile_layout(): void
    {
        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('<h2 class="up-title">من نحن</h2>', $html);
        $this->assertStringNotContainsString('/images/home/unifco-about-card-en.webp', $html);
    }
}
