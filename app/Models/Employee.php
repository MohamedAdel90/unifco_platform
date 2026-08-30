<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class Employee extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','job_position_id','manager_employee_id','work_schedule_id','employee_no','name','first_name','middle_name','last_name','email','official_email','mobile','mobile_country_code','hire_date','status',
        'nationality','gender','date_of_birth','marital_status','national_id','iqama_no','iqama_expiry','passport_no','passport_expiry','gosi_no','gosi_status','gosi_registered_on',
        'bank_name','iban','emergency_contact_name','emergency_contact_mobile','emergency_mobile_country_code','address_line','city','country','saudi_city','saudi_region','birth_contact_country_code','birth_contact_mobile','employment_type','contract_type',
        'probation_end_date','contract_end_date','basic_salary','housing_allowance','transport_allowance','other_allowances','work_location','project_name','notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            if (!app()->bound('request')) return;
            $request=request();
            foreach (['saudi_city','saudi_region','birth_contact_country_code','birth_contact_mobile','project_name'] as $field) {
                if ($request->exists($field)) {
                    $value=trim((string)$request->input($field));
                    $employee->{$field}=$value !== '' ? mb_substr($value,0,in_array($field,['saudi_city','saudi_region'])?100:($field==='project_name'?160:30)) : null;
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'hire_date'=>'date','date_of_birth'=>'date','iqama_expiry'=>'date','passport_expiry'=>'date','gosi_registered_on'=>'date','probation_end_date'=>'date','contract_end_date'=>'date',
            'basic_salary'=>'decimal:2','housing_allowance'=>'decimal:2','transport_allowance'=>'decimal:2','other_allowances'=>'decimal:2',
        ];
    }

    public function position(): BelongsTo { return $this->belongsTo(JobPosition::class,'job_position_id'); }
    public function manager(): BelongsTo { return $this->belongsTo(self::class,'manager_employee_id'); }
    public function workSchedule(): BelongsTo { return $this->belongsTo(WorkSchedule::class,'work_schedule_id'); }
    public function contracts(): HasMany { return $this->hasMany(EmploymentContract::class); }
    public function documents(): HasMany { return $this->hasMany(EmployeeDocument::class); }
    public function attendanceEntries(): HasMany { return $this->hasMany(AttendanceEntry::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function payrollLines(): HasMany { return $this->hasMany(PayrollLine::class); }
    public function businessTrips(): HasMany { return $this->hasMany(BusinessTrip::class); }

    public function grossMonthlyCompensation(): float
    {
        return (float)$this->basic_salary+(float)$this->housing_allowance+(float)$this->transport_allowance+(float)$this->other_allowances;
    }
}
