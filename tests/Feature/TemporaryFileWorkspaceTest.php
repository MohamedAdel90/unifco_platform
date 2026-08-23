<?php

namespace Tests\Feature;

use App\Models\{Organization, Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemporaryFileWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function users(): array
    {
        $tenant = Tenant::create(['name' => 'File Tenant', 'code' => 'FILES', 'status' => 'ACTIVE']);
        $organization = Organization::create(['tenant_id' => $tenant->id, 'name' => 'File Org', 'code' => 'FILES-HQ', 'status' => 'ACTIVE']);
        $admin = User::create(['tenant_id' => $tenant->id, 'organization_id' => $organization->id, 'name' => 'File Admin', 'email' => 'file-admin@example.test', 'password' => 'password', 'role' => 'ADMIN', 'status' => 'ACTIVE']);
        $user = User::create(['tenant_id' => $tenant->id, 'organization_id' => $organization->id, 'name' => 'File User', 'email' => 'file-user@example.test', 'password' => 'password', 'role' => 'USER', 'status' => 'ACTIVE']);

        return compact('admin', 'user');
    }

    public function test_only_an_admin_can_open_the_temporary_file_workspace(): void
    {
        $users = $this->users();

        $this->get('/admin/temporary-files')->assertRedirect('/login');
        $this->actingAs($users['user'])->get('/admin/temporary-files')->assertForbidden();
        $this->actingAs($users['admin'])->get('/admin')->assertRedirect('/admin/temporary-files');
        $this->actingAs($users['admin'])->get('/admin/temporary-files')->assertOk()->assertSee('Upload Temporary File', false);
    }

    public function test_admin_can_upload_share_and_delete_a_temporary_image(): void
    {
        Storage::fake('local');
        $users = $this->users();

        $this->actingAs($users['admin'])
            ->post('/admin/temporary-files', ['file' => UploadedFile::fake()->image('homepage-reference.png', 1200, 800)])
            ->assertRedirect();

        $directories = Storage::disk('local')->directories('temporary-files');
        $this->assertCount(1, $directories);
        $token = basename($directories[0]);
        Storage::disk('local')->assertExists($directories[0].'/file.png');
        Storage::disk('local')->assertExists($directories[0].'/metadata.json');

        $this->get(route('temporary-files.show', $token))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($users['admin'])->delete('/admin/temporary-files/'.$token)->assertRedirect();
        Storage::disk('local')->assertMissing($directories[0].'/file.png');
        $this->get(route('temporary-files.show', $token))->assertNotFound();
    }

    public function test_executable_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $users = $this->users();

        $this->actingAs($users['admin'])
            ->post('/admin/temporary-files', [
                'file' => UploadedFile::fake()->create('payload.php', 2, 'application/x-httpd-php'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame([], Storage::disk('local')->directories('temporary-files'));
    }
}
