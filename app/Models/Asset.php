<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class Asset extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','customer_id','customer_site_id','asset_location_id','parent_asset_id','asset_category_template_id','asset_code','customer_asset_code','name','location_code','serial_no','manufacturer_asset_number',
        'asset_category','asset_subcategory','asset_type','manufacturer','model_no','criticality','ownership_type','lifecycle_status','operational_status','maintenance_strategy','pm_template','pm_frequency','last_pm','next_pm','last_inspection','next_inspection',
        'building','floor','zone','room','room_code','physical_location','latitude','longitude','manufacture_date','installation_date','warranty_start','warranty_expiry','warranty_provider','contract_reference','sla_reference','coverage_type',
        'technical_specifications','acquisition_cost','replacement_value','salvage_value','useful_life_months','expected_replacement_date','accumulated_depreciation','net_book_value',
        'meter_value','operating_hours','meter_unit','design_capacity','current_load','failure_impact','commission_date','commissioning_status','commissioning_requested_by','commissioning_requested_at','commissioning_approved_by','commissioning_approved_at','commissioning_notes',
        'supplier_name','installer_name','qr_token','verification_status','data_completeness_score','verified_by','verified_at','verification_notes',
        'health_score','health_band','remaining_life_months','replacement_recommendation','replacement_reason','last_health_calculated_at','disposed_at','status'
    ];

    protected function casts(): array
    {
        return [
            'acquisition_cost'=>'decimal:2','replacement_value'=>'decimal:2','salvage_value'=>'decimal:2','accumulated_depreciation'=>'decimal:2','net_book_value'=>'decimal:2',
            'meter_value'=>'decimal:4','operating_hours'=>'decimal:2','design_capacity'=>'decimal:3','current_load'=>'decimal:3','latitude'=>'decimal:7','longitude'=>'decimal:7','manufacture_date'=>'date','installation_date'=>'date','commission_date'=>'date',
            'warranty_start'=>'date','warranty_expiry'=>'date','expected_replacement_date'=>'date','last_pm'=>'date','next_pm'=>'date','last_inspection'=>'date','next_inspection'=>'date','technical_specifications'=>'array','verified_at'=>'datetime',
            'commissioning_requested_at'=>'datetime','commissioning_approved_at'=>'datetime','last_health_calculated_at'=>'datetime','disposed_at'=>'datetime'
        ];
    }

    public function parent(): BelongsTo { return $this->belongsTo(self::class,'parent_asset_id'); }
    public function children(): HasMany { return $this->hasMany(self::class,'parent_asset_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function site(): BelongsTo { return $this->belongsTo(CustomerSite::class,'customer_site_id'); }
    public function location(): BelongsTo { return $this->belongsTo(AssetLocation::class,'asset_location_id'); }
    public function template(): BelongsTo { return $this->belongsTo(AssetCategoryTemplate::class,'asset_category_template_id'); }
    public function documents(): HasMany { return $this->hasMany(AssetDocument::class); }
    public function lifecycleEvents(): HasMany { return $this->hasMany(AssetLifecycleEvent::class)->orderByDesc('performed_at'); }
    public function commissioningRecords(): HasMany { return $this->hasMany(AssetCommissioningRecord::class)->orderByDesc('id'); }
}
