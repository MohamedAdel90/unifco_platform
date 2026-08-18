<?php

namespace Tests\Feature;

use App\Models\{Journal,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceModuleResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_module_renders_responsive_finance_workspace_with_summary_and_actions(): void
    {
        $tenant = Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org = Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $user = User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Finance User',
            'email'=>'finance@example.test','password'=>'StrongPassword123','role'=>'ADMIN','status'=>'ACTIVE',
        ]);

        Journal::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'created_by'=>$user->id,
            'journal_no'=>'JRN-POSTED','journal_date'=>now(),'description'=>'Posted test','status'=>'POSTED','posted_at'=>now(),
        ]);
        Journal::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'created_by'=>$user->id,
            'journal_no'=>'JRN-DRAFT','journal_date'=>now(),'description'=>'Draft test','status'=>'DRAFT',
        ]);

        $this->actingAs($user)->get('/modules/finance')
            ->assertOk()
            ->assertSee('Finance Workspace')
            ->assertSee('New Journal')
            ->assertSee('Finance Core')
            ->assertSee('Executive Reporting')
            ->assertSee('All Journals')
            ->assertSee('JRN-POSTED')
            ->assertSee('JRN-DRAFT')
            ->assertSee('finance-kpis', false)
            ->assertSee('@media(max-width:700px)', false)
            ->assertSee('/brand/unifco-logo.webp', false);
    }
}
