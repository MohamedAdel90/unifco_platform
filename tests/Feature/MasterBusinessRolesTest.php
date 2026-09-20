<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MasterBusinessRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_business_roles_are_seeded(): void
    {
        $expected=[
            'CEO','OPERATIONS_MANAGER','MAINTENANCE_MANAGER','PROJECT_MANAGER',
            'MAINTENANCE_ENGINEER','TECHNICAL_SUPERVISOR','TECHNICIAN','QUALITY','HSE',
            'FINANCE_MANAGER','ACCOUNTANT','CUSTOMER_SERVICE','SALES','PROCUREMENT',
            'TENDERS_CONTRACTS','CUSTOMER',
        ];

        $actual=DB::table('roles')->whereNull('tenant_id')->whereIn('code',$expected)->where('is_active',true)->pluck('code')->all();

        $this->assertEmpty(array_diff($expected,$actual));
    }

    public function test_unified_customer_role_has_customer_permissions(): void
    {
        $roleId=DB::table('roles')->whereNull('tenant_id')->where('code','CUSTOMER')->value('id');
        $this->assertNotNull($roleId);

        $permissions=DB::table('role_permissions')
            ->where('role_id',$roleId)->where('effect','ALLOW')
            ->pluck('permission_code')->all();

        $this->assertContains('customer.dashboard.view',$permissions);
        $this->assertContains('customer.requests.create',$permissions);
        $this->assertContains('customer.invoices.view',$permissions);
        $this->assertContains('customer.documents.view',$permissions);
    }

    public function test_legacy_customer_persona_roles_are_retired(): void
    {
        $active=DB::table('roles')->whereIn('code',[
            'CUSTOMER_ADMIN','CUSTOMER_SITE_MANAGER','CUSTOMER_FINANCE','CUSTOMER_VIEWER'
        ])->where('is_active',true)->count();

        $this->assertSame(0,$active);
    }
}
