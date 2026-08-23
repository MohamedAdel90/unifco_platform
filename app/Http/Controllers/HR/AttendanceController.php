<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceEntry,Employee,WorkSchedule};
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $r): View
    {
        $date=$r->date('date')?->toDateString() ?? now()->toDateString();
        $entries=AttendanceEntry::whereDate('work_date',$date)->orderByDesc('late_minutes')->get();
        return view('hr.attendance.index',[
            'date'=>$date,
            'entries'=>$entries,
            'employees'=>Employee::with('position')->where('status','ACTIVE')->orderBy('employee_no')->get(),
            'schedules'=>WorkSchedule::where('status','ACTIVE')->orderBy('code')->get(),
            'present'=>$entries->where('attendance_type','PRESENT')->count(),
            'absent'=>$entries->where('attendance_type','ABSENT')->count(),
            'late'=>$entries->where('late_minutes','>',0)->count(),
            'overtime'=>$entries->sum(fn($e)=>(float)$e->overtime_hours),
        ]);
    }

    public function storeSchedule(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate([
            'code'=>['required','string','max:40'],'name'=>['required','string','max:120'],'starts_at'=>['required','date_format:H:i'],'ends_at'=>['required','date_format:H:i'],
            'break_minutes'=>['required','integer','min:0','max:240'],'grace_minutes'=>['required','integer','min:0','max:120'],'daily_hours'=>['required','numeric','min:1','max:16'],
            'ramadan_mode'=>['nullable','boolean'],'ramadan_daily_hours'=>['required','numeric','min:1','max:12'],
        ]);
        $schedule=WorkSchedule::create([...$d,'organization_id'=>$r->user()->organization_id,'working_days'=>[0,1,2,3,4],'ramadan_mode'=>$r->boolean('ramadan_mode'),'status'=>'ACTIVE']);
        $audit->record('hr.schedule.created',$schedule,[],$schedule->toArray());
        return back()->with('status','Work schedule created.');
    }

    public function assignSchedule(Request $r, Employee $employee, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['work_schedule_id'=>['required','integer']]);
        $schedule=WorkSchedule::findOrFail($d['work_schedule_id']);
        $before=$employee->toArray();
        DB::table('employees')->where('id',$employee->id)->where('tenant_id',$r->user()->tenant_id)->update(['work_schedule_id'=>$schedule->id,'updated_at'=>now()]);
        $audit->record('hr.employee.schedule_assigned',$employee,$before,$employee->fresh()->toArray());
        return back()->with('status','Work schedule assigned.');
    }

    public function store(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate([
            'employee_id'=>['required','integer'],'work_date'=>['required','date'],'attendance_type'=>['required','in:PRESENT,ABSENT,REMOTE,FIELD,DUTY'],
            'check_in_at'=>['nullable','date_format:H:i'],'check_out_at'=>['nullable','date_format:H:i'],'source'=>['required','in:MANUAL,DEVICE,MOBILE,IMPORT'],'notes'=>['nullable','string','max:1000'],
        ]);
        $employee=Employee::findOrFail($d['employee_id']);
        $schedule=$employee->getAttribute('work_schedule_id') ? WorkSchedule::find($employee->getAttribute('work_schedule_id')) : WorkSchedule::where('status','ACTIVE')->orderBy('id')->first();
        $metrics=$this->metrics($d,$schedule);
        $entry=AttendanceEntry::updateOrCreate(
            ['employee_id'=>$employee->id,'work_date'=>$d['work_date']],
            [...$d,...$metrics,'work_schedule_id'=>$schedule?->id,'created_by'=>Auth::id(),'status'=>'RECORDED']
        );
        $audit->record('hr.attendance.phase2_recorded',$entry,[],$entry->toArray());
        return back()->with('status','Attendance day calculated and saved.');
    }

    private function metrics(array $d, ?WorkSchedule $schedule): array
    {
        if ($d['attendance_type']==='ABSENT' || empty($d['check_in_at']) || empty($d['check_out_at'])) {
            return ['worked_hours'=>0,'overtime_hours'=>0,'late_minutes'=>0,'early_leave_minutes'=>0];
        }
        $in=Carbon::createFromFormat('H:i',$d['check_in_at']);
        $out=Carbon::createFromFormat('H:i',$d['check_out_at']);
        if ($out->lessThanOrEqualTo($in)) $out->addDay();
        $break=$schedule?->break_minutes ?? 0;
        $worked=max(0,round(($in->diffInMinutes($out)-$break)/60,2));
        $target=$schedule ? (float)($schedule->ramadan_mode ? $schedule->ramadan_daily_hours : $schedule->daily_hours) : 8.0;
        $late=0; $early=0;
        if ($schedule) {
            $start=Carbon::createFromFormat('H:i:s',$schedule->starts_at);
            $end=Carbon::createFromFormat('H:i:s',$schedule->ends_at);
            $late=max(0,$start->diffInMinutes($in,false)-$schedule->grace_minutes);
            $early=max(0,$out->diffInMinutes($end,false));
        }
        return ['worked_hours'=>$worked,'overtime_hours'=>max(0,round($worked-$target,2)),'late_minutes'=>$late,'early_leave_minutes'=>$early];
    }
}
