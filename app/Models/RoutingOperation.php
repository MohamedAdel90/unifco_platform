<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RoutingOperation extends Model { protected $fillable=['routing_id','sequence','work_center_id','operation_name','standard_hours']; protected function casts(): array { return ['standard_hours'=>'decimal:4']; } }
