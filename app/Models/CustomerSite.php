<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSite extends Model
{
    protected $fillable=['customer_id','site_code','name','city','address','latitude','longitude','contact_name','contact_mobile','status'];
    protected $casts=['latitude'=>'decimal:7','longitude'=>'decimal:7'];
    public function customer(){ return $this->belongsTo(Customer::class); }
}
