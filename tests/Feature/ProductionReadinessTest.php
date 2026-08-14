<?php

namespace Tests\Feature;

use App\Jobs\CreatePlatformNotification;
use App\Models\{ApiToken,Organization,PlatformNotification,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Queue};
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $tenant=Tenant::create(['name'=>'T1','code'=>'T1','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>'prod@test.local','password'=>Hash::make('Secret123!'),'role'=>'ADMIN','status'=>'ACTIVE']);
    }

    public function test_readiness_endpoint_checks_database_and_storage(): void
    {
        $this->getJson('/health/ready')->assertOk()->assertJsonPath('status','ready');
    }

    public function test_api_token_authenticates_and_is_tenant_aware(): void
    {
        $user=$this->user(); $plain='unf_test_secret';
        ApiToken::create(['tenant_id'=>$user->tenant_id,'user_id'=>$user->id,'name'=>'CI','token_hash'=>hash('sha256',$plain),'abilities'=>['status','summary']]);
        $this->withHeader('Authorization','Bearer '.$plain)->getJson('/api/v1/status')->assertOk()->assertJsonPath('data.tenant_id',$user->tenant_id);
        $this->withHeader('Authorization','Bearer wrong')->getJson('/api/v1/status')->assertUnauthorized();
    }

    public function test_notification_job_is_queueable_and_persists_when_handled(): void
    {
        $user=$this->user();
        $job=new CreatePlatformNotification($user->tenant_id,$user->id,'Deployment','Ready');
        $job->handle();
        $this->assertDatabaseHas('platform_notifications',['tenant_id'=>$user->tenant_id,'user_id'=>$user->id,'title'=>'Deployment']);
    }
}
