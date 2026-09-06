<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\User;
use App\Services\HomepageContentService;
use App\Services\HomepageSectionSchema;
use Database\Seeders\HomepageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCmsSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_homepage_section_can_be_updated_in_arabic_and_english_without_cross_section_leakage(): void
    {
        $this->seedHomepage();
        $admin = User::query()->where('role', 'ADMIN')->firstOrFail();

        foreach (array_keys(HomepageSectionSchema::all()) as $key) {
            $section = HomepageSection::query()->where('section_key', $key)->firstOrFail();
            $payload = $this->sectionPayload($key);

            $this->actingAs($admin)
                ->put(route('admin.homepage.sections.update', $section), $payload)
                ->assertRedirect(route('admin.homepage.sections.edit', $section));

            $section->refresh();
            $this->assertSectionPayloadPersisted($key, $section, $payload);
        }
    }

    public function test_every_homepage_section_supports_unsaved_preview_in_both_languages_without_persisting(): void
    {
        $this->seedHomepage();
        $admin = User::query()->where('role', 'ADMIN')->firstOrFail();

        foreach (array_keys(HomepageSectionSchema::all()) as $key) {
            $section = HomepageSection::query()->where('section_key', $key)->firstOrFail();
            $beforeAr = $section->data_ar;
            $beforeEn = $section->data_en;
            $payload = $this->sectionPayload($key);

            foreach (['ar', 'en'] as $locale) {
                $response = $this->actingAs($admin)->post(route('admin.homepage.sections.preview', $section), $payload + ['preview_locale' => $locale]);
                $response->assertOk();
            }

            $section->refresh();
            $this->assertSame($beforeAr, $section->data_ar);
            $this->assertSame($beforeEn, $section->data_en);
        }
    }

    public function test_services_editor_binds_image_controls_to_real_repeater_field_names(): void
    {
        $this->seedHomepage();
        $admin = User::query()->where('role', 'ADMIN')->firstOrFail();
        $section = HomepageSection::query()->where('section_key', 'services')->firstOrFail();

        $html = $this->actingAs($admin)->get(route('admin.homepage.sections.edit', $section))->assertOk()->getContent();
        $this->assertStringContainsString('item_ar_services_0_image', $html);
        $this->assertStringContainsString('item_en_services_0_image', $html);
    }

    public function test_services_save_preserves_existing_image_when_an_unrelated_field_is_not_submitted(): void
    {
        $this->seedHomepage();
        $admin = User::query()->where('role', 'ADMIN')->firstOrFail();
        $section = HomepageSection::query()->where('section_key', 'services')->firstOrFail();
        $ar = $section->data_ar;
        $en = $section->data_en;
        $ar['services'][0]['image'] = '/images/preserved-service.webp';
        $en['services'][0]['image'] = '/images/preserved-service.webp';
        $section->update(['data_ar' => $ar, 'data_en' => $en]);

        $payload = $this->sectionPayload('services');
        unset($payload['item_ar_services_3_image'], $payload['item_en_services_3_image']);

        $this->actingAs($admin)->put(route('admin.homepage.sections.update', $section), $payload)->assertRedirect();
        $section->refresh();
        $this->assertSame('/images/preserved-service.webp', $section->data_ar['services'][0]['image']);
        $this->assertSame('/images/preserved-service.webp', $section->data_en['services'][0]['image']);
    }

    public function test_services_image_is_shared_and_can_be_replaced_through_the_cms_field(): void
    {
        $this->seedHomepage();
        $admin = User::query()->where('role', 'ADMIN')->firstOrFail();
        $section = HomepageSection::query()->where('section_key', 'services')->firstOrFail();
        $payload = $this->sectionPayload('services');
        $payload['item_ar_services_3_image'] = '/images/new-service.webp';
        $payload['item_en_services_3_image'] = '/images/new-service.webp';

        $this->actingAs($admin)->put(route('admin.homepage.sections.update', $section), $payload)->assertRedirect();
        $section->refresh();
        $this->assertSame('/images/new-service.webp', $section->data_ar['services'][0]['image']);
        $this->assertSame('/images/new-service.webp', $section->data_en['services'][0]['image']);
    }

    public function test_changing_service_image_in_one_language_propagates_to_the_other_language_when_still_shared(): void
    {
        $this->seedHomepage();
        $admin = User::query()->where('role', 'ADMIN')->firstOrFail();
        $section = HomepageSection::query()->where('section_key', 'services')->firstOrFail();
        $payload = $this->sectionPayload('services');
        $payload['item_ar_services_3_image'] = '/images/shared-service.webp';
        $payload['item_en_services_3_image'] = '/images/shared-service.webp';

        $this->actingAs($admin)->put(route('admin.homepage.sections.update', $section), $payload)->assertRedirect();
        $section->refresh();
        $this->assertSame('/images/shared-service.webp', $section->data_ar['services'][0]['image']);
        $this->assertSame('/images/shared-service.webp', $section->data_en['services'][0]['image']);
    }

    private function seedHomepage(): void
    {
        $this->seed();
        $this->seed(HomepageContentSeeder::class);
    }

    private function sectionPayload(string $key): array
    {
        $schema = HomepageSectionSchema::fields($key);
        $payload = [];

        foreach (['ar', 'en'] as $locale) {
            foreach ($schema['scalars'] ?? [] as $field) {
                $payload["scalar_{$locale}_{$field}"] = "{$key}-{$locale}-{$field}";
            }

            foreach ($schema['checks'] ?? [] as $field) {
                $payload["check_{$locale}_{$field}"] = "{$key}-{$locale}-{$field}";
            }

            foreach ($schema['items'] ?? [] as $listKey => $itemFields) {
                foreach ([3, 8] as $rowIndex) {
                    foreach ($itemFields as $field) {
                        $value = $key === 'clients' && $field === 'image'
                            ? "{$key}-shared-{$listKey}-{$rowIndex}-{$field}"
                            : "{$key}-{$locale}-{$listKey}-{$rowIndex}-{$field}";

                        $payload["item_{$locale}_{$listKey}_{$rowIndex}_{$field}"] = $value;
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
                        $expected = $payload["item_{$locale}_{$listKey}_{$submittedIndex}_{$field}"];
                        $this->assertSame($expected, $stored[$listKey][$storedIndex][$field]);
                    }
                }
            }
        }
    }
}
