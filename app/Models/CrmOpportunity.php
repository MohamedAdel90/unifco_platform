<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class CrmOpportunity extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','lead_id','customer_id','opportunity_no','name','stage','expected_value','probability','expected_close','status','created_by']; protected function casts(): array { return ['expected_value'=>'decimal:2','expected_close'=>'date']; } }
