<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicHomepageResponsiveTest extends TestCase
{
    public function test_public_homepage_renders_approved_corporate_unifco_experience(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/brand/unifco-logo-v2.webp', false)
            ->assertSee('ONE FACILITY SHOP')
            ->assertSee('شريك واحد لجميع احتياجات منشأتك')
            ->assertSee('حلول متكاملة تحت سقف واحد')
            ->assertSee('كيف نعمل')
            ->assertSee('القطاعات التي نخدمها')
            ->assertSee('إدارة خدماتك من مكان واحد')
            ->assertSee('لماذا UNIFCO؟')
            ->assertSee('أعمالنا على أرض الواقع')
            ->assertSee('menu-toggle', false)
            ->assertSee('@media(max-width:1080px)', false)
            ->assertSee('@media(max-width:700px)', false)
            ->assertSee('@media(max-width:450px)', false)
            ->assertSee('fonts.googleapis.com/css2?family=Cairo', false)
            ->assertSee('font-family:"Cairo",Tahoma,Arial,sans-serif', false)
            ->assertSee('#1e315b', false)
            ->assertSee('#132137', false)
            ->assertSee('#ce122d', false);
    }
}
