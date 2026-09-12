<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','customer_code','name','name_ar','commercial_registration','vat_number','industry','customer_type',
        'email','website','contact_name','contact_title','contract_manager_name','contract_manager_title','project_name','logo_path',
        'phone','alternate_phone','city','country','address','notes','status','onboarding_status','onboarding_review_status','onboarding_reviewed_by','onboarding_reviewed_at','onboarding_review_notes',
        'acquisition_source','origin_lead_id','first_touch_at','converted_by','converted_at'
    ];

    protected function casts(): array { return [
        'first_touch_at'=>'datetime','converted_at'=>'datetime','onboarding_reviewed_at'=>'datetime'
    ]; }

    public function contacts(){ return $this->hasMany(CustomerContact::class); }
    public function sites(){ return $this->hasMany(CustomerSite::class); }
}
