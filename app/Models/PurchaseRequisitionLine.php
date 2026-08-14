<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseRequisitionLine extends Model { protected $fillable=['purchase_requisition_id','line_no','item_id','quantity','estimated_unit_price']; }
