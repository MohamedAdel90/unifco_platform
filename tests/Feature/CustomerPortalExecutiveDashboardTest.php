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

        $this->assertNotFalse($wrapper);
        $this->assertNotFalse($dashboard);
        $this->assertStringContainsString("@include('customer.section-base')", $wrapper);

        // Final customer command-center layer: customer identity stays in the sidebar,
        // operational graphs use the dashboard canvas, and recent activity is promoted
        // to sidebar navigation rather than duplicated as a large dashboard panel.
        foreach ([
            'customer-side-status',
            'Customer ID:',
            'Service Request Status',
            'Work Order Status',
            'Request Workflow Distribution',
            'Contracts & SLA',
            'Financial Overview',
            'recent-nav-promoted',
            '.page-head,.executive-strip,.activity-panel{display:none!important}',
        ] as $requirement) {
            $this->assertStringContainsString($requirement, $wrapper);
        }

        // The underlying production dashboard remains intact and supplies the live
        // customer data/links used by the final presentation layer.
        foreach ([
            'data-action-center-panel',
            'Service Request Journey',
            'Priority Today',
            'Asset Health',
            'Upcoming Visits & Maintenance',
            'Contracts & SLA',
            'Financial Summary',
            'Recent Relationship Activity',
        ] as $section) {
            $this->assertStringContainsString($section, $dashboard);
        }

        // Customer decisions remain separate from UNIFCO operational exceptions.
        // The production dashboard now represents operational health directly through
        // live overdue/in-progress work-order counters rather than the removed
        // $dashboardHealth presentation variable.
        $this->assertStringContainsString('$actionRequiredCount', $dashboard);
        $this->assertStringContainsString('$overdueCount', $dashboard);
        $this->assertStringContainsString('$inProgressCount', $dashboard);
        $this->assertStringContainsString("route('customer.actions')", $dashboard);
    }
}
