<?php

namespace Tests\Feature;

use App\Models\{Customer,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProspectOnboardingRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_prospect_page_renders_verification_and_the_action_activates_customer_without_full_onboarding(): void
    {
        $tenant = Tenant::create(['name' => 'UAT', 'code' => 'PROSPECT-RENDER', 'status' => 'ACTIVE']);
        $organization = Organization::create(['tenant_id' => $tenant->id, 'name' => 'HQ', 'code' => 'HQ', 'status' => 'ACTIVE']);
        $admin = User::create([
            'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
            'name' => 'Test Admin', 'email' => 'prospect-render@example.test',
            'password' => 'TestPassword123!', 'role' => 'ADMIN', 'status' => 'ACTIVE',
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
            'customer_code' => 'PROS-RENDER', 'name' => 'UAT Prospect',
            'status' => 'PROSPECT', 'onboarding_status' => 'PENDING_VERIFICATION',
        ]);

        $this->actingAs($admin)->get(route('crm.customers.portal', $customer))
            ->assertOk()->assertSeeText('Verify & Activate Prospect')
            ->assertSee(route('crm.acquisition.verify-prospect', $customer), false);

        $this->post(route('crm.acquisition.verify-prospect', $customer))->assertRedirect();
        $customer->refresh();
        $this->assertSame('ACTIVE', $customer->status);
        $this->assertSame('ONBOARDING', $customer->onboarding_status);
        $this->assertSame('APPROVED', $customer->onboarding_review_status);
        $this->assertStringStartsWith('UN-', $customer->customer_code);
        $this->get(route('crm.customers.portal', $customer))->assertOk()
            ->assertDontSeeText('Verify & Activate Prospect')->assertSeeText('Activate Customer');
    }
}
