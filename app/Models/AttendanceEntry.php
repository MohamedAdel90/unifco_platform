<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class AttendanceEntry extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','work_schedule_id','work_date','check_in_at','check_out_at','worked_hours','overtime_hours','late_minutes','early_leave_minutes','attendance_type','source','notes','status','created_by']; protected function casts(): array { return ['work_date'=>'date','worked_hours'=>'decimal:2','overtime_hours'=>'decimal:2']; } }
