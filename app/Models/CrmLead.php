<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class CrmLead extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','lead_no','name','company','email','status','created_by']; }
