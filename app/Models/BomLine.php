<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BomLine extends Model { protected $fillable=['bom_id','line_no','component_item_id','quantity_per','standard_unit_cost']; protected function casts(): array { return ['quantity_per'=>'decimal:4','standard_unit_cost'=>'decimal:4']; } }
