<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class Asset extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','customer_id','customer_site_id','parent_asset_id','asset_category_template_id','asset_code','name','location_code','serial_no',
        'asset_category','asset_subcategory','manufacturer','model_no','criticality','lifecycle_status','operational_status','manufacture_date','installation_date',
        'warranty_expiry','acquisition_cost','replacement_value','salvage_value','useful_life_months','expected_replacement_date','accumulated_depreciation','net_book_value',
        'meter_value','commission_date','supplier_name','installer_name','qr_token','verification_status','disposed_at','status'
    ];

    protected function casts(): array
    {
        return [
            'acquisition_cost'=>'decimal:2','replacement_value'=>'decimal:2','salvage_value'=>'decimal:2','accumulated_depreciation'=>'decimal:2','net_book_value'=>'decimal:2',
            'meter_value'=>'decimal:4','manufacture_date'=>'date','installation_date'=>'date','commission_date'=>'date','warranty_expiry'=>'date','expected_replacement_date'=>'date','disposed_at'=>'datetime'
        ];
    }

    public function parent(): BelongsTo { return $this->belongsTo(self::class,'parent_asset_id'); }
    public function children(): HasMany { return $this->hasMany(self::class,'parent_asset_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function site(): BelongsTo { return $this->belongsTo(CustomerSite::class,'customer_site_id'); }
}
