<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class AiInteraction extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','user_id','query','response','citations','recommended_actions','result']; protected function casts():array{return ['citations'=>'array','recommended_actions'=>'array'];} }
