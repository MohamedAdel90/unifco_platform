<?php

namespace Tests\Feature;

use App\Models\{PublicServiceRequest,User};
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRequestAdminInboxTest extends TestCase
{
    use RefreshDatabase;

    private function salesAndLegacyRequest(): array
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $sales=User::where('email','sales@unifco.local')->firstOrFail();
        $public=PublicServiceRequest::create([
            'reference_no'=>'UNRM-926000999',
            'request_type'=>'MAINTENANCE',
            'service_category'=>'Maintenance',
            'subject'=>'Legacy maintenance request',
            'details'=>'Check the pump.',
            'urgency'=>'NORMAL',
            'company_name'=>'UNIFCO Workflow Test Customer',
            'commercial_registration'=>'WF-TEST-CR-001',
            'email'=>'workflow.customer@unifco.local',
            'mobile'=>'0500000001',
            'status'=>'NEW',
            'submitted_at'=>now(),
        ]);

        return [$sales,$public];
    }

    public function test_sales_can_open_public_requests_with_a_legacy_maintenance_row(): void
    {
        [$sales]=$this->salesAndLegacyRequest();

        $this->assertDatabaseHas('public_service_requests',['reference_no'=>'UNRM-926000999']);
        fwrite(STDERR,"INBOX_BEFORE count=".PublicServiceRequest::count()." tx=".\Illuminate\Support\Facades\DB::transactionLevel()."\n");
        $response=$this->actingAs($sales)->get('/admin/public-requests')->assertOk();
        fwrite(STDERR, "INBOX_DIAG count=".PublicServiceRequest::count()
            ." tx=".\Illuminate\Support\Facades\DB::transactionLevel()
            ." excerpt=".substr(strip_tags(substr($response->getContent(),strpos($response->getContent(),'Website Requests'))),0,1000)."\n");
        $this->assertStringContainsString('Website Requests',$response->getContent(),
            'Unexpected page: '.substr(strip_tags($response->getContent()),0,600));
        $this->assertStringContainsString('UNRM-926000999',$response->getContent(),
            'Inbox excerpt: '.substr(strip_tags(substr($response->getContent(),strpos($response->getContent(),'Website Requests'))),0,1800));
        $response->assertSee('Maintenance')
            ->assertDontSee('Emergency Maintenance');
    }

    public function test_admin_inbox_renders_an_invalid_legacy_date_without_throwing(): void
    {
        [$sales,$public]=$this->salesAndLegacyRequest();
        $this->actingAs($sales);
        // Simulate an old row whose raw values cannot be cast as dates.
        $public->setRawAttributes(array_replace($public->getAttributes(),[
            'requested_date'=>'invalid-date',
            'submitted_at'=>'invalid-timestamp',
        ]),true);

        $html=view('public.admin-requests',['requests'=>collect([$public])])->render();
        $this->assertStringContainsString('UNRM-926000999',$html);
        $this->assertStringContainsString('Legacy maintenance request',$html);
    }
}
