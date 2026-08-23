<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id','employee_id','document_type','document_no','issued_on','expires_on','file_path','status'];
    protected function casts(): array { return ['issued_on'=>'date','expires_on'=>'date']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
