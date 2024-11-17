<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class Invoice
 *
 *
 * @package App\Models
 */
class InvoiceDetailTmp extends Eloquent
{
    protected $table = 'invoice_detail_tmp';
    protected $fillable = [
        'invoice_id',
        'drug_id',
        'unit_id',
        'warehouse_id',
        'number',
        'expiry_date',
        'quantity',
        'main_cost',
        'current_cost',
        'usage',
        'cost',
        'vat',
    ];
}
