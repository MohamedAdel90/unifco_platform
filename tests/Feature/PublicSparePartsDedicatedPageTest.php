<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSparePartsDedicatedPageTest extends TestCase
{
    public function test_spare_parts_route_renders_the_approved_dedicated_page(): void
    {
        $response = $this->get('/maintenance/spare-parts?lang=ar');

        $response->assertOk()
            ->assertSee('طلب قطع غيار')
            ->assertSee('ملخص الطلب')
            ->assertSee('بيانات العميل والأصل')
            ->assertSee('تفاصيل قطع الغيار المطلوبة')
            ->assertSee('البحث في المستودع')
            ->assertSee('إرسال الطلب وإنشاء تذكرة')
            ->assertDontSee('بيانات الموقع والتواصل')
            ->assertDontSee('بيانات طلب عرض السعر');
    }

    public function test_spare_parts_page_posts_to_the_existing_ticket_pipeline(): void
    {
        $response = $this->get('/maintenance/spare-parts?lang=ar');

        $response->assertOk()
            ->assertSee('name="request_intent" value="QUOTATION"', false)
            ->assertSee('name="request_subtype" value="SPARE_PARTS_QUOTE"', false)
            ->assertSee('action="'.route('public.request.store').'"', false);
    }
}
