<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicServiceDetailsTest extends TestCase
{
    public function test_all_public_service_detail_pages_are_available(): void
    {
        $slugs = [
            'transformer-maintenance','ups-systems','generators','mv-systems','preventive-maintenance',
            'corrective-emergency-maintenance','inspection-testing','maintenance-contracts',
            'industrial-commercial-electrical','asset-management','hvac-systems','mep-services','facility-management',
        ];

        foreach ($slugs as $slug) {
            $this->get('/services/'.$slug.'?lang=en')->assertOk()->assertSee('Request Service', false);
        }
    }

    public function test_transformer_page_contains_service_specific_scope(): void
    {
        $this->get('/services/transformer-maintenance?lang=en')->assertOk()
            ->assertSee('Transformers', false)
            ->assertSee('Visual Inspection', false)
            ->assertSee('Oil Testing', false)
            ->assertSee('Insulation Resistance Testing', false)
            ->assertSee('Winding Resistance', false)
            ->assertSee('Transformer Ratio Test (TTR)', false)
            ->assertSee('Protection Testing', false)
            ->assertSee('Thermal Inspection', false)
            ->assertSee('Preventive Maintenance', false)
            ->assertSee('Corrective Maintenance', false)
            ->assertSee('Emergency Support', false);
    }

    public function test_home_service_cards_link_to_detail_pages_and_use_current_generator_photo(): void
    {
        $generatorImage = '/images/home/service-generators-unifco-20260904.webp';

        $this->get('/?lang=en')->assertOk()
            ->assertSee('/services/transformer-maintenance?lang=en', false)
            ->assertSee('/services/ups-systems?lang=en', false)
            ->assertSee('/services/facility-management?lang=en', false)
            ->assertSee($generatorImage, false);

        $this->get('/?lang=ar')->assertOk()
            ->assertSee('/services/generators?lang=ar', false)
            ->assertSee($generatorImage, false);
    }
}
