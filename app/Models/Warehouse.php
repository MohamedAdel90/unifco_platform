<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class Warehouse extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','code','name','status']; }
