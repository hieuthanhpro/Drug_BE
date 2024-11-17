<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class Invoice
 *
 *
 * @package App\Models
 */
class InvoiceDetail extends Eloquent
{
    protected $table = 'invoice_detail';
    protected $fillable = [
        'invoice_id',
        'drug_id',
        'unit_id',
        'warehouse_id',
        'number',
        'expiry_date',
        'exchange',
        'quantity',
        'usage',
        'cost',
        'vat',
        'combo_name',
        'org_cost',
        'mfg_date',
        'warehouse_invoice_id',
        'note',
        'discount_promotion'
    ];
}
