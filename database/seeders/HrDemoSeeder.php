<?php

namespace Database\Seeders;

use App\Models\{AttendanceEntry,Employee,JobPosition,LeaveRequest,Organization,PayrollLine,PayrollRun,Tenant,User};
use Illuminate\Database\Seeder;

class HrDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $org = Organization::where('tenant_id', $tenant->id)->where('code', 'HQ')->firstOrFail();
        $admin = User::where('tenant_id', $tenant->id)->where('email', 'admin@unifco.local')->firstOrFail();

        if (Employee::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $position = static fn (string $code) => JobPosition::where('tenant_id',$tenant->id)->where('code',$code)->value('id');

        $employees = [];
        foreach ([
            ['EMP-0001','Alice Johnson','alice.johnson@unifco.local','DIR-01','2021-01-10'],
            ['EMP-0002','Bob Smith','bob.smith@unifco.local','FIN-01','2021-03-15'],
            ['EMP-0003','Carol Davis','carol.davis@unifco.local','OPS-01','2022-06-01'],
            ['EMP-0004','David Wilson','david.wilson@unifco.local','SAL-01','2023-02-20'],
            ['EMP-0005','Emma Brown','emma.brown@unifco.local','PROD-01','2024-09-05'],
        ] as [$no,$name,$email,$pos,$hire]) {
            $employees[] = Employee::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'job_position_id'=>$position($pos),
                'employee_no'=>$no,'name'=>$name,'email'=>$email,'hire_date'=>$hire,'status'=>'ACTIVE',
            ]);
        }

        $workDates = ['2026-08-10','2026-08-11','2026-08-12'];
        foreach ($workDates as $date) {
            foreach ([$employees[0], $employees[2]] as $emp) {
                AttendanceEntry::firstOrCreate(
                    ['tenant_id'=>$tenant->id,'employee_id'=>$emp->id,'work_date'=>$date],
                    ['worked_hours'=>8,'overtime_hours'=>0,'status'=>'RECORDED','created_by'=>$admin->id],
                );
            }
        }

        LeaveRequest::create([
            'tenant_id'=>$tenant->id,'employee_id'=>$employees[1]->id,'leave_type'=>'ANNUAL',
            'starts_on'=>'2026-09-01','ends_on'=>'2026-09-05','days'=>5,
            'reason'=>'Family vacation','status'=>'APPROVED','requested_by'=>$admin->id,
            'decided_by'=>$admin->id,'decided_at'=>'2026-08-10 14:00:00',
        ]);
        LeaveRequest::create([
            'tenant_id'=>$tenant->id,'employee_id'=>$employees[3]->id,'leave_type'=>'SICK',
            'starts_on'=>'2026-08-20','ends_on'=>'2026-08-21','days'=>2,
            'reason'=>'Medical appointment','status'=>'PENDING','requested_by'=>$admin->id,
        ]);

        $run = PayrollRun::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'payroll_no'=>'PAY-2026-08',
            'period_start'=>'2026-08-01','period_end'=>'2026-08-31','posting_date'=>'2026-08-31',
            'currency'=>'USD','status'=>'POSTED','created_by'=>$admin->id,'approved_by'=>$admin->id,
        ]);
        foreach ([
            [$employees[0], 20000, 5000, 3000],
            [$employees[1], 12000, 3000, 2000],
            [$employees[2], 9000, 2000, 1500],
        ] as [$emp,$basic,$allow,$ded]) {
            PayrollLine::create([
                'payroll_run_id'=>$run->id,'employee_id'=>$emp->id,'basic_pay'=>$basic,
                'allowances'=>$allow,'deductions'=>$ded,'net_pay'=>$basic + $allow - $ded,
            ]);
        }
    }
}