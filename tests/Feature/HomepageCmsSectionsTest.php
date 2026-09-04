<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\Tenant;
use App\Models\User;
use App\Services\HomepageSectionSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCmsSectionsTest extends TestCase
{
    use RefreshDatabase;

    private array $sectionKeys = [
        'hero', 'capabilities', 'about', 'services', 'process',
        'industries', 'operations', 'why', 'showcase', 'clients',
        'emergency', 'footer_cta', 'footer',
    ];

    private function admin(): User
    {
        $code = 'HPC'.substr(uniqid(), -6);
        $tenant = Tenant::query()->create([
            'name' => 'Homepage CMS Test Tenant',
            'code' => $code,
            'status' => 'ACTIVE',
        ]);

        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Homepage CMS Test Admin',
            'email' => 'homepage-cms-'.$code.'@example.test',
            'password' => 'password',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_every_homepage_section_can_be_updated_in_arabic_and_english_without_cross_section_overlap(): void
    {
        $admin = $this->admin();
        $sections = collect($this->sectionKeys)->mapWithKeys(function (string $key, int $index) {
            $section = HomepageSection::query()->create([
                'section_key' => $key,
                'is_active' => true,
                'sort_order' => $index * 10,
                'data_ar' => ['legacy_keep' => "legacy-ar-{$key}"],
                'data_en' => ['legacy_keep' => "legacy-en-{$key}"],
            ]);

            return [$key => $section];
        });

        foreach ($this->sectionKeys as $key) {
            $section = $sections[$key];
            $otherSnapshots = $sections
                ->except($key)
                ->mapWithKeys(fn (HomepageSection $other, string $otherKey) => [
                    $otherKey => [$other->fresh()->data_ar, $other->fresh()->data_en],
                ]);

            $payload = $this->payloadFor($key);
            $response = $this->actingAs($admin)->put(route('admin.homepage.sections.update', $section), $payload);
            $response->assertRedirect();

            $fresh = $section->fresh();
            $this->assertSame("legacy-ar-{$key}", $fresh->data_ar['legacy_keep']);
            $this->assertSame("legacy-en-{$key}", $fresh->data_en['legacy_keep']);
            $this->assertSectionPayloadPersisted($key, $fresh, $payload);

            foreach ($otherSnapshots as $otherKey => [$beforeAr, $beforeEn]) {
                $other = $sections[$otherKey]->fresh();
                $this->assertSame($beforeAr, $other->data_ar, "Updating {$key} changed AR data of {$otherKey}");
                $this->assertSame($beforeEn, $other->data_en, "Updating {$key} changed EN data of {$otherKey}");
            }
        }
    }

    public function test_every_homepage_section_supports_unsaved_preview_in_both_languages_without_persisting_draft_data(): void
    {
        $admin = $this->admin();

        foreach ($this->sectionKeys as $index => $key) {
            $section = HomepageSection::query()->create([
                'section_key' => $key,
                'is_active' => true,
                'sort_order' => $index * 10,
                'data_ar' => ['legacy_keep' => "preview-ar-{$key}"],
                'data_en' => ['legacy_keep' => "preview-en-{$key}"],
            ]);

            $beforeAr = $section->data_ar;
            $beforeEn = $section->data_en;
            $payload = $this->payloadFor($key);

            foreach (['ar', 'en'] as $locale) {
                $previewPayload = $payload;
                $previewPayload['locale'] = $locale;

                $response = $this->actingAs($admin)
                    ->post(route('admin.homepage.sections.preview', $section), $previewPayload);

                $response->assertOk();
                $response->assertHeader('Cache-Control');
                $response->assertSee('UNIFCO', false);

                if ($key === 'hero' && $locale === 'ar') {
                    $response->assertSee('hero-full-banner-image', false);
                }

                $fresh = $section->fresh();
                $this->assertSame($beforeAr, $fresh->data_ar, "Preview {$locale} persisted AR data for {$key}");
                $this->assertSame($beforeEn, $fresh->data_en, "Preview {$locale} persisted EN data for {$key}");
            }
        }
    }

    public function test_services_editor_binds_image_controls_to_real_repeater_field_names(): void
    {
        $admin = $this->admin();
        $section = HomepageSection::query()->create([
            'section_key' => 'services',
            'is_active' => true,
            'sort_order' => 30,
            'data_ar' => ['items' => [[
                'number' => '01',
                'image' => '/images/home/service-photo-v14-04.webp',
                'title' => 'Transformers',
                'desc' => 'AR desc',
            ]]],
            'data_en' => ['items' => [[
                'number' => '01',
                'image' => '/images/home/service-photo-v14-04.webp',
                'title' => 'Transformers',
                'desc' => 'EN desc',
            ]]],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.homepage.sections.edit', $section));

        $response->assertOk();
        $response->assertSee('name="item_ar_items_0_image"', false);
        $response->assertSee('name="item_en_items_0_image"', false);
        $response->assertDontSee('name="item_{{$locale}}_{{$listKey}}_{{$i}}_{{$f}}"', false);
    }

    public function test_services_save_preserves_existing_image_when_an_unrelated_field_is_not_submitted(): void
    {
        $admin = $this->admin();
        $section = HomepageSection::query()->create([
            'section_key' => 'services',
            'is_active' => true,
            'sort_order' => 30,
            'data_ar' => ['items' => [[
                'number' => '03',
                'image' => '/images/home/generator-maintenance-card.svg',
                'title' => 'Generators',
                'desc' => 'Old AR description',
            ]]],
            'data_en' => ['items' => [[
                'number' => '03',
                'image' => '/images/home/generator-maintenance-card.svg',
                'title' => 'Generators',
                'desc' => 'Old EN description',
            ]]],
        ]);

        $payload = [
            'sort_order' => 30,
            'item_ar_items_index' => [0],
            'item_ar_items_0_number' => '03',
            'item_ar_items_0_title' => 'Generators',
            'item_ar_items_0_desc' => 'Updated AR description',
            'item_en_items_index' => [0],
            'item_en_items_0_number' => '03',
            'item_en_items_0_title' => 'Generators',
            'item_en_items_0_desc' => 'Updated EN description',
        ];

        $this->actingAs($admin)
            ->put(route('admin.homepage.sections.update', $section), $payload)
            ->assertRedirect();

        $fresh = $section->fresh();
        $this->assertSame('/images/home/generator-maintenance-card.svg', $fresh->data_ar['items'][0]['image']);
        $this->assertSame('/images/home/generator-maintenance-card.svg', $fresh->data_en['items'][0]['image']);
        $this->assertSame('Updated AR description', $fresh->data_ar['items'][0]['desc']);
        $this->assertSame('Updated EN description', $fresh->data_en['items'][0]['desc']);
    }

    public function test_services_image_can_be_replaced_through_the_cms_field(): void
    {
        $admin = $this->admin();
        $section = HomepageSection::query()->create([
            'section_key' => 'services',
            'is_active' => true,
            'sort_order' => 30,
            'data_ar' => ['items' => [[
                'number' => '04',
                'image' => '/images/home/facility-power.svg',
                'title' => 'MV Systems',
                'desc' => 'AR desc',
            ]]],
            'data_en' => ['items' => [[
                'number' => '04',
                'image' => '/images/home/facility-power.svg',
                'title' => 'MV Systems',
                'desc' => 'EN desc',
            ]]],
        ]);

        $payload = [
            'sort_order' => 30,
            'item_ar_items_index' => [0],
            'item_ar_items_0_number' => '04',
            'item_ar_items_0_image' => '/storage/homepage-media/new-mv-ar.webp',
            'item_ar_items_0_title' => 'MV Systems',
            'item_ar_items_0_desc' => 'AR desc',
            'item_en_items_index' => [0],
            'item_en_items_0_number' => '04',
            'item_en_items_0_image' => '/storage/homepage-media/new-mv-en.webp',
            'item_en_items_0_title' => 'MV Systems',
            'item_en_items_0_desc' => 'EN desc',
        ];

        $this->actingAs($admin)
            ->put(route('admin.homepage.sections.update', $section), $payload)
            ->assertRedirect();

        $fresh = $section->fresh();
        $this->assertSame('/storage/homepage-media/new-mv-ar.webp', $fresh->data_ar['items'][0]['image']);
        $this->assertSame('/storage/homepage-media/new-mv-en.webp', $fresh->data_en['items'][0]['image']);
    }

    private function payloadFor(string $key): array
    {
        $schema = HomepageSectionSchema::fields($key);
        $payload = ['sort_order' => 777];

        foreach (['ar', 'en'] as $locale) {
            foreach ($schema['scalars'] ?? [] as $field) {
                if ($field === 'render_mode') {
                    $payload["scalar_{$locale}_{$field}"] = $locale === 'ar' ? 'full_banner' : 'background';
                } else {
                    $payload["scalar_{$locale}_{$field}"] = "{$key}-{$locale}-{$field}";
                }
            }

            foreach ($schema['checks'] ?? [] as $field) {
                $payload["check_{$locale}_{$field}"] = [
                    "{$key}-{$locale}-{$field}-1",
                    "{$key}-{$locale}-{$field}-2",
                ];
            }

            foreach ($schema['items'] ?? [] as $listKey => $itemFields) {
                $payload["item_{$locale}_{$listKey}_index"] = [3, 8];
                foreach ([3, 8] as $rowIndex) {
                    foreach ($itemFields as $field) {
                        $payload["item_{$locale}_{$listKey}_{$rowIndex}_{$field}"] = "{$key}-{$locale}-{$listKey}-{$rowIndex}-{$field}";
                    }
                }
            }
        }

        return $payload;
    }

    private function assertSectionPayloadPersisted(string $key, HomepageSection $section, array $payload): void
    {
        $schema = HomepageSectionSchema::fields($key);

        foreach (['ar', 'en'] as $locale) {
            $stored = $section->{"data_{$locale}"};

            foreach ($schema['scalars'] ?? [] as $field) {
                $this->assertSame($payload["scalar_{$locale}_{$field}"], $stored[$field]);
            }

            foreach ($schema['checks'] ?? [] as $field) {
                $this->assertSame($payload["check_{$locale}_{$field}"], $stored[$field]);
            }

            foreach ($schema['items'] ?? [] as $listKey => $itemFields) {
                $this->assertCount(2, $stored[$listKey]);
                foreach ([0 => 3, 1 => 8] as $storedIndex => $submittedIndex) {
                    foreach ($itemFields as $field) {
                        $this->assertSame(
                            $payload["item_{$locale}_{$listKey}_{$submittedIndex}_{$field}"],
                            $stored[$listKey][$storedIndex][$field]
                        );
                    }
                }
            }
        }
    }
}
