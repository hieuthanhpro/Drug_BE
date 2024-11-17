<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class OrderTmp
 *
 *
 * @package App\Models
 */
class OrderTmp extends Eloquent
{
    protected $table = 'order_tmp';
    protected $fillable = [
        'drug_store_id',
        'supplier_order_code',
        'number',
        'order_code',
        'supplier_id',
        'amount',
        'vat_amount',
        'pay_amount',
        'created_by',
        'description',
        'status',
        'delivery_date',
        'receipt_date',
        'created_at',
        'updated_at',
    ];
}
