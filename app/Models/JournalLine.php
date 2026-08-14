<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    public $timestamps = false;
    protected $fillable = ['journal_id','line_no','account_code','debit','credit','description'];
    protected function casts(): array { return ['debit'=>'decimal:2','credit'=>'decimal:2']; }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
}
