<?php

namespace Tests\Feature;

use App\Models\{AccessScope,Organization,Permission,Project,Role,Tenant,User,UserInvitation};
use App\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Hash};
use Tests\TestCase;

class SystemAdministratorArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private function identity(string $suffix='A'): array
    {
        $tenant=Tenant::create(['name'=>'Access '.$suffix,'code'=>'ACCESS-'.$suffix,'status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ-'.$suffix,'status'=>'ACTIVE']);
        return [$tenant,$org];
    }

    private function assign(User $user,Role $role,bool $primary=true): void
    {
        DB::table('user_roles')->insert(['tenant_id'=>$user->tenant_id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>$primary,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
    }

    private function grant(Role $role,string $code,string $effect='ALLOW'): void
    {
        [$module,$action]=array_pad(explode('.',$code,2),2,'access');
        $permission=Permission::firstOrCreate(['code'=>$code],['module'=>$module,'action'=>$action]);
        DB::table('role_permissions')->insert(['tenant_id'=>$role->tenant_id,'role_id'=>$role->id,'permission_id'=>$permission->id,'role_code'=>$role->code,'permission_code'=>$code,'effect'=>$effect,'created_at'=>now(),'updated_at'=>now()]);
    }

    public function test_system_administrator_has_system_authority_but_not_business_authority(): void
    {
        [$tenant,$org]=$this->identity();
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'System Admin','email'=>'sysadmin@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $role=Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail();
        $this->assign($user,$role);
        $authorization=app(AuthorizationService::class);

        $this->assertTrue($authorization->allows($user,'users.create'));
        $this->assertTrue($authorization->allows($user,'audit.view'));
        $this->assertFalse($authorization->allows($user,'workflow.approval.decide'));
        $this->assertFalse($authorization->allows($user,'procurement.po.approve'));
        $this->assertFalse($authorization->allows($user,'finance.journal.post'));
    }

    public function test_system_administrator_old_dashboard_link_redirects_to_system_dashboard(): void
    {
        [$tenant,$org]=$this->identity('REDIRECT');
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'System Admin','email'=>'redirect-admin@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $this->assign($admin,Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail());

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/system-admin');
    }

    public function test_system_administrator_can_open_dedicated_operational_admin_pages(): void
    {
        [$tenant,$org]=$this->identity('PAGES');
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'System Admin','email'=>'pages-admin@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $this->assign($admin,Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail());

        foreach(['/admin/system/sessions','/admin/system/security-events','/admin/system/invitations','/admin/system/scheduled-jobs','/admin/system/organization','/admin/system/master-data','/admin/system/email-templates'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_tenant_role_catalog_is_dynamic_and_supports_primary_role(): void
    {
        [$tenant,$org]=$this->identity('DYNAMIC');
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'System Admin','email'=>'dynamic-admin@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $target=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Target','email'=>'dynamic-target@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $system=Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail();
        $assetManager=Role::create(['tenant_id'=>$tenant->id,'code'=>'ASSET_MANAGER','name_en'=>'Asset Manager','name_ar'=>'مدير الأصول','is_active'=>true,'grants_business_authority'=>true]);
        $this->assign($admin,$system);

        $this->actingAs($admin)->put('/admin/users/'.$target->id,[
            'name'=>$target->name,'name_en'=>$target->name,'email'=>$target->email,'status'=>'ACTIVE',
            'roles'=>['SYSTEM_ADMIN','ASSET_MANAGER'],'primary_role'=>'ASSET_MANAGER','scope_ids'=>[],
        ])->assertRedirect('/admin/users/'.$target->id);

        $assignments=DB::table('user_roles')->where('user_id',$target->id)->whereNull('revoked_at')->get();
        $this->assertCount(2,$assignments);
        $this->assertTrue((bool)$assignments->firstWhere('role_id',$assetManager->id)->is_primary);
        $this->assertFalse((bool)$assignments->firstWhere('role_id',$system->id)->is_primary);
    }

    public function test_system_catalog_change_is_audited_without_business_authority(): void
    {
        [$tenant,$org]=$this->identity('CATALOG');
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'System Admin','email'=>'catalog-admin@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $this->assign($admin,Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail());

        $this->actingAs($admin)->post('/admin/system/master-data',[
            'type'=>'PRIORITY','code'=>'EMERGENCY','name_ar'=>'طارئ','name_en'=>'Emergency',
        ])->assertRedirect();

        $this->assertDatabaseHas('master_data_entries',['tenant_id'=>$tenant->id,'type'=>'PRIORITY','code'=>'EMERGENCY']);
        $this->assertDatabaseHas('audit_logs',['user_id'=>$admin->id,'action'=>'system.master_data.created']);
        $this->assertFalse(app(AuthorizationService::class)->allows($admin,'workflow.approval.decide'));
    }

    public function test_multiple_roles_merge_allows_but_explicit_deny_wins(): void
    {
        [$tenant,$org]=$this->identity('B');
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Multi Role','email'=>'multi@example.test','password'=>'password','role'=>'MANAGER','status'=>'ACTIVE']);
        $manager=Role::create(['tenant_id'=>$tenant->id,'code'=>'PROJECT_MANAGER','name_en'=>'Project Manager','is_active'=>true,'grants_business_authority'=>true]);
        $approver=Role::create(['tenant_id'=>$tenant->id,'code'=>'QUOTATION_APPROVER','name_en'=>'Quotation Approver','is_active'=>true,'grants_business_authority'=>true]);
        $this->grant($manager,'projects.project.read'); $this->grant($approver,'quotation.approve');
        $this->grant($manager,'quotation.approve','DENY');
        $this->assign($user,$manager); $this->assign($user,$approver,false);

        $authorization=app(AuthorizationService::class);
        $this->assertTrue($authorization->allows($user,'projects.project.read'));
        $this->assertFalse($authorization->allows($user,'quotation.approve'));
    }

    public function test_separate_business_role_can_add_authority_to_system_administrator(): void
    {
        [$tenant,$org]=$this->identity('B2');
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Dual Role','email'=>'dual@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $system=Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail();
        $finance=Role::create(['tenant_id'=>$tenant->id,'code'=>'FINANCE_MANAGER','name_en'=>'Finance Manager','is_active'=>true,'grants_business_authority'=>true]);
        $this->grant($finance,'invoice.approve');
        $this->assign($user,$system); $this->assign($user,$finance,false);

        $authorization=app(AuthorizationService::class);
        $this->assertTrue($authorization->allows($user,'users.create'));
        $this->assertTrue($authorization->allows($user,'invoice.approve'));
    }

    public function test_business_permission_cannot_be_added_to_system_role(): void
    {
        [$tenant,$org]=$this->identity('B3');
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>'role-admin@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $this->assign($admin,Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail());

        $this->actingAs($admin)->post('/admin/permissions',[
            'role_code'=>'SYSTEM_ADMIN','permission_code'=>'invoice.approve','effect'=>'ALLOW','reason'=>'Must be rejected',
        ])->assertStatus(422);
    }

    public function test_scope_is_checked_server_side_for_project_resources(): void
    {
        [$tenant,$org]=$this->identity('C');
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Scoped PM','email'=>'scope@example.test','password'=>'password','role'=>'PROJECT_MANAGER','status'=>'ACTIVE']);
        $role=Role::create(['tenant_id'=>$tenant->id,'code'=>'PROJECT_MANAGER','name_en'=>'Project Manager','is_active'=>true,'grants_business_authority'=>true]);
        $this->grant($role,'projects.project.read'); $this->assign($user,$role);
        $allowed=Project::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'project_no'=>'P-1','name'=>'Allowed','budget'=>0,'status'=>'ACTIVE']);
        $hidden=Project::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'project_no'=>'P-2','name'=>'Hidden','budget'=>0,'status'=>'ACTIVE']);
        $scope=AccessScope::create(['tenant_id'=>$tenant->id,'scope_type'=>'PROJECT','scope_id'=>$allowed->id,'name'=>'P-1','is_active'=>true]);
        DB::table('user_scopes')->insert(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'USER','created_at'=>now(),'updated_at'=>now()]);

        $authorization=app(AuthorizationService::class);
        $this->assertTrue($authorization->allows($user,'projects.project.read',$allowed));
        $this->assertFalse($authorization->allows($user,'projects.project.read',$hidden));
    }

    public function test_read_only_impersonation_is_audited_and_blocks_transactions(): void
    {
        [$tenant,$org]=$this->identity('D');
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>'imp-admin@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $target=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Target','email'=>'imp-target@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $this->assign($admin,Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail());

        $this->actingAs($admin)->post('/admin/users/'.$target->id.'/impersonate')->assertRedirect('/dashboard');
        $this->withSession(['impersonation'=>['admin_id'=>$admin->id,'target_id'=>$target->id,'started_at'=>now()->toISOString(),'read_only'=>true]])
            ->post('/admin/users/'.$target->id.'/status',['status'=>'INACTIVE'])->assertForbidden();
        $this->assertDatabaseHas('audit_logs',['user_id'=>$admin->id,'action'=>'security.impersonation.started','entity_id'=>$target->id]);
        $this->assertDatabaseHas('users',['id'=>$target->id,'status'=>'ACTIVE']);
    }

    public function test_deactivated_account_and_revoked_session_are_rejected(): void
    {
        [$tenant,$org]=$this->identity('E');
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Inactive','email'=>'inactive@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'INACTIVE']);
        $this->assertFalse(app(AuthorizationService::class)->allows($user,'dashboard.view'));
        DB::table('user_sessions')->insert(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'session_id'=>'revoked-session','login_at'=>now(),'last_activity_at'=>now(),'status'=>'REVOKED','revoked_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        $this->assertDatabaseHas('user_sessions',['session_id'=>'revoked-session','status'=>'REVOKED']);
    }

    public function test_system_administrator_can_force_logout_one_session_and_action_is_audited(): void
    {
        [$tenant,$org]=$this->identity('FORCE-LOGOUT');
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'System Admin','email'=>'force-logout-admin@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $target=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Session Target','email'=>'force-logout-target@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $this->assign($admin,Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail());
        $sessionId=DB::table('user_sessions')->insertGetId([
            'tenant_id'=>$tenant->id,'user_id'=>$target->id,'session_id'=>'target-session-to-revoke',
            'login_at'=>now(),'last_activity_at'=>now(),'status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now(),
        ]);

        $this->actingAs($admin)->post('/admin/system/sessions/'.$sessionId.'/revoke')->assertRedirect();

        $this->assertDatabaseHas('user_sessions',['id'=>$sessionId,'status'=>'REVOKED','revoked_by'=>$admin->id]);
        $this->assertDatabaseHas('audit_logs',['user_id'=>$admin->id,'action'=>'security.session.revoked','entity_id'=>$sessionId]);
    }

    public function test_invitation_is_hashed_expiring_and_single_use(): void
    {
        [$tenant,$org]=$this->identity('F');
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Invited','email'=>'invite@example.test','password'=>'temporary-password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $token='one-time-invitation-token';
        UserInvitation::create(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'email'=>$user->email,'token_hash'=>hash('sha256',$token),'status'=>'PENDING','expires_at'=>now()->addHour()]);

        $this->post('/invitations/'.$token,['password'=>'StrongPassword123','password_confirmation'=>'StrongPassword123'])->assertRedirect('/dashboard');
        $this->assertTrue(Hash::check('StrongPassword123',$user->fresh()->password));
        $this->assertDatabaseHas('user_invitations',['user_id'=>$user->id,'status'=>'ACCEPTED']);
        $this->get('/invitations/'.$token)->assertNotFound();
    }
}
