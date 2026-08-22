<?php

namespace Tests\Feature;

use App\Models\{Organization,Tenant,User};
use App\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->actingAs($admin)->get('/admin/users/'.$user->id)->assertOk()->assertSee('User-specific Permission Overrides',false)->assertSee('Reset Password',false);
        $this->actingAs($admin)->put('/admin/users/'.$user->id,['name'=>'Technician Updated','email'=>'tech1@example.test','role'=>'SUPERVISOR','status'=>'ACTIVE','organization_id'=>$org->id])->assertRedirect();
        $this->assertDatabaseHas('users',['id'=>$user->id,'tenant_id'=>$tenant->id,'name'=>'Technician Updated','role'=>'SUPERVISOR']);
        $this->actingAs($admin)->post('/admin/users/'.$user->id.'/status',['status'=>'SUSPENDED'])->assertRedirect();
        $this->assertDatabaseHas('users',['id'=>$user->id,'status'=>'SUSPENDED']);
        $this->actingAs($admin)->post('/admin/users/'.$user->id.'/reset-password',['password'=>'Changed123!','password_confirmation'=>'Changed123!'])->assertRedirect();
        $this->assertTrue(Hash::check('Changed123!',User::find($user->id)->password));
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
        [$tenantB,$orgB,$adminB]=$this->admin('B1');
        $other=User::create(['tenant_id'=>$tenantB->id,'organization_id'=>$orgB->id,'name'=>'Other Tenant User','email'=>'other@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $this->actingAs($adminA)->get('/admin/users/'.$other->id)->assertNotFound();
        $tech=User::create(['tenant_id'=>$tenantA->id,'organization_id'=>$orgA->id,'name'=>'Restricted','email'=>'restricted-admin@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $this->actingAs($tech)->get('/workspace/users')->assertForbidden();
        $this->actingAs($tech)->get('/admin/users/create')->assertForbidden();
    }

    public function test_admin_cannot_suspend_own_active_session(): void
    {
        [,,$admin]=$this->admin('SELF');
        $this->actingAs($admin)->post('/admin/users/'.$admin->id.'/status',['status'=>'SUSPENDED'])->assertSessionHasErrors('status');
        $this->assertDatabaseHas('users',['id'=>$admin->id,'status'=>'ACTIVE']);
    }
}
