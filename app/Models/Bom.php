<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Bom extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','product_item_id','bom_no','version','status']; public function lines(): HasMany { return $this->hasMany(BomLine::class); } }
