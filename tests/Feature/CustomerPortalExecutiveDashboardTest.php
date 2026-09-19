<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Release qualification for the approved Customer Portal dashboard refresh. */
class CustomerPortalExecutiveDashboardTest extends TestCase
{
    public function test_customer_portal_dashboard_contains_approved_executive_sections_and_drilldowns(): void
    {
        $view = file_get_contents(resource_path('views/customer/dashboard-v3.blade.php'));

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
            "route('customer.portal')",
            "route('customer.section','requests')",
            "route('customer.section','work-orders')",
            "route('customer.section','assets')",
            "route('customer.section','contracts')",
            "route('customer.section','visits')",
            "route('customer.section','invoices')",
            "route('customer.actions')",
            "route('customer.inbox')",
            "route('public.request-service'",
        ] as $target) {
            $this->assertStringContainsString($target, $view);
        }

        $this->assertStringContainsString('grid-template-columns:repeat(6,minmax(0,1fr))', $view);
        $this->assertStringContainsString('class="card attention"', $view);
        $this->assertStringContainsString("dir=\"{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}\"", $view);
    }

    public function test_customer_dashboard_has_one_canonical_entry_and_no_duplicate_portal_view(): void
    {
        $routes = file_get_contents(base_path('routes/public.php'));
        $dispatcher = file_get_contents(resource_path('views/customer/section.blade.php'));

        $this->assertStringContainsString("Route::get('/customer', CustomerPortalController::class)->name('customer.portal');", $routes);
        $this->assertStringContainsString("Route::get('/customer/dashboard', fn () => redirect()->route('customer.portal', [], 301))->name('customer.dashboard');", $routes);
        $this->assertStringNotContainsString("['dashboard', 'requests'", $routes);

        $this->assertStringContainsString("@include('customer.dashboard-v3')", $dispatcher);
        $this->assertStringContainsString("@include('customer.workspace')", $dispatcher);
        $this->assertFileExists(resource_path('views/customer/workspace.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/customer/portal.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/customer/section-legacy.blade.php'));
    }
}
