<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerPortalDashboardV22FinalClosureTest extends TestCase
{
    public function test_v22_dashboard_closure_keeps_customer_actions_and_mobile_density_contract(): void
    {
        $source=file_get_contents(app_path('Http/Middleware/CustomerPortalDashboardPresentation.php'));

        $this->assertStringContainsString('customer-dashboard-v2.2-final-closure-20260915',$source);
        $this->assertStringContainsString('data-customer-dashboard-v22',$source);
        $this->assertStringContainsString('data-v22-timeline',$source);
        $this->assertStringContainsString('Reason: ',$source);
        $this->assertStringContainsString('grid-template-columns:1fr 1fr!important',$source);
        $this->assertStringContainsString('CustomerActivityEvent::where(\'customer_id\',$customerId)',$source);
        $this->assertStringContainsString('ServiceRequest::where(\'customer_id\',$customerId)',$source);
        $this->assertStringContainsString('FinancialDocument::where(\'customer_id\',$customerId)',$source);
    }
}
