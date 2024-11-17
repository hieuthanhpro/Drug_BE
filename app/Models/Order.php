<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;
use App\LibExtension\LogEx;

/**
 * Class Order
 *
 *
 * @package App\Models
 */
class Order extends Eloquent
{
    protected $className = "Order";

    protected $table = 'order';
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
        'return_date',
        'created_at',
        'updated_at',
    ];

    // Enum data type to fix some bug
    public $enum_mapping = [
        'status' => ['sent', 'cancel_gdp', 'checking', 'checked', 'done', 'cancel', 'modify', 'ordering', 'confirm', 'delivery', 'cancel_gpp', 'prepared', 'returned', 'cancel_gpp_gdp']
    ];

    public function orderDetails()
    {
        LogEx::methodName($this->className, 'orderDetails');

        return $this->hasMany(OrderDetail::class);
    }
}
