<?php

namespace Tests\Feature;

use App\Models\{ApiToken,Document,PlatformNotification,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_status_api_is_tenant_aware(): void
    {
        $this->seed();
        $user=User::firstOrFail();
        $plain='unf_test_platform_status';
        ApiToken::create([
            'tenant_id'=>$user->tenant_id,
            'user_id'=>$user->id,
            'name'=>'Platform services test',
            'token_hash'=>hash('sha256',$plain),
            'abilities'=>['status'],
        ]);

        $this->withHeader('Authorization','Bearer '.$plain)
            ->getJson('/api/v1/status')
            ->assertOk()
            ->assertJsonPath('data.tenant_id',$user->tenant_id);
    }

    public function test_document_upload_is_private_and_tenant_scoped(): void
    {
        Storage::fake('local');
        $this->seed();
        $user=User::firstOrFail();

        $this->actingAs($user)->post('/platform/documents',[
            'document_no'=>'DOC-001',
            'title'=>'Test',
            'file'=>UploadedFile::fake()->create('test.pdf',20,'application/pdf'),
        ])->assertRedirect();

        $document=Document::firstOrFail();
        Storage::disk('local')->assertExists($document->storage_path);

        $otherTenant=Tenant::create(['name'=>'Other','code'=>'OTHER','status'=>'ACTIVE']);
        $other=User::create([
            'tenant_id'=>$otherTenant->id,
            'name'=>'Other',
            'email'=>'other@example.test',
            'password'=>'password',
            'role'=>'ADMIN',
            'status'=>'ACTIVE',
        ]);

        $this->actingAs($other)
            ->get('/platform/documents/'.$document->id.'/download')
            ->assertNotFound();
    }

    public function test_user_can_mark_only_own_notification_as_read(): void
    {
        $this->seed();
        $user=User::firstOrFail();
        $notification=PlatformNotification::create([
            'tenant_id'=>$user->tenant_id,
            'user_id'=>$user->id,
            'title'=>'Ready',
        ]);

        $this->actingAs($user)
            ->post('/platform/notifications/'.$notification->id.'/read')
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
