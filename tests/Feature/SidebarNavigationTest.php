<?php

namespace Tests\Feature;

use App\Models\{Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;
    private function admin(): User { $tenant=Tenant::create(['name'=>'Navigation Tenant','code'=>'NAV','status'=>'ACTIVE']); $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'NAV-HQ','status'=>'ACTIVE']); return User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Navigation Admin','email'=>'navigation@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']); }
    public function test_admin_sidebar_renders_grouped_platform_navigation(): void { $this->actingAs($this->admin())->get('/dashboard')->assertOk()->assertSee('side-nav-search',false)->assertSee('Customers & Service',false)->assertSee('Maintenance & Field Service',false)->assertSee('Assets & EAM',false)->assertSee('Inventory & Warehouses',false)->assertSee('Procurement')->assertSee('Finance')->assertSee('Projects & Manufacturing',false)->assertSee('People')->assertSee('Insights & Intelligence',false)->assertSee('Administration')->assertSee('+ Work Order')->assertSee('+ Asset')->assertSee('+ Customer')->assertSee('Asset 360',false)->assertSee('Preventive Maintenance',false)->assertSee('Purchase Requisitions',false)->assertSee('Materials / BOM',false)->assertSee('System Settings',false)->assertSee('Recruitment & Onboarding',false)->assertSee('/hr/recruitment',false)->assertSee('End of Service',false)->assertSee('/hr/settlements',false); }
    public function test_sidebar_enhancer_is_available_outside_dashboard(): void { $this->actingAs($this->admin())->get('/workspace/asset-360')->assertOk()->assertSee('Asset 360',false)->assertSee('Open Operations',false)->assertSee('unifco-language-switcher',false)->assertSee('/workspace/predictive-analytics',false); }
    public function test_users_workspace_is_real_administration_screen(): void { $admin=$this->admin(); User::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'name'=>'Field User','email'=>'field@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']); $this->actingAs($admin)->get('/workspace/users')->assertOk()->assertSee('User Management',false)->assertSee('Total Users',false)->assertSee('Field User',false)->assertSee('Roles & Permissions',false)->assertDontSee('Workspace Overview',false)->assertDontSee('Continue to Operational Page',false); }
    public function test_non_admin_cannot_open_users_workspace(): void { $admin=$this->admin(); $user=User::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'name'=>'Restricted User','email'=>'restricted@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']); $this->actingAs($user)->get('/workspace/users')->assertForbidden(); }
    public function test_operational_wave_routes_are_registered(): void { $this->assertTrue(\Route::has('inventory.operations.index')); $this->assertTrue(\Route::has('procurement.operations.index')); $this->assertTrue(\Route::has('hr.operations.index')); $this->assertTrue(\Route::has('manufacturing.operations.index')); $this->assertTrue(\Route::has('maintenance.operations.index')); $this->assertTrue(\Route::has('workspace.show')); $this->assertTrue(\Route::has('hr.recruitment.index')); $this->assertTrue(\Route::has('hr.settlements.index')); }
    public function test_sidebar_keeps_mobile_menu_and_active_navigation_hooks(): void { $this->actingAs($this->admin())->get('/dashboard')->assertOk()->assertSee('data-open-side',false)->assertSee('data-close-side',false)->assertSee('active-group',false)->assertSee('side-nav-empty',false); }
}
