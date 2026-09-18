<?php

namespace Tests\Feature;

use App\Models\{Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsManagerDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_operations_command_center(): void
    {
        $this->get('/operations')->assertRedirect('/login');
    }

    public function test_non_operations_user_is_forbidden_from_operations_command_center(): void
    {
        $tenant = Tenant::create(['name' => 'Access Test', 'code' => 'OPS-ACCESS', 'status' => 'ACTIVE']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Standard User',
            'email' => 'standard.operations-access@example.test',
            'password' => 'password',
            'role' => 'USER',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user)->get('/operations')->assertForbidden();
    }

    public function test_operations_route_is_named_and_protected_by_auth(): void
    {
        $this->assertSame('/operations', route('operations-manager.dashboard', [], false));
    }
}
