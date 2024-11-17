<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class Invoice
 *
 *
 * @package App\Models
 */
class InvoiceTmp  extends Eloquent
{
    protected $table = 'invoice_tmp';
    protected $fillable = [
        'drug_store_id',
        'invoice_code',
        'warehouse_action_id',
        'refer_id',
        'customer_id',
        'amount',
        'pay_amount',
        'discount',
        'created_by',
        'invoice_type',
        'description',
        'status',
        'payment_status',
        'supplier_invoice_code',
        'number',
        'vat_amount',
        'receipt_date',
        'created_at',
        'updated_at',
        'amount_vat',
    ];

    // Enum data type to fix some bug
    public $enum_mapping = [
        'status' => ['done', 'cancel'],
        'payment_status' => ['unpaid', 'paid_path', 'paid'],
    ];
}
