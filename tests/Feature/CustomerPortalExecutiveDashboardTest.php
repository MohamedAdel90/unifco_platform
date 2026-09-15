<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Release qualification for the compact Customer 360 V2.1 dashboard. */
class CustomerPortalExecutiveDashboardTest extends TestCase
{
    public function test_customer_portal_dashboard_contains_v21_executive_sections_and_drilldowns(): void
    {
        $view = file_get_contents(resource_path('views/customer/portal.blade.php'));

        foreach ([
            'Customer 360 · Operations, assets, contracts, finance and service delivery in one unified account.',
            'Service Request Journey',
            'Priority Today',
            'Asset Health',
            'Upcoming Visits & Maintenance',
            'Contracts & SLA',
            'Financial Summary',
            'Recent Relationship Activity',
            'Unified customer account',
            'No eligible completed requests yet',
            'Requires operational follow-up',
        ] as $section) {
            $this->assertStringContainsString($section, $view);
        }

        foreach ([
            '/request-service?type=emergency',
            '/request-service?type=quotation',
            '/request-service?type=spare_parts',
            '/request-service?type=consultation',
            '/customer/service-requests?status=',
            '/customer/work-orders',
            '/customer/assets',
            '/customer/visits',
            '/customer/contracts',
            '/customer/finance',
            '/customer/activity',
        ] as $target) {
            $this->assertStringContainsString($target, $view);
        }

        $this->assertStringContainsString('grid-template-columns:repeat(8,1fr)', $view);
        $this->assertStringContainsString("attention {{ \$pendingQ>0?'warn':'' }}", $view);
        $this->assertStringContainsString('server-authorized scope', $view);
    }
}
