<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View { return view('hr.employees.index',['employees'=>Employee::orderBy('employee_no')->paginate(25)]); }
    public function create(): View { return view('hr.employees.form',['employee'=>new Employee()]); }
    public function edit(Employee $employee): View { return view('hr.employees.form',compact('employee')); }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$this->validated($request);
        $employee=Employee::create([...$data,'organization_id'=>Auth::user()->organization_id,'status'=>'ACTIVE']);
        $audit->record('hr.employee.created',$employee,[],$employee->toArray());
        return redirect()->route('hr.employees.index')->with('status','Employee created.');
    }

    public function update(Request $request, Employee $employee, AuditService $audit): RedirectResponse
    {
        $before=$employee->toArray(); $employee->update($this->validated($request,$employee));
        $audit->record('hr.employee.updated',$employee,$before,$employee->fresh()->toArray());
        return redirect()->route('hr.employees.index')->with('status','Employee updated.');
    }

    public function deactivate(Employee $employee, AuditService $audit): RedirectResponse
    {
        $before=$employee->toArray(); $employee->update(['status'=>'INACTIVE']);
        $audit->record('hr.employee.deactivated',$employee,$before,$employee->fresh()->toArray());
        return back()->with('status','Employee deactivated.');
    }

    private function validated(Request $request, ?Employee $employee=null): array
    {
        return $request->validate([
            'employee_no'=>['required','string','max:50',Rule::unique('employees')->where(fn($q)=>$q->where('tenant_id',Auth::user()->tenant_id))->ignore($employee?->id)],
            'name'=>['required','string','max:160'],'email'=>['nullable','email','max:255'],'hire_date'=>['nullable','date'],
        ]);
    }
}
