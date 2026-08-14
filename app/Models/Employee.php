<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','employee_no','name','email','hire_date','status'];
    protected function casts(): array { return ['hire_date' => 'date']; }
}
