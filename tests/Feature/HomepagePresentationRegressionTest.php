<?php

namespace Tests\Feature;

use App\Models\HomepageClient;
use App\Models\HomepageProject;
use App\Models\HomepageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomepagePresentationRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

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

    public function test_same_project_visual_is_rendered_once_even_when_project_metadata_differs(): void
    {
        HomepageProject::query()->create([
            'sort_order' => 1,
            'is_active' => true,
            'year' => '2025',
            'image' => '/images/home/projects/generator-maintenance.webp?v=1',
            'title_ar' => 'صيانة مولد المستشفى',
            'title_en' => 'Hospital generator maintenance',
            'owner_ar' => 'المستشفى الوطني',
            'owner_en' => 'National Hospital',
            'location_ar' => 'المدينة',
            'location_en' => 'Madinah',
            'scope_ar' => 'صيانة وقائية',
            'scope_en' => 'Preventive maintenance',
        ]);

        HomepageProject::query()->create([
            'sort_order' => 2,
            'is_active' => true,
            'year' => '2026',
            'image' => '/images/home/projects/generator-maintenance.webp?v=2',
            'title_ar' => 'صيانة مولد احتياطي',
            'title_en' => 'Standby generator maintenance',
            'owner_ar' => 'عميل آخر',
            'owner_en' => 'Another Client',
            'location_ar' => 'تبوك',
            'location_en' => 'Tabuk',
            'scope_ar' => 'فحص وتشغيل',
            'scope_en' => 'Inspection and operation',
        ]);

        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '/images/home/projects/generator-maintenance.webp'));
    }

    public function test_identical_project_files_with_different_filenames_are_rendered_once(): void
    {
        $fileA = public_path('test-home-project-copy-a.webp');
        $fileB = public_path('test-home-project-copy-b.webp');
        file_put_contents($fileA, 'same-binary-project-image');
        file_put_contents($fileB, 'same-binary-project-image');

        try {
            HomepageProject::query()->create([
                'sort_order' => 1,
                'is_active' => true,
                'year' => '2025',
                'image' => '/test-home-project-copy-a.webp',
                'title_ar' => 'مشروع أول',
                'title_en' => 'Project One',
                'owner_ar' => 'عميل أول',
                'owner_en' => 'Client One',
                'location_ar' => 'الرياض',
                'location_en' => 'Riyadh',
                'scope_ar' => 'صيانة',
                'scope_en' => 'Maintenance',
            ]);

            HomepageProject::query()->create([
                'sort_order' => 2,
                'is_active' => true,
                'year' => '2026',
                'image' => '/test-home-project-copy-b.webp',
                'title_ar' => 'مشروع ثان',
                'title_en' => 'Project Two',
                'owner_ar' => 'عميل ثان',
                'owner_en' => 'Client Two',
                'location_ar' => 'جدة',
                'location_en' => 'Jeddah',
                'scope_ar' => 'تشغيل',
                'scope_en' => 'Operations',
            ]);

            $html = $this->get('/?lang=ar')->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'test-home-project-copy-'));
        } finally {
            @unlink($fileA);
            @unlink($fileB);
        }
    }

    public function test_same_client_is_rendered_once_when_logo_url_or_upload_token_differs(): void
    {
        HomepageClient::query()->create([
            'sort_order' => 1,
            'is_active' => true,
            'image' => '/storage/homepage-media/nwc-logo.webp?v=1',
            'name_ar' => 'شركة المياه الوطنية',
            'name_en' => 'National Water Company',
        ]);

        HomepageClient::query()->create([
            'sort_order' => 2,
            'is_active' => true,
            'image' => '/storage/homepage-media/nwc-logo-copy.webp',
            'name_ar' => '  شركة   المياه الوطنية  ',
            'name_en' => 'National Water Company',
        ]);

        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '/storage/homepage-media/nwc-logo'));
    }

    public function test_identical_client_logo_files_with_different_names_are_rendered_once(): void
    {
        $fileA = public_path('test-home-client-copy-a.webp');
        $fileB = public_path('test-home-client-copy-b.webp');
        file_put_contents($fileA, 'same-binary-client-logo');
        file_put_contents($fileB, 'same-binary-client-logo');

        try {
            HomepageClient::query()->create([
                'sort_order' => 1,
                'is_active' => true,
                'image' => '/test-home-client-copy-a.webp',
                'name_ar' => 'العميل الأول',
                'name_en' => 'First Client',
            ]);
            HomepageClient::query()->create([
                'sort_order' => 2,
                'is_active' => true,
                'image' => '/test-home-client-copy-b.webp',
                'name_ar' => 'العميل الثاني',
                'name_en' => 'Second Client',
            ]);

            $html = $this->get('/?lang=ar')->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'test-home-client-copy-'));
        } finally {
            @unlink($fileA);
            @unlink($fileB);
        }
    }

    public function test_project_and_client_cache_clear_methods_invalidate_their_real_showcase_keys(): void
    {
        Cache::put('homepage_projects_ar', ['stale'], 3600);
        Cache::put('homepage_projects_en', ['stale'], 3600);
        Cache::put('homepage_clients_ar', ['stale'], 3600);
        Cache::put('homepage_clients_en', ['stale'], 3600);

        HomepageProject::clearCache();
        HomepageClient::clearCache();

        $this->assertNull(Cache::get('homepage_projects_ar'));
        $this->assertNull(Cache::get('homepage_projects_en'));
        $this->assertNull(Cache::get('homepage_clients_ar'));
        $this->assertNull(Cache::get('homepage_clients_en'));
    }
}
