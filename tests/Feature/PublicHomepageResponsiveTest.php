<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicHomepageResponsiveTest extends TestCase
{
    public function test_public_homepage_renders_approved_corporate_unifco_experience(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/brand/unifco-logo-v3.webp', false)
            ->assertSee('/images/unifco-hero-approved-v15.webp', false)
            ->assertSee('/images/home/about-technician-v14.webp', false)
            ->assertSee('/images/home/service-photo-v14-00.webp', false)
            ->assertSee('/images/home/industry-photo-v14-00.webp', false)
            ->assertSee('/images/home/client-portal-v14.webp', false)
            ->assertSee('ONE FACILITY SHOP')
            ->assertSee('شريك واحد لجميع احتياجات منشأتك')
            ->assertSee('حلول متكاملة تحت سقف واحد')
            ->assertSee('كيف نعمل')
            ->assertSee('القطاعات التي نخدمها')
            ->assertSee('إدارة خدماتك من مكان واحد')
            ->assertSee('لماذا UNIFCO؟')
            ->assertSee('خبرة موثوقة في تنفيذ المشاريع')
            ->assertSee('44+')
            ->assertSee('/images/home/projects/ats-maintenance.webp', false)
            ->assertSee('/images/home/clients/nwc.webp', false)
            ->assertSee('capability-bar', false)
            ->assertSee('service-grid', false)
            ->assertSee('operation-split', false)
            ->assertSee('maintenance-photo', false)
            ->assertSee('portal-device', false)
            ->assertSee('showcase-project-card', false)
            ->assertSee('showcase-clients', false)
            ->assertSee('emergency-banner', false)
            ->assertSee('/css/home-showcase.css', false)
            ->assertSee('/js/home-showcase.js', false)
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
