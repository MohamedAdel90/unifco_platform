<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class JobPosition extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','code','title','department','status']; }
