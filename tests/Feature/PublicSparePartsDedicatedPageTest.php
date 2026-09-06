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

    public function test_summary_is_forced_to_the_physical_left_in_arabic_and_english(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $response = $this->get('/maintenance/spare-parts?lang='.$locale);

            $response->assertOk()
                ->assertSee('id="unifco-spare-summary-left-layout"', false)
                ->assertSee('grid-template-areas:"summary main"', false)
                ->assertSee('grid-column:1 !important', false);
        }
    }

    public function test_common_request_selector_stays_above_the_request_without_repeating_selection_data(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $response = $this->get('/maintenance/spare-parts?lang='.$locale);

            $response->assertOk()
                ->assertSee('id="request-selector-bar"', false)
                ->assertSee('position:sticky', false)
                ->assertSee($locale === 'ar' ? 'نوع الخدمة المطلوبة' : 'Required Service')
                ->assertSee($locale === 'ar' ? 'عرض سعر قطع غيار' : 'Spare Parts Quotation');
        }
    }

    public function test_the_entire_upper_request_chrome_is_shared_by_maintenance_and_spare_parts(): void
    {
        foreach (['ar', 'en'] as $locale) {
            foreach (['/maintenance?lang='.$locale, '/maintenance/spare-parts?lang='.$locale] as $url) {
                $response = $this->get($url);

                $response->assertOk()
                    ->assertSee('id="unifco-request-chrome"', false)
                    ->assertSee('id="request-selector-bar"', false)
                    ->assertSee($locale === 'ar' ? 'خدمة أسرع تبدأ بطلب أوضح' : 'A faster service starts with a clearer request')
                    ->assertSee($locale === 'ar' ? 'نوع العميل' : 'Customer Type')
                    ->assertSee($locale === 'ar' ? 'نوع الخدمة المطلوبة' : 'Required Service')
                    ->assertSee($locale === 'ar' ? 'نوع الطلب' : 'Request Type');
            }
        }
    }

    public function test_legacy_maintenance_selector_is_hidden_below_the_shared_chrome(): void
    {
        $response = $this->get('/maintenance?lang=ar');

        $response->assertOk()
            ->assertSee('#maintenance-form > .panel:first-of-type{display:none!important}', false);
    }
}
