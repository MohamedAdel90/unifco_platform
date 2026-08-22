<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class Warehouse extends Model
{
    use BelongsToTenant;

    protected $fillable=[
        'tenant_id','organization_id','code','name','location_type','parent_warehouse_id','assigned_employee_id','customer_site_id',
        'vehicle_code','plate_no','city','address','is_mobile','allow_negative_stock','status'
    ];

    protected function casts(): array
    {
        return ['is_mobile'=>'boolean','allow_negative_stock'=>'boolean'];
    }

    public function parent(): BelongsTo { return $this->belongsTo(self::class,'parent_warehouse_id'); }
    public function children(): HasMany { return $this->hasMany(self::class,'parent_warehouse_id'); }
    public function assignedEmployee(): BelongsTo { return $this->belongsTo(Employee::class,'assigned_employee_id'); }
    public function site(): BelongsTo { return $this->belongsTo(CustomerSite::class,'customer_site_id'); }
    public function bins(): HasMany { return $this->hasMany(WarehouseBin::class); }
    public function users() { return $this->belongsToMany(User::class,'warehouse_user_assignments')->withPivot('access_level')->withTimestamps(); }
}
