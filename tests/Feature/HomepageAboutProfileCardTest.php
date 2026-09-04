<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageAboutProfileCardTest extends TestCase
{
    public function test_homepage_restores_about_profile_modal_for_arabic_and_english(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $html = $this->get('/?lang='.$locale)->assertOk()->getContent();

            $this->assertStringContainsString('id="unifco-profile-modal"', $html);
            $this->assertStringContainsString('id="unifco-profile-modal-script"', $html);
            $this->assertStringContainsString("document.querySelector('#about .about-copy > .btn')", $html);
            $this->assertStringContainsString("trigger.setAttribute('href','#unifco-profile-modal')", $html);
            $this->assertStringContainsString('aria-haspopup', $html);
            $this->assertStringContainsString("grid.cloneNode(true)", $html);
            $this->assertStringContainsString("stats.cloneNode(true)", $html);
        }
    }
}
