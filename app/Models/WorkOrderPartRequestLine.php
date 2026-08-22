<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPartRequestLine extends Model
{
    protected $fillable=['work_order_part_request_id','item_id','requested_quantity','approved_quantity','reserved_quantity','issued_quantity','received_quantity'];
    protected function casts(): array { return ['requested_quantity'=>'decimal:4','approved_quantity'=>'decimal:4','reserved_quantity'=>'decimal:4','issued_quantity'=>'decimal:4','received_quantity'=>'decimal:4']; }
    public function request(): BelongsTo { return $this->belongsTo(WorkOrderPartRequest::class,'work_order_part_request_id'); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
}
