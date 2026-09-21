<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRoleWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_workflow_role_gets_its_own_workspace_and_action_queue(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $expected=[
            'engineer@unifco.local'=>['Maintenance Engineer Workspace','Technical Work Queue'],
            'maintenance.manager@unifco.local'=>['Maintenance Manager Workspace','Operational Review Queue'],
            'procurement@unifco.local'=>['Procurement Workspace','Procurement Action Queue'],
            'tenders@unifco.local'=>['Tenders & Contracts Workspace','Commercial Queue'],
            'finance@unifco.local'=>['Finance Workspace','Financial Review Queue'],
            'projects.manager@unifco.local'=>['Project Manager Workspace','Execution Queue'],
            'ceo@unifco.local'=>['CEO Executive Workspace','Executive Exception Queue'],
        ];

        foreach($expected as $email=>[$title,$queue]){
            $user=User::where('email',$email)->firstOrFail();
            $this->actingAs($user)->get('/workflow/workspace')->assertOk()->assertSee($title)->assertSee($queue);
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

    public function test_role_navigation_exposes_relevant_workspaces(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);

        $engineer=User::where('email','engineer@unifco.local')->firstOrFail();
        $this->actingAs($engineer)->get('/workflow/workspace')
            ->assertOk()->assertSee('My Technical Approvals')->assertSee('Work Orders')->assertSee('Assets & EAM');

        $finance=User::where('email','finance@unifco.local')->firstOrFail();
        $this->actingAs($finance)->get('/workflow/workspace')
            ->assertOk()->assertSee('My Financial Approvals')->assertSee('Finance Core')->assertSee('Journals');

        $ceo=User::where('email','ceo@unifco.local')->firstOrFail();
        $this->actingAs($ceo)->get('/workflow/workspace')
            ->assertOk()->assertSee('Executive Approvals')->assertSee('Executive Reports')->assertSee('Projects Overview');
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
