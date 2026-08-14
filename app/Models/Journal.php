<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','journal_no','journal_date','description','status','posted_at'];
    protected function casts(): array { return ['journal_date'=>'date','posted_at'=>'datetime']; }
    public function lines(): HasMany { return $this->hasMany(JournalLine::class); }
}
