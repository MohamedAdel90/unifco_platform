<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class HrServiceRequest extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','request_no','employee_id','request_type','language','recipient_name','purpose','requested_changes','notes','status','requested_by','decided_by','decided_at','decision_notes','template_id','document_no','verification_token','snapshot','issued_by','issued_at'];
    protected function casts(): array { return ['requested_changes'=>'array','snapshot'=>'array','decided_at'=>'datetime','issued_at'=>'datetime']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function template(): BelongsTo { return $this->belongsTo(HrDocumentTemplate::class,'template_id'); }
}
