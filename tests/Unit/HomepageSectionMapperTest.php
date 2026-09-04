<?php

namespace Tests\Unit;

use App\Services\HomepageSectionMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HomepageSectionMapperTest extends TestCase
{
    public static function scalarMappings(): array
    {
        return [
            'hero' => ['hero', ['title' => 'New Hero'], 'hero_title', 'New Hero'],
            'about' => ['about', ['title' => 'New About'], 'about_title', 'New About'],
            'services' => ['services', ['title' => 'New Services'], 'services_title', 'New Services'],
            'process' => ['process', ['title' => 'New Process'], 'process_title', 'New Process'],
            'industries' => ['industries', ['title' => 'New Industries'], 'industries_title', 'New Industries'],
            'why' => ['why', ['title' => 'New Why'], 'why_title', 'New Why'],
            'showcase' => ['showcase', ['title' => 'New Showcase'], 'showcase_title', 'New Showcase'],
            'clients' => ['clients', ['title' => 'New Clients'], 'clients_title', 'New Clients'],
            'emergency' => ['emergency', ['title' => 'New Emergency'], 'emergency_title', 'New Emergency'],
            'footer cta' => ['footer_cta', ['title' => 'New CTA'], 'final_title', 'New CTA'],
            'footer' => ['footer', ['about' => 'New Footer'], 'footer_about', 'New Footer'],
            'operations' => ['operations', ['maintenance_title' => 'New Operations'], 'maintenance_title', 'New Operations'],
        ];
    }

    #[DataProvider('scalarMappings')]
    public function test_each_section_maps_only_to_its_public_contract(string $section, array $input, string $expectedKey, string $expectedValue): void
    {
        $mapped = HomepageSectionMapper::toPublic($section, $input);

        $this->assertSame($expectedValue, $mapped[$expectedKey]);
    }

    public function test_current_cms_value_overrides_stale_legacy_value(): void
    {
        $mapped = HomepageSectionMapper::toPublic('hero', [
            'title' => 'Edited in CMS',
            'hero_title' => 'Old legacy value',
        ]);

        $this->assertSame('Edited in CMS', $mapped['hero_title']);
        $this->assertArrayNotHasKey('title', $mapped);
    }

    public function test_hero_render_mode_is_namespaced_for_public_renderer(): void
    {
        $mapped = HomepageSectionMapper::toPublic('hero', [
            'render_mode' => 'full_banner',
            'image' => '/banner.webp',
        ]);

        $this->assertSame('full_banner', $mapped['hero_render_mode']);
        $this->assertSame('/banner.webp', $mapped['hero_image']);
        $this->assertArrayNotHasKey('render_mode', $mapped);
    }

    public function test_services_more_keeps_the_public_more_key(): void
    {
        $mapped = HomepageSectionMapper::toPublic('services', ['more' => 'Read more']);

        $this->assertSame('Read more', $mapped['more']);
        $this->assertArrayNotHasKey('services_more', $mapped);
    }

    public function test_capabilities_rows_are_normalized_without_generic_items_key(): void
    {
        $mapped = HomepageSectionMapper::toPublic('capabilities', [
            'items' => [
                ['icon' => 'settings', 'title' => 'Power', 'subtitle' => 'Electrical'],
            ],
        ]);

        $this->assertSame([['settings', 'Power', 'Electrical']], $mapped['capabilities']);
        $this->assertArrayNotHasKey('items', $mapped);
    }

    public function test_repeater_rows_keep_section_specific_order(): void
    {
        $services = HomepageSectionMapper::toPublic('services', [
            'items' => [[
                'number' => '01',
                'image' => '/image.webp',
                'title' => 'Transformers',
                'desc' => 'Maintenance',
            ]],
        ]);
        $industries = HomepageSectionMapper::toPublic('industries', [
            'items' => [[
                'image' => '/industry.webp',
                'label' => 'Healthcare',
            ]],
        ]);

        $this->assertSame([['01', '/image.webp', 'Transformers', 'Maintenance']], $services['services']);
        $this->assertSame([['Healthcare', '/industry.webp']], $industries['industries']);
        $this->assertArrayNotHasKey('items', $services);
        $this->assertArrayNotHasKey('items', $industries);
    }

    public function test_hero_image_is_language_local_and_namespaced(): void
    {
        $mapped = HomepageSectionMapper::toPublic('hero', ['image' => '/hero-ar.webp']);

        $this->assertSame('/hero-ar.webp', $mapped['hero_image']);
        $this->assertArrayNotHasKey('image', $mapped);
    }
}
