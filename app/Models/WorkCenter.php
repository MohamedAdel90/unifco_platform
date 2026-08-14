<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class WorkCenter extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','code','name','hourly_rate','status']; protected function casts(): array { return ['hourly_rate'=>'decimal:2']; } }
