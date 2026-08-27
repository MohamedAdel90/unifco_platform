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
            ->assertSee('EN');
    }

    public function test_homepage_can_switch_to_english_and_back_to_arabic(): void
    {
        $this->get('/?lang=en')
            ->assertOk()
            ->assertSee('Integrated Facility, Projects, Operations & Maintenance Solutions')
            ->assertSee('AR');

        $this->get('/')
            ->assertOk()
            ->assertSee('Integrated Facility, Projects, Operations & Maintenance Solutions');

        $this->get('/?lang=ar')
            ->assertOk()
            ->assertSee('حلول متكاملة للمرافق والمشاريع والتشغيل والصيانة');
    }
}
