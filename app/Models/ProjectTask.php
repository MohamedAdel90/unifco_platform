<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class ProjectTask extends Model { use BelongsToTenant; protected $fillable=['tenant_id','project_id','wbs_code','name','planned_start','planned_finish','budget','status']; protected function casts(): array { return ['planned_start'=>'date','planned_finish'=>'date','budget'=>'decimal:2']; } }
