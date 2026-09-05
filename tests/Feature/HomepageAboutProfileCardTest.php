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
            $this->assertStringContainsString('class="unifco-profile-artwork"', $html);
        }
    }

    public function test_english_homepage_uses_only_the_exact_approved_profile_card_artwork(): void
    {
        $html = $this->get('/?lang=en')->assertOk()->getContent();

        $this->assertStringContainsString('/images/home/unifco-about-card-en.webp?v=20260905-2', $html);
        $this->assertStringContainsString('unifco-profile-dialog is-english', $html);
        $this->assertStringNotContainsString('/images/home/unifco-about-card-ar.webp', $html);
        $this->assertStringNotContainsString('class="unifco-profile-sheet"', $html);
        $this->assertStringNotContainsString('class="up-values"', $html);
        $this->assertStringNotContainsString('class="up-cards"', $html);
    }

    public function test_arabic_homepage_uses_only_the_exact_approved_profile_card_artwork_at_large_zoom(): void
    {
        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('/images/home/unifco-about-card-ar.webp?v=20260905-2', $html);
        $this->assertStringContainsString('unifco-profile-dialog is-arabic', $html);
        $this->assertStringContainsString('width:392vw', $html);
        $this->assertStringNotContainsString('/images/home/unifco-about-card-en.webp', $html);
        $this->assertStringNotContainsString('class="unifco-profile-sheet"', $html);
        $this->assertStringNotContainsString('<h2 class="up-title">من نحن</h2>', $html);
        $this->assertStringNotContainsString('class="up-values"', $html);
        $this->assertStringNotContainsString('class="up-cards"', $html);
    }
}
