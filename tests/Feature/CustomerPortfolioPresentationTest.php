<?php

namespace Tests\Feature;

use App\Models\{Customer,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerPortfolioPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'CUSTOMERS','status'=>'ACTIVE']);
        $organization=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'CUSTOMERS-HQ','status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'organization_id'=>$organization->id,'name'=>'CRM Admin','email'=>'customers@example.test','password'=>'StrongPassword123','role'=>'ADMIN','status'=>'ACTIVE']);
    }

    private function customer(User $user,array $values=[]): Customer
    {
        return Customer::create(array_merge([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_code'=>'CUS-0001',
            'name'=>'Riyadh Facilities','email'=>'contact@riyadh.test','city'=>'Riyadh','industry'=>'Facilities',
            'contact_name'=>'Ahmed Ali','status'=>'ACTIVE','onboarding_status'=>'ONBOARDING',
        ],$values));
    }

    public function test_customer_portfolio_has_metrics_filters_bilingual_content_and_safe_actions(): void
    {
        $user=$this->admin();
        $this->customer($user);
        $this->customer($user,['customer_code'=>'CUS-0002','name'=>'Blocked Customer','status'=>'BLOCKED','onboarding_status'=>'COMPLETED']);

        $this->actingAs($user)->get('/crm/customers')
            ->assertOk()
            ->assertSee('Customer Management')
            ->assertSee('إدارة العملاء')
            ->assertSee('customers-metrics',false)
            ->assertSee('customer-menu-toggle',false)
            ->assertSee('/crm/customers/export',false);
    }

    public function test_customer_filters_and_csv_export_use_the_same_query(): void
    {
        $user=$this->admin();
        $this->customer($user);
        $this->customer($user,['customer_code'=>'CUS-0002','name'=>'Jeddah Trading','email'=>'jeddah@example.test','city'=>'Jeddah']);

        $this->actingAs($user)->get('/crm/customers?q=Riyadh&city=Riyadh')
            ->assertOk()->assertSee('Riyadh Facilities')->assertDontSee('Jeddah Trading');

        $response=$this->actingAs($user)->get('/crm/customers/export?q=Riyadh');
        $response->assertOk()->assertHeader('Content-Type','text/csv; charset=UTF-8');
        $csv=$response->streamedContent();
        $this->assertStringContainsString('Riyadh Facilities',$csv);
        $this->assertStringNotContainsString('Jeddah Trading',$csv);
    }

    public function test_customer_csv_import_creates_customer_records(): void
    {
        $user=$this->admin();
        $csv="customer_code,name,email,status,onboarding_status\nCUS-0099,Imported Customer,imported@example.test,ACTIVE,ONBOARDING\n";
        $file=UploadedFile::fake()->createWithContent('customers.csv',$csv);

        $this->actingAs($user)->post('/crm/customers/import',['file'=>$file])->assertRedirect('/crm/customers');
        $this->assertDatabaseHas('customers',['tenant_id'=>$user->tenant_id,'customer_code'=>'CUS-0099','name'=>'Imported Customer']);
    }
}
