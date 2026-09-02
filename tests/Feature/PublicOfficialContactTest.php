<?php

namespace Tests\Feature;

use App\Support\UnifcoContact;
use Tests\TestCase;

class PublicOfficialContactTest extends TestCase
{
    public function test_official_contact_constants_are_canonical(): void
    {
        $this->assertSame('0599402090',UnifcoContact::WHATSAPP_DISPLAY);
        $this->assertSame('966599402090',UnifcoContact::WHATSAPP_E164);
        $this->assertSame('info@unifco.com',UnifcoContact::EMAIL);
        $this->assertSame('mailto:info@unifco.com',UnifcoContact::mailto());
        $this->assertStringStartsWith('https://wa.me/966599402090',UnifcoContact::whatsappUrl('Test'));
    }

    public function test_public_home_exposes_official_contact_actions_and_emergency_phone(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('0599402090')
            ->assertSee('tel:+966599402090',false)
            ->assertSee('https://wa.me/966599402090',false)
            ->assertSee('mailto:info@unifco.com',false)
            ->assertSee('/css/home-contact.css?v=20260828-4',false)
            ->assertSee('/js/home-contact.js?v=20260828-4',false);
    }

    public function test_public_request_page_exposes_same_official_contact_channels(): void
    {
        $this->get('/request-service')
            ->assertOk()
            ->assertSee('0599402090')
            ->assertSee('info@unifco.com');
    }
}
