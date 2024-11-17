<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class Invoice
 *
 *
 * @package App\Models
 */
class InvoiceDose extends Eloquent
{
    protected $table = 'invoice_dose';
    protected $fillable = [
        'drug_store_id',
        'invoice_id',
        'dose_id',
        'quantity',
        'updated_at',
        'created_at',
    ];
}
