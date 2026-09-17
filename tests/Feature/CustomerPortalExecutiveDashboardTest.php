<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Release qualification for the approved Customer Portal dashboard refresh. */
class CustomerPortalExecutiveDashboardTest extends TestCase
{
    public function test_customer_portal_dashboard_contains_approved_executive_sections_and_drilldowns(): void
    {
        $view = file_get_contents(resource_path('views/customer/portal.blade.php'));

        foreach ([
            'Service Request Status',
            'Work Orders Status',
            'Asset Health',
            'Monthly Activity',
            'Contracts & SLA',
            'Financial Summary',
            'Request Trend',
            'Needs Attention',
            'Quick Actions',
            'New Service Request',
            'Emergency Maintenance',
            'Request Quotation',
            'Request Spare Parts',
            'Technical Consultation',
            'Contact UNIFCO support team.',
        ] as $section) {
            $this->assertStringContainsString($section, $view);
        }

        foreach ([
            '/request-service',
            '/request-service?type=emergency',
            '/request-service?type=quotation',
            '/request-service?type=spare_parts',
            '/request-service?type=consultation',
            '/customer/service-requests',
            '/customer/work-orders',
            '/customer/assets',
            '/customer/visits',
            '/customer/contracts',
            '/customer/finance',
            '/customer/action-center',
            '/customer/inbox',
        ] as $target) {
            $this->assertStringContainsString($target, $view);
        }

        $this->assertStringContainsString('grid-template-columns:repeat(6,minmax(0,1fr))', $view);
        $this->assertStringContainsString('grid-template-columns:1.18fr 1.18fr .96fr 1.1fr', $view);
        $this->assertStringContainsString('class="panel attention"', $view);
        $this->assertStringContainsString("dir=\"{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}\"", $view);
    }
}
