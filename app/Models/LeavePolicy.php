<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class LeavePolicy extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','code','name','leave_type','annual_entitlement_days','accrual_method','carry_forward_limit_days','requires_approval','paid','status']; protected function casts(): array { return ['annual_entitlement_days'=>'decimal:2','carry_forward_limit_days'=>'decimal:2','requires_approval'=>'boolean','paid'=>'boolean']; } }
