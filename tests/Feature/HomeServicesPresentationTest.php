<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeServicesPresentationTest extends TestCase
{
    public function test_homepage_services_use_unique_service_specific_artwork(): void
    {
        $images = [
            '/images/home/transformer.svg',
            '/images/home/ups.svg',
            '/images/home/generator.svg',
            '/images/home/mv-switchgear.svg',
            '/images/home/service-preventive.svg',
            '/images/home/service-corrective.svg',
            '/images/home/service-inspection.svg',
            '/images/home/service-contracts.svg',
            '/images/home/service-industrial-electrical.svg',
            '/images/home/service-asset-management.svg',
            '/images/home/service-hvac.svg',
            '/images/home/service-mep.svg',
            '/images/home/service-facility-management.svg',
        ];

        $this->assertCount(13, $images);
        $this->assertCount(13, array_unique($images));

        foreach ($images as $image) {
            $this->assertFileExists(public_path(ltrim($image, '/')));
        }

        $arabic = $this->get('/?lang=ar')->assertOk();
        $english = $this->get('/?lang=en')->assertOk();

        foreach ($images as $image) {
            $arabic->assertSee($image, false);
            $english->assertSee($image, false);
        }

        $arabic->assertSee('Transformers · المحولات الكهربائية', false)
            ->assertSee('Corrective / Emergency Maintenance', false)
            ->assertSee('Facility Management', false);

        $english->assertSee('Transformers', false)
            ->assertSee('Inspection & Testing', false)
            ->assertSee('Facility Management', false);
    }
}
