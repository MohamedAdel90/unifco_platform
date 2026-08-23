<?php

namespace Tests\Feature;

use App\Models\{Organization,Tenant,User};
use App\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $code='UA'): array
    {
        $tenant=Tenant::create(['name'=>$code.' Tenant','code'=>$code,'status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>$code.'-HQ','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>strtolower($code).'@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        return [$tenant,$org,$admin];
    }

    public function test_admin_can_create_edit_suspend_reset_and_open_profile(): void
    {
        [$tenant,$org,$admin]=$this->admin();
        $this->actingAs($admin)->post('/admin/users',['name'=>'Technician One','email'=>'tech1@example.test','password'=>'Password123!','password_confirmation'=>'Password123!','role'=>'TECHNICIAN','status'=>'ACTIVE','organization_id'=>$org->id])->assertRedirect();
        $user=User::where('email','tech1@example.test')->firstOrFail();
        $this->assertTrue($user->force_password_change);
        $this->actingAs($admin)->get('/admin/users/'.$user->id)->assertOk()->assertSee('User-specific Permission Overrides',false)->assertSee('Security Summary',false)->assertSee('API Tokens',false)->assertSee('Recent Audit Timeline',false);
        $this->actingAs($admin)->put('/admin/users/'.$user->id,['name'=>'Technician Updated','email'=>'tech1@example.test','role'=>'SUPERVISOR','status'=>'ACTIVE','organization_id'=>$org->id])->assertRedirect();
        $this->assertDatabaseHas('users',['id'=>$user->id,'tenant_id'=>$tenant->id,'name'=>'Technician Updated','role'=>'SUPERVISOR']);
        $before=User::find($user->id)->session_version;
        $this->actingAs($admin)->post('/admin/users/'.$user->id.'/status',['status'=>'SUSPENDED'])->assertRedirect();
        $this->assertDatabaseHas('users',['id'=>$user->id,'status'=>'SUSPENDED']);
        $this->assertGreaterThan($before,User::find($user->id)->session_version);
        $this->actingAs($admin)->post('/admin/users/'.$user->id.'/reset-password',['password'=>'Changed123!','password_confirmation'=>'Changed123!'])->assertRedirect();
        $fresh=User::find($user->id);
        $this->assertTrue(Hash::check('Changed123!',$fresh->password));
        $this->assertTrue($fresh->force_password_change);
    }

    public function test_permission_override_changes_effective_access(): void
    {
        [$tenant,$org,$admin]=$this->admin('UP');
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Tech','email'=>'up-tech@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $this->actingAs($admin)->post('/admin/users/'.$user->id.'/permission',['permission_code'=>'finance.journal.read','effect'=>'ALLOW'])->assertRedirect();
        $this->assertTrue(app(AuthorizationService::class)->allows($user->fresh(),'finance.journal.read'));
        $this->actingAs($admin)->post('/admin/users/'.$user->id.'/permission',['permission_code'=>'finance.journal.read','effect'=>'DENY'])->assertRedirect();
        $this->assertFalse(app(AuthorizationService::class)->allows($user->fresh(),'finance.journal.read'));
    }

    public function test_user_admin_is_tenant_scoped_and_non_admin_is_blocked(): void
    {
        [$tenantA,$orgA,$adminA]=$this->admin('A1');
        [$tenantB,$orgB]=$this->admin('B1');
        $other=User::create(['tenant_id'=>$tenantB->id,'organization_id'=>$orgB->id,'name'=>'Other Tenant User','email'=>'other@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $this->actingAs($adminA)->get('/admin/users/'.$other->id)->assertNotFound();
        $tech=User::create(['tenant_id'=>$tenantA->id,'organization_id'=>$orgA->id,'name'=>'Restricted','email'=>'restricted-admin@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $this->actingAs($tech)->get('/workspace/users')->assertForbidden();
        $this->actingAs($tech)->get('/admin/users/create')->assertForbidden();
    }

    public function test_admin_cannot_suspend_lock_or_revoke_own_active_session(): void
    {
        [,,$admin]=$this->admin('SELF');
        $this->actingAs($admin)->post('/admin/users/'.$admin->id.'/status',['status'=>'SUSPENDED'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->post('/admin/users/'.$admin->id.'/security',['action'=>'LOCK'])->assertSessionHasErrors('security');
        $this->actingAs($admin)->post('/admin/users/'.$admin->id.'/security',['action'=>'REVOKE_SESSIONS'])->assertSessionHasErrors('security');
        $this->assertDatabaseHas('users',['id'=>$admin->id,'status'=>'ACTIVE','locked_at'=>null]);
    }

    public function test_session_revocation_invalidates_existing_authenticated_session(): void
    {
        [$tenant,$org,$admin]=$this->admin('SESS');
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Session User','email'=>'session@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE','session_version'=>1]);
        $this->actingAs($admin)->post('/admin/users/'.$user->id.'/security',['action'=>'REVOKE_SESSIONS'])->assertRedirect();
        $this->assertSame(2,User::find($user->id)->session_version);
        $this->actingAs($user)->withSession(['account_session_version'=>1])->get('/dashboard')->assertRedirect('/login');
    }

    public function test_csv_import_and_export_are_tenant_scoped(): void
    {
        [$tenant,$org,$admin]=$this->admin('CSV');
        $csv="name,email,role,status,organization_code,employee_no,password\nImported User,imported@example.test,TECHNICIAN,ACTIVE,CSV-HQ,,Password123!\n";
        $file=UploadedFile::fake()->createWithContent('users.csv',$csv);
        $this->actingAs($admin)->post('/admin/users/import',['file'=>$file])->assertRedirect();
        $this->assertDatabaseHas('users',['tenant_id'=>$tenant->id,'email'=>'imported@example.test','role'=>'TECHNICIAN']);
        $response=$this->actingAs($admin)->get('/admin/users/export/csv');
        $response->assertOk();
        $this->assertStringContainsString('imported@example.test',$response->streamedContent());
    }

    public function test_successful_login_records_last_login_and_session_version(): void
    {
        [,,$admin]=$this->admin('LOGIN');
        $this->post('/login',['email'=>$admin->email,'password'=>'password'])->assertRedirect();
        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertSame((int)$admin->fresh()->session_version,(int)session('account_session_version'));
    }
}
