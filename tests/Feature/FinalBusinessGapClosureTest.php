<?php

namespace Tests\Feature;

use App\Jobs\CreatePlatformNotification;
use App\Models\{Asset,Customer,Organization,ReportSubscription,Tenant,User,WorkOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Artisan,Bus,DB};
use Tests\TestCase;

class FinalBusinessGapClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_accept_only_own_completed_work(): void
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'ACC','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'ACC-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C-1','name'=>'Customer','email'=>'customer@example.test','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>'Customer User','email'=>'customer.user@example.test','password'=>'StrongPassword123','role'=>'CUSTOMER','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'asset_code'=>'GEN-A','name'=>'Generator','status'=>'REGISTERED']);
        $wo=WorkOrder::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'work_order_no'=>'WO-ACC-1','asset_id'=>$asset->id,'maintenance_type'=>'CORRECTIVE','priority'=>'NORMAL','status'=>'COMPLETED','completed_at'=>now()]);

        $this->actingAs($user)->get('/customer/work-acceptance')->assertOk()->assertSee('WO-ACC-1');
        $this->actingAs($user)->post('/customer/work-orders/'.$wo->id.'/acceptance',['decision'=>'ACCEPT','notes'=>'Work verified'])->assertRedirect();
        $this->assertDatabaseHas('work_orders',['id'=>$wo->id,'customer_acceptance_notes'=>'Work verified']);
        $this->assertNotNull($wo->fresh()->customer_accepted_at);
    }

    public function test_executive_report_exports_and_due_subscription_dispatches_notification(): void
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'REP','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'REP-HQ','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Executive','email'=>'exec@example.test','password'=>'StrongPassword123','role'=>'ADMIN','status'=>'ACTIVE']);
        DB::table('role_permissions')->insert(['tenant_id'=>$tenant->id,'role_code'=>'ADMIN','permission_code'=>'reporting.executive.read','created_at'=>now(),'updated_at'=>now()]);

        $this->actingAs($user)->get('/reports/executive.csv')->assertOk()->assertHeader('Content-Type','text/csv; charset=UTF-8')->assertSee('Posted Journals');
        $this->actingAs($user)->post('/reports/subscriptions',['frequency'=>'WEEKLY','delivery_channel'=>'IN_APP'])->assertRedirect();
        $subscription=ReportSubscription::firstOrFail(); $subscription->update(['next_delivery_at'=>now()->subMinute()]);

        Bus::fake(); Artisan::call('unifco:deliver-reports');
        Bus::assertDispatched(CreatePlatformNotification::class);
        $this->assertNotNull($subscription->fresh()->last_delivered_at);
        $this->assertTrue($subscription->fresh()->next_delivery_at->isFuture());
    }
}
