<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;
use App\LibExtension\LogEx;

/**
 * Class Invoice
 *
 *
 * @package App\Models
 */
class Invoice extends Eloquent
{
    protected $className = "Invoice";

    protected $table = 'invoice';
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
        'shipping_status',
        'supplier_invoice_code',
        'vat_amount',
        'receipt_date',
        'created_at',
        'updated_at',
        'synced_at',
        'image',
        'method',
        'payment_method',
        'sale_id',
        'source',
        'is_order',
        'is_import'
    ];

    // Enum data type to fix some bug
    public $enum_mapping = [
        'status' => ['processing', 'done', 'cancel', 'temp'],
        'payment_status' => ['unpaid', 'partial_paid', 'paid', 'fail'],
        'shipping_status' => ['processing', 'delivery', 'done'],
        'method' => ['direct', 'online'],
        'payment_method' => ['cash', 'banking', 'card', 'momo', 'vnpay', 'other']
    ];

    public function invoiceDetails()
    {
        LogEx::methodName($this->className, 'invoiceDetails');

        return $this->hasMany(InvoiceDetail::class);
    }
}
