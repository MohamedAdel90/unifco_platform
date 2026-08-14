<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Routing extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','product_item_id','routing_no','status']; public function operations(): HasMany { return $this->hasMany(RoutingOperation::class); } }
