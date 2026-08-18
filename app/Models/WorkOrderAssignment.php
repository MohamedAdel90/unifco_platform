<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class WorkOrderAssignment extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','work_order_id','employee_id','scheduled_start','scheduled_end','dispatch_status','dispatched_at','accepted_at','arrived_at','dispatcher_notes']; protected function casts():array{return ['scheduled_start'=>'datetime','scheduled_end'=>'datetime','dispatched_at'=>'datetime','accepted_at'=>'datetime','arrived_at'=>'datetime'];} }
