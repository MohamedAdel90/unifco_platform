<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollRun extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','payroll_no','period_start','period_end','posting_date','currency','status','created_by','approved_by','journal_id']; protected function casts(): array { return ['period_start'=>'date','period_end'=>'date','posting_date'=>'date']; } public function lines(): HasMany { return $this->hasMany(PayrollLine::class); } }
