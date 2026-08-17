<?php

namespace Database\Seeders;

use App\Models\{Customer,Employee,Organization,Project,ProjectTask,Tenant,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $org = Organization::where('tenant_id', $tenant->id)->where('code', 'HQ')->firstOrFail();
        $admin = User::where('tenant_id', $tenant->id)->where('email', 'admin@unifco.local')->firstOrFail();

        if (Project::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $customer = static fn (string $code) => Customer::where('tenant_id',$tenant->id)->where('customer_code',$code)->value('id');

        $project = Project::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'project_no'=>'PRJ-0001',
            'name'=>'Widget delivery program','customer_id'=>$customer('CUS-0001'),
            'planned_start'=>'2026-08-01','planned_finish'=>'2026-12-31','budget'=>48000,
            'actual_cost'=>15000,'status'=>'ACTIVE',
        ]);
        Project::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'project_no'=>'PRJ-0002',
            'name'=>'Maintenance automation','customer_id'=>$customer('CUS-0002'),
            'planned_start'=>'2026-09-01','planned_finish'=>'2027-02-28','budget'=>25000,
            'actual_cost'=>0,'status'=>'DRAFT',
        ]);

        foreach ([
            ['1.1','Discovery phase','IN_PROGRESS',5000],
            ['1.2','Requirements','PLANNED',4000],
            ['2.1','Delivery','PLANNED',18000],
        ] as [$wbs,$name,$status,$budget]) {
            ProjectTask::create([
                'tenant_id'=>$tenant->id,'project_id'=>$project->id,'wbs_code'=>$wbs,'name'=>$name,
                'planned_start'=>'2026-08-05','planned_finish'=>'2026-11-30','budget'=>$budget,'status'=>$status,
            ]);
        }

        $employee = static fn (string $no) => Employee::where('tenant_id',$tenant->id)->where('employee_no',$no)->value('id');
        $task = ProjectTask::where('project_id',$project->id)->where('wbs_code','1.1')->value('id');

        DB::table('project_resource_assignments')->insert([
            ['tenant_id'=>$tenant->id,'project_id'=>$project->id,'employee_id'=>$employee('EMP-0003'),'role'=>'Project Lead','planned_hours'=>120,'created_at'=>now(),'updated_at'=>now()],
            ['tenant_id'=>$tenant->id,'project_id'=>$project->id,'employee_id'=>$employee('EMP-0004'),'role'=>'Consultant','planned_hours'=>80,'created_at'=>now(),'updated_at'=>now()],
        ]);

        DB::table('project_timesheets')->insert([
            'tenant_id'=>$tenant->id,'project_id'=>$project->id,'project_task_id'=>$task,
            'employee_id'=>$employee('EMP-0003'),'work_date'=>'2026-08-12','hours'=>8,
            'hourly_cost'=>50,'status'=>'POSTED','created_by'=>$admin->id,'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
}