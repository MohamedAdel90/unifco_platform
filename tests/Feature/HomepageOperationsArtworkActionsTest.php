<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageOperationsArtworkActionsTest extends TestCase
{
    public function test_operations_full_image_actions_target_maintenance_and_client_login(): void
    {
        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('unifco-full-card-hotspot--maintenance', $html);
        $this->assertStringContainsString('unifco-full-card-hotspot--portal', $html);
        $this->assertStringContainsString('data-operation-action', $html);
        $this->assertStringContainsString(route('public.request-service'), $html);
        $this->assertStringContainsString(route('login'), $html);
        $this->assertStringContainsString('تعرف على خدمات الصيانة', $html);
        $this->assertStringContainsString('دخول حساب العميل', $html);
    }

    public function test_english_operations_actions_use_the_same_destinations_with_english_labels(): void
    {
        $html = $this->get('/?lang=en')->assertOk()->getContent();

        $this->assertStringContainsString(route('public.request-service'), $html);
        $this->assertStringContainsString(route('login'), $html);
        $this->assertStringContainsString('Explore Maintenance Services', $html);
        $this->assertStringContainsString('Client Login', $html);
    }
}
