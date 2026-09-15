<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerPortalExecutiveDashboardTest extends TestCase
{
    public function test_customer_portal_dashboard_contains_executive_360_sections(): void
    {
        $view = file_get_contents(resource_path('views/customer/portal.blade.php'));

        foreach ([
            'Customer 360 Executive Dashboard',
            'Service Request Journey',
            'Priority Today',
            'Asset Health',
            'Upcoming Visits & Maintenance',
            'Contracts & SLA',
            'Financial Summary',
            'Recent Relationship Activity',
            'Unified customer account',
        ] as $section) {
            $this->assertStringContainsString($section, $view);
        }

        $this->assertStringContainsString('/request-service?type=emergency', $view);
        $this->assertStringContainsString('/request-service?type=quotation', $view);
        $this->assertStringContainsString('/request-service?type=spare_parts', $view);
        $this->assertStringContainsString('/request-service?type=consultation', $view);
    }
}
