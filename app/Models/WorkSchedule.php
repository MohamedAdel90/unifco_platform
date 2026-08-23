<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class WorkSchedule extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','code','name','starts_at','ends_at','break_minutes','grace_minutes','daily_hours','working_days','ramadan_mode','ramadan_daily_hours','status']; protected function casts(): array { return ['working_days'=>'array','ramadan_mode'=>'boolean','daily_hours'=>'decimal:2','ramadan_daily_hours'=>'decimal:2']; } }
