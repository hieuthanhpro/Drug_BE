<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class PaymentLogs
 *
 *
 * @package App\Models
 */
class PaymentLogs extends Eloquent
{
    private $errors;
    protected $className = "PaymentLogs";
    protected $table = 'payment_logs';
    protected $fillable = [
        'drug_store_id',
        'amount',
        'cash_date',
        'payment_method',
        'invoice_id',
        'invoice_type',
        'reason',
        'status',
        'body',
    ];

    public $enum_mapping = [
        'status' => ['success', 'fail'],
        'invoice_type' => ['IV1', 'IV3'],
        'payment_method' => ['cash', 'banking', 'card', 'momo', 'vnpay', 'other']
    ];
}
