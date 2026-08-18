<?php

namespace Tests\Feature;

use App\Models\{Asset,Employee,InspectionTemplate,Organization,Tenant,User,WorkOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldServiceBaselineGapClosureTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'FIELD','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'FIELD-HQ','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Dispatcher','email'=>'dispatcher@example.test','password'=>'StrongPassword123','role'=>'ADMIN','status'=>'ACTIVE']);
        $employee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'TECH-1','name'=>'Technician One','email'=>'tech@example.test','hire_date'=>now(),'status'=>'ACTIVE']);
        $technician=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_id'=>$employee->id,'name'=>'Technician One','email'=>'tech.user@example.test','password'=>'StrongPassword123','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_code'=>'GEN-001','name'=>'Generator','status'=>'REGISTERED']);
        $workOrder=WorkOrder::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'work_order_no'=>'WO-FIELD-1','asset_id'=>$asset->id,'maintenance_type'=>'CORRECTIVE','priority'=>'EMERGENCY','status'=>'OPEN']);
        return compact('tenant','org','manager','employee','technician','asset','workOrder');
    }

    public function test_dispatch_technician_inspection_notifications_and_ai_workflow(): void
    {
        $c=$this->context();
        $this->actingAs($c['manager'])->get('/field/operations')->assertOk()->assertSee('Scheduling & Dispatch');
        $this->actingAs($c['manager'])->post('/field/assignments',[
            'work_order_id'=>$c['workOrder']->id,'employee_id'=>$c['employee']->id,'scheduled_start'=>now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertRedirect();
        $this->assertDatabaseHas('work_order_assignments',['work_order_id'=>$c['workOrder']->id,'employee_id'=>$c['employee']->id,'dispatch_status'=>'DISPATCHED']);
        $this->assertDatabaseHas('platform_notifications',['user_id'=>$c['technician']->id,'type'=>'WORK_ORDER_DISPATCH']);

        $assignment=\App\Models\WorkOrderAssignment::firstOrFail();
        $this->actingAs($c['technician'])->get('/field/technician')->assertOk()->assertSee('Offline mode')->assertSee('WO-FIELD-1');
        $this->actingAs($c['technician'])->post('/field/technician/assignments/'.$assignment->id.'/status',['status'=>'IN_PROGRESS'])->assertRedirect();
        $this->assertDatabaseHas('work_orders',['id'=>$c['workOrder']->id,'status'=>'IN_PROGRESS']);

        $template=InspectionTemplate::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'template_no'=>'GEN-CHECK','name'=>'Generator Check','checklist'=>['Oil level','Battery'],'status'=>'ACTIVE']);
        $this->actingAs($c['technician'])->post('/field/inspections',[
            'work_order_id'=>$c['workOrder']->id,'inspection_template_id'=>$template->id,'responses'=>'Oil OK; Battery OK','findings'=>'No defect','recommendations'=>'Continue PM','complete'=>1,
        ])->assertRedirect();
        $this->assertDatabaseHas('inspections',['work_order_id'=>$c['workOrder']->id,'employee_id'=>$c['employee']->id,'status'=>'COMPLETED']);

        $this->actingAs($c['manager'])->post('/field/assistant',['query'=>'Summarize open work orders and emergency maintenance'])->assertRedirect();
        $this->assertDatabaseHas('ai_interactions',['user_id'=>$c['manager']->id,'result'=>'ANSWERED']);
        $this->actingAs($c['manager'])->get('/field/assistant')->assertOk()->assertSee('UNIFCO AI Assistant')->assertSee('open work orders');
    }
}
