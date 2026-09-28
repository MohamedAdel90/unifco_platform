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
        fwrite(STDERR,"INBOX_BEFORE count=".PublicServiceRequest::count()." write=".PublicServiceRequest::query()->useWritePdo()->count()." pdo=".spl_object_id(\Illuminate\Support\Facades\DB::connection()->getPdo())."\n");
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Database\Events\TransactionRolledBack::class,
            function () { fwrite(STDERR,"INBOX_ROLLBACK ".json_encode(array_slice(array_map(
                fn ($frame) => ($frame['class'] ?? '').'::'.($frame['function'] ?? ''),debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),0,15))."\n"); });
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            if (preg_match('/delete|truncate|rollback/i',$query->sql)) fwrite(STDERR,"INBOX_SQL ".$query->sql."\n");
        });
        $response=$this->actingAs($sales)->get('/admin/public-requests')->assertOk();
        fwrite(STDERR,"INBOX_AFTER count=".PublicServiceRequest::count()." write=".PublicServiceRequest::query()->useWritePdo()->count()." pdo=".spl_object_id(\Illuminate\Support\Facades\DB::connection()->getPdo())."\n");
        $this->assertStringContainsString('Website Requests',$response->getContent(),
            'Unexpected page: '.substr(strip_tags($response->getContent()),0,600));
        $response->assertSee('UNRM-926000999')->assertSee('Maintenance')
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
