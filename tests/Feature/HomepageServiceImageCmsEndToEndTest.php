<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageServiceImageCmsEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_generator_image_changed_in_cms_is_rendered_immediately_on_both_homepage_locales(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Homepage Image Test',
            'code' => 'HPIMG',
            'status' => 'ACTIVE',
        ]);
        $admin = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Homepage Admin',
            'email' => 'homepage-image@example.test',
            'password' => 'password',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $old = '/images/home/generator-maintenance-card.svg';
        $new = '/images/home/service-generators-unifco-20260904.webp';

        $section = HomepageSection::query()->create([
            'section_key' => 'services',
            'is_active' => true,
            'sort_order' => 30,
            'data_ar' => ['items' => [[
                'number' => '03', 'image' => $old, 'title' => 'Generators · المولدات', 'desc' => 'صيانة المولدات',
            ]]],
            'data_en' => ['items' => [[
                'number' => '03', 'image' => $old, 'title' => 'Generators', 'desc' => 'Generator maintenance',
            ]]],
        ]);

        $payload = [
            'sort_order' => 30,
            'item_ar_items_index' => [0],
            'item_ar_items_0_number' => '03',
            'item_ar_items_0_image' => $new,
            'item_ar_items_0_title' => 'Generators · المولدات',
            'item_ar_items_0_desc' => 'صيانة المولدات',
            'item_en_items_index' => [0],
            'item_en_items_0_number' => '03',
            'item_en_items_0_image' => $old,
            'item_en_items_0_title' => 'Generators',
            'item_en_items_0_desc' => 'Generator maintenance',
        ];

        $this->actingAs($admin)
            ->put(route('admin.homepage.sections.update', $section), $payload)
            ->assertRedirect();

        $fresh = $section->fresh();
        $this->assertSame($new, $fresh->data_ar['items'][0]['image']);
        $this->assertSame($new, $fresh->data_en['items'][0]['image']);

        foreach (['ar', 'en'] as $locale) {
            $html = $this->get('/?lang='.$locale)->assertOk()->getContent();
            $this->assertStringContainsString('src="'.$new.'"', $html);
            $this->assertStringNotContainsString('src="'.$old.'"', $html);
        }
    }
}
