<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Release qualification for the live Customer 360 dashboard and approved command-center layout. */
class CustomerPortalExecutiveDashboardTest extends TestCase
{
    public function test_live_dashboard_uses_the_production_section_view_and_final_command_center_layer(): void
    {
        $wrapper = file_get_contents(resource_path('views/customer/section.blade.php'));
        $dashboard = file_get_contents(resource_path('views/customer/section-base.blade.php'));

        $this->assertStringContainsString("@include('customer.section-base')", $wrapper);

        foreach ([
            'Service attention is being tracked by UNIFCO',
            'No customer decision is required right now.',
            'active request SLA risk',
            'performance not yet measurable',
            'active SLA risk',
            'SERVICE & MAINTENANCE',
            'Service Request Status',
            'Work Order Status',
            'Request Workflow Distribution',
            'Contracts & SLA',
            'Financial Overview',
            'customer-side-status',
            'Customer ID:',
            'Recent Activity',
            'recent-nav-promoted',
            ".page-head,.executive-strip,.activity-panel{display:none!important}",
            "roleNote.style.display = 'none'",
        ] as $requirement) {
            $this->assertStringContainsString($requirement, $wrapper);
        }

        foreach ([
            'Customer 360 Executive Dashboard',
            'data-action-center-panel',
            'Service Request Journey',
            'Priority Today',
            'Asset Health',
            'Upcoming Visits & Maintenance',
            'Contracts & SLA',
            'Financial Summary',
            'Recent Relationship Activity',
            'SLA Compliance',
        ] as $section) {
            $this->assertStringContainsString($section, $dashboard);
        }

        // Customer decisions remain separate from UNIFCO operational exceptions.
        $this->assertStringContainsString('$actionRequiredCount', $dashboard);
        $this->assertStringContainsString('$dashboardHealth', $dashboard);
        $this->assertStringContainsString("route('customer.actions')", $dashboard);
    }
}
