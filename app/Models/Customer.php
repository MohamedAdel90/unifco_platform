<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','customer_code','name','commercial_registration','vat_number','industry',
        'email','contact_name','phone','city','country','address','status','onboarding_status'
    ];

    public function contacts(){ return $this->hasMany(CustomerContact::class); }
    public function sites(){ return $this->hasMany(CustomerSite::class); }
}
