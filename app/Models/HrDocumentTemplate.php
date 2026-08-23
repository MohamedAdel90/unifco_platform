<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class HrDocumentTemplate extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','code','name','document_type','language','subject','body_template','include_salary','status','created_by'];
    protected function casts(): array { return ['include_salary'=>'boolean']; }
}
