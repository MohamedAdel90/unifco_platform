<?php

namespace Tests\Feature;

use App\Models\{CrmOpportunity,Customer,Employee,Project,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Wave11CrmProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_opportunity_quotation_and_won_project_flow(): void
    {
        $this->seed(); $admin=User::firstOrFail(); $this->actingAs($admin);
        $customer=Customer::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'customer_code'=>'C-100','name'=>'Customer 100','status'=>'ACTIVE']);
        $this->post('/crm/operations/leads',['lead_no'=>'L-100','name'=>'Lead 100','company'=>'Prospect Co'])->assertRedirect();
        $this->post('/crm/operations/opportunities',['opportunity_no'=>'O-100','name'=>'ERP Rollout','stage'=>'PROPOSAL','expected_value'=>50000,'probability'=>60])->assertRedirect();
        $opp=CrmOpportunity::firstOrFail();
        $this->post('/crm/operations/quotations',['opportunity_id'=>$opp->id,'quotation_no'=>'Q-100','quotation_date'=>'2026-08-15','currency'=>'USD','amount'=>48000])->assertRedirect();
        $this->post("/crm/operations/opportunities/{$opp->id}/win",['customer_id'=>$customer->id,'project_no'=>'P-100','project_name'=>'ERP Rollout Project','budget'=>48000])->assertRedirect();
        $this->assertDatabaseHas('crm_opportunities',['id'=>$opp->id,'status'=>'WON','stage'=>'CLOSED_WON']);
        $this->assertDatabaseHas('projects',['project_no'=>'P-100','customer_id'=>$customer->id,'opportunity_id'=>$opp->id]);
        $this->assertDatabaseHas('crm_quotations',['quotation_no'=>'Q-100','status'=>'ISSUED']);
    }

    public function test_wbs_resource_and_timesheet_update_project_cost(): void
    {
        $this->seed(); $admin=User::firstOrFail(); $this->actingAs($admin);
        $project=Project::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'project_no'=>'P-200','name'=>'Delivery','budget'=>10000,'actual_cost'=>0,'status'=>'ACTIVE']);
        $employee=Employee::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'employee_no'=>'E-300','name'=>'Consultant','status'=>'ACTIVE']);
        $this->post('/projects/operations/tasks',['project_id'=>$project->id,'wbs_code'=>'1.1','name'=>'Analysis','budget'=>2500])->assertRedirect();
        $taskId=(int)\DB::table('project_tasks')->value('id');
        $this->post("/projects/operations/{$project->id}/resources",['employee_id'=>$employee->id,'role'=>'Consultant','planned_hours'=>80])->assertRedirect();
        $this->post("/projects/operations/{$project->id}/timesheets",['project_task_id'=>$taskId,'employee_id'=>$employee->id,'work_date'=>'2026-08-15','hours'=>8,'hourly_cost'=>50])->assertRedirect();
        $this->assertDatabaseHas('project_resource_assignments',['project_id'=>$project->id,'employee_id'=>$employee->id,'planned_hours'=>80]);
        $this->assertDatabaseHas('project_timesheets',['project_id'=>$project->id,'employee_id'=>$employee->id,'hours'=>8]);
        $this->assertEquals(400.0,(float)$project->fresh()->actual_cost);
    }
}
