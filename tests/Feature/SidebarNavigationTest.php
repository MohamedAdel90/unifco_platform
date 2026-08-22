<?php

namespace Tests\Feature;

use App\Models\{Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $tenant=Tenant::create(['name'=>'Navigation Tenant','code'=>'NAV','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'NAV-HQ','status'=>'ACTIVE']);
        return User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Navigation Admin',
            'email'=>'navigation@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE',
        ]);
    }

    public function test_admin_sidebar_renders_grouped_platform_navigation(): void
    {
        $this->actingAs($this->admin())->get('/dashboard')
            ->assertOk()
            ->assertSee('side-nav-search', false)
            ->assertSee('Customers & Service', false)
            ->assertSee('Maintenance & Field Service', false)
            ->assertSee('Assets & EAM', false)
            ->assertSee('Inventory & Warehouses', false)
            ->assertSee('Procurement')
            ->assertSee('Finance')
            ->assertSee('Projects & Manufacturing', false)
            ->assertSee('People')
            ->assertSee('Insights & Intelligence', false)
            ->assertSee('Administration')
            ->assertSee('+ Work Order')
            ->assertSee('+ Asset')
            ->assertSee('+ Customer')
            ->assertSee('Asset 360', false)
            ->assertSee('Preventive Maintenance', false)
            ->assertSee('Purchase Requisitions', false)
            ->assertSee('Materials / BOM', false)
            ->assertSee('System Settings', false);
    }

    public function test_sidebar_enhancer_is_available_outside_dashboard(): void
    {
        $this->actingAs($this->admin())->get('/workspace/asset-360')
            ->assertOk()
            ->assertSee('Asset 360', false)
            ->assertSee('Open Operations', false)
            ->assertSee('unifco-language-switcher', false)
            ->assertSee('/workspace/predictive-analytics', false);
    }

    public function test_operational_wave_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('inventory.operations.index'));
        $this->assertTrue(\Route::has('procurement.operations.index'));
        $this->assertTrue(\Route::has('hr.operations.index'));
        $this->assertTrue(\Route::has('manufacturing.operations.index'));
        $this->assertTrue(\Route::has('maintenance.operations.index'));
        $this->assertTrue(\Route::has('workspace.show'));
    }

    public function test_sidebar_keeps_mobile_menu_and_active_navigation_hooks(): void
    {
        $this->actingAs($this->admin())->get('/dashboard')
            ->assertOk()
            ->assertSee('data-open-side', false)
            ->assertSee('data-close-side', false)
            ->assertSee('active-group', false)
            ->assertSee('side-nav-empty', false);
    }
}
