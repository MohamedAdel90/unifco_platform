<?php

namespace Tests\Feature;

use App\Models\{Organization,Tenant,User};
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UatResetLinkTest extends TestCase
{
    use RefreshDatabase;

    private function externalUser(string $email): User
    {
        $tenant=Tenant::create(['name'=>'UAT Reset Tenant','code'=>'UAT-RESET','status'=>'ACTIVE']);
        $organization=Organization::create(['tenant_id'=>$tenant->id,'name'=>'UAT Reset Org','code'=>'UAT-RESET-HQ','status'=>'ACTIVE']);

        return User::create([
            'tenant_id'=>$tenant->id,
            'organization_id'=>$organization->id,
            'name'=>'Portal Test User',
            'email'=>$email,
            'password'=>'OriginalPassword!2026',
            'role'=>'CUSTOMER',
            'user_type'=>'EXTERNAL',
            'status'=>'ACTIVE',
            'locked_at'=>now(),
            'force_password_change'=>true,
        ]);
    }

    public function test_one_time_uat_link_sets_password_and_cannot_be_reused(): void
    {
        $user=$this->externalUser('portal.test@unifco.local');
        ['token'=>$token]=app(InvitationService::class)->issueOneTimeLink($user,null,30);
        $newPassword='StrongUat!2026';

        $this->get(route('uat-reset.show',['token'=>$token]))->assertOk();
        $this->post(route('uat-reset.update',['token'=>$token]),[
            'password'=>$newPassword,
            'password_confirmation'=>$newPassword,
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword,$user->password));
        $this->assertNull($user->locked_at);
        $this->assertFalse($user->force_password_change);
        $this->get(route('uat-reset.show',['token'=>$token]))->assertNotFound();
    }

    public function test_uat_link_is_rejected_for_non_local_email(): void
    {
        $user=$this->externalUser('real.customer@example.com');
        ['token'=>$token]=app(InvitationService::class)->issueOneTimeLink($user,null,30);

        $this->get(route('uat-reset.show',['token'=>$token]))->assertForbidden();
    }
}
