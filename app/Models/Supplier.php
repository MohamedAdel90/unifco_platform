<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','supplier_code','name','email','tax_no','status']; }
