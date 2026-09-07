<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicHomepageLocalizationTest extends TestCase
{
    public function test_homepage_defaults_to_arabic(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('حلول متكاملة للمرافق والمشاريع والتشغيل والصيانة')
            ->assertSee('خبرة موثوقة في تنفيذ المشاريع')
            ->assertSee('EN');
    }

    public function test_homepage_can_switch_to_english_and_back_to_arabic(): void
    {
        $this->get('/?lang=en')
            ->assertOk()
            ->assertSee('Integrated Facility, Projects, Operations & Maintenance Solutions')
            ->assertSee('Proven experience in project delivery')
            ->assertSee('AR');

        $this->get('/')
            ->assertOk()
            ->assertSee('Integrated Facility, Projects, Operations & Maintenance Solutions');

        $this->get('/?lang=ar')
            ->assertOk()
            ->assertSee('حلول متكاملة للمرافق والمشاريع والتشغيل والصيانة');
    }

    public function test_desktop_and_mobile_language_switch_links_explicitly_set_target_locale(): void
    {
        $arabic = $this->get('/?lang=ar');
        $arabic->assertOk();
        $arabic->assertSee('data-language-switch="en"', false);
        $arabic->assertSee('href="'.route('public.home', ['lang' => 'en']).'"', false);

        $english = $this->get('/?lang=en');
        $english->assertOk();
        $english->assertSee('data-language-switch="ar"', false);
        $english->assertSee('href="'.route('public.home', ['lang' => 'ar']).'"', false);
    }
}
