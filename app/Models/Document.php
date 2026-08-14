<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use BelongsToTenant;

    protected $fillable=['tenant_id','organization_id','uploaded_by','document_no','title','original_name','storage_path','mime_type','size_bytes','entity_type','entity_id','status'];
}
