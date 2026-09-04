<?php

namespace Tests\Feature;

use App\Models\HomepageClient;
use App\Models\HomepageProject;
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
        $response->assertDontSee('dynamic-brand-logo-presentation', false);
        $response->assertDontSee('grid-template-columns:repeat(auto-fit,minmax(205px,1fr))', false);
        $response->assertDontSee('color:var(--navy-deep;font-size', false);
    }

    public function test_duplicate_projects_and_client_logos_are_rendered_only_once(): void
    {
        for ($i = 0; $i < 2; $i++) {
            HomepageProject::query()->create([
                'sort_order' => $i,
                'is_active' => true,
                'year' => '2025',
                'image' => '/images/home/projects/duplicate-project.webp',
                'title_ar' => 'مشروع مكرر',
                'title_en' => 'Duplicate Project',
                'owner_ar' => 'عميل',
                'owner_en' => 'Client',
                'location_ar' => 'الرياض',
                'location_en' => 'Riyadh',
                'scope_ar' => 'صيانة',
                'scope_en' => 'Maintenance',
            ]);

            HomepageClient::query()->create([
                'sort_order' => $i,
                'is_active' => true,
                'image' => '/images/home/clients/duplicate-logo.webp',
                'name_ar' => 'عميل مكرر',
                'name_en' => 'Duplicate Client',
            ]);
        }

        $response = $this->get('/?lang=ar');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, '/images/home/projects/duplicate-project.webp'));
        $this->assertSame(1, substr_count($html, '/images/home/clients/duplicate-logo.webp'));
    }
}
