<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class InspectionTemplate extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','template_no','name','checklist','status']; protected function casts():array{return ['checklist'=>'array'];} }
