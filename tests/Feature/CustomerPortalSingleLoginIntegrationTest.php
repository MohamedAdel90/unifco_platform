<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalSingleLoginIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_exposes_full_customer_access_and_real_global_search(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $this->actingAs($user)->get('/customer')
            ->assertOk()
            ->assertSee('Search your workspace')
            ->assertSee('Open Requests')
            ->assertSee('Financial Summary')
            ->assertSee('Quick Actions')
            ->assertSee(route('public.request-service'),false);
    }

    public function test_customer_360_search_is_customer_only_and_available_from_same_login(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $this->actingAs($user)->get('/customer/search?q=SR')->assertOk()->assertSee('Customer 360 Search')->assertSee('Results are restricted to your customer account.');
    }

    public function test_internal_user_cannot_use_customer_global_search(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $internal=User::where('email','finance@unifco.local')->firstOrFail();
        $this->actingAs($internal)->get('/customer/search?q=SR')->assertForbidden();
    }
}
