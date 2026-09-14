<?php

namespace Tests\Feature;

use App\Models\{Tenant,User};
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UatResetLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_time_uat_link_sets_password_and_cannot_be_reused(): void
    {
        $tenant=Tenant::factory()->create();
        $user=User::factory()->create([
            'tenant_id'=>$tenant->id,
            'email'=>'portal.test@unifco.local',
            'user_type'=>'EXTERNAL',
            'status'=>'ACTIVE',
            'locked_at'=>now(),
            'force_password_change'=>true,
        ]);

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
        $tenant=Tenant::factory()->create();
        $user=User::factory()->create([
            'tenant_id'=>$tenant->id,
            'email'=>'real.customer@example.com',
            'user_type'=>'EXTERNAL',
            'status'=>'ACTIVE',
        ]);
        ['token'=>$token]=app(InvitationService::class)->issueOneTimeLink($user,null,30);

        $this->get(route('uat-reset.show',['token'=>$token]))->assertForbidden();
    }
}
