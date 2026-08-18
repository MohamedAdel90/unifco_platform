<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class ReportSubscription extends Model { use BelongsToTenant; protected $fillable=['tenant_id','user_id','report_code','frequency','delivery_channel','recipient','last_delivered_at','next_delivery_at','is_active']; protected function casts():array{return ['last_delivered_at'=>'datetime','next_delivery_at'=>'datetime','is_active'=>'boolean'];} }
