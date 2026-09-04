<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepagePresentationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_keeps_approved_layout_and_renders_service_image_from_cms(): void
    {
        HomepageSection::query()->create([
            'section_key' => 'services',
            'is_active' => true,
            'sort_order' => 30,
            'data_ar' => [
                'items' => [[
                    'number' => '03',
                    'image' => '/storage/homepage-media/generator-cms.webp',
                    'title' => 'Generators · المولدات',
                    'desc' => 'صيانة المولدات',
                ]],
            ],
            'data_en' => [
                'items' => [[
                    'number' => '03',
                    'image' => '/storage/homepage-media/generator-cms.webp',
                    'title' => 'Generators',
                    'desc' => 'Generator maintenance',
                ]],
            ],
        ]);

        $response = $this->get('/?lang=ar');

        $response->assertOk();
        $response->assertSee('.service-grid{display:grid;grid-template-columns:repeat(6,1fr)', false);
        $response->assertSee('.about-grid{display:grid;grid-template-columns:.9fr 1.1fr', false);
        $response->assertSee('.industry-grid{display:grid;grid-template-columns:repeat(7,1fr)', false);
        $response->assertSee('src="/storage/homepage-media/generator-cms.webp"', false);
        $response->assertDontSee('color:var(--navy-deep;font-size', false);
    }
}
