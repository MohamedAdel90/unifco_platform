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
            ->assertSee('English');
    }

    public function test_homepage_can_switch_to_english_and_back_to_arabic(): void
    {
        $this->get('/?lang=en')
            ->assertOk()
            ->assertSee('Integrated Facility, Projects, Operations & Maintenance Solutions', false)
            ->assertSee('العربية');

        $this->get('/')
            ->assertOk()
            ->assertSee('Integrated Facility, Projects, Operations & Maintenance Solutions', false);

        $this->get('/?lang=ar')
            ->assertOk()
            ->assertSee('حلول متكاملة للمرافق والمشاريع والتشغيل والصيانة');
    }
}
