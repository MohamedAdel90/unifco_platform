<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicHomepageResponsiveTest extends TestCase
{
    public function test_public_homepage_renders_enhanced_responsive_unifco_experience(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/brand/unifco-logo-v2.webp', false)
            ->assertSee('UNIFCO · ONE FACILITY SHOP')
            ->assertSee('كيف نعمل')
            ->assertSee('Integrated Facilities · MEP · Power Systems')
            ->assertSee('menu-toggle', false)
            ->assertSee('@media(max-width:1050px)', false)
            ->assertSee('@media(max-width:720px)', false)
            ->assertSee('@media(max-width:460px)', false)
            ->assertSee('fonts.googleapis.com/css2?family=Cairo', false)
            ->assertSee('font-family:"Cairo",Tahoma,Arial,sans-serif', false)
            ->assertSee('#1e315b', false)
            ->assertSee('#132137', false)
            ->assertSee('#ce122d', false);
    }
}
