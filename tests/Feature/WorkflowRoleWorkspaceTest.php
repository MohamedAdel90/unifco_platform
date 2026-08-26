<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRoleWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_workflow_role_gets_its_own_workspace(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $expected=[
            'engineer@unifco.local'=>'Maintenance Engineer Workspace',
            'maintenance.manager@unifco.local'=>'Maintenance Manager Workspace',
            'procurement@unifco.local'=>'Procurement Workspace',
            'tenders@unifco.local'=>'Tenders & Contracts Workspace',
            'finance@unifco.local'=>'Finance Workspace',
            'projects.manager@unifco.local'=>'Project Manager Workspace',
            'ceo@unifco.local'=>'CEO Executive Workspace',
        ];

        foreach($expected as $email=>$title){
            $user=User::where('email',$email)->firstOrFail();
            $this->actingAs($user)->get('/workflow/workspace')->assertOk()->assertSee($title);
            $this->actingAs($user)->get('/')->assertRedirect('/workflow/workspace');
        }
    }

    public function test_login_redirects_internal_workflow_roles_to_workspace_and_customer_to_customer_portal(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);

        $this->post('/login',['email'=>'maintenance.manager@unifco.local','password'=>'UnifcoWorkflow!2026'])
            ->assertRedirect('/workflow/workspace');
        $this->post('/logout');

        $this->post('/login',['email'=>'workflow.customer@unifco.local','password'=>'UnifcoWorkflow!2026'])
            ->assertRedirect('/customer');
    }

    public function test_workspaces_do_not_show_another_roles_identity(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $manager=User::where('email','maintenance.manager@unifco.local')->firstOrFail();
        $this->actingAs($manager)->get('/workflow/workspace')
            ->assertOk()
            ->assertSee('Maintenance Manager Workspace')
            ->assertDontSee('Maintenance Engineer Workspace')
            ->assertDontSee('Procurement Workspace');
    }
}
