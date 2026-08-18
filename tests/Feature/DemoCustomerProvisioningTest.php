<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoCustomerProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_customer_can_be_provisioned_and_login_redirects_to_customer_portal(): void
    {
        $email = 'demo.customer@unifco.local';
        $password = 'DemoCustomer#2026!';

        $this->assertSame(0, Artisan::call('customer:provision-demo', [
            '--email' => $email,
            '--password' => $password,
            '--name' => 'UNIFCO Demo Customer',
        ]));

        $user = User::where('email', $email)->firstOrFail();
        $this->assertSame('CUSTOMER', $user->role);
        $this->assertSame('ACTIVE', $user->status);
        $this->assertNotNull($user->customer_id);

        $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ])->assertRedirect(route('customer.portal'));
    }

    public function test_demo_customer_command_rejects_short_passwords(): void
    {
        $this->assertSame(1, Artisan::call('customer:provision-demo', [
            '--password' => 'short',
        ]));
    }
}
