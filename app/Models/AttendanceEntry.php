<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class AttendanceEntry extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','work_date','worked_hours','overtime_hours','status','created_by']; protected function casts(): array { return ['work_date'=>'date','worked_hours'=>'decimal:2','overtime_hours'=>'decimal:2']; } }
