<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 *
 * @package App\Models
 */
class OrderDetailAdmin extends Eloquent
{
    protected $table = 'order_detail_admin';
    protected $fillable = [
        'order_id',
        'drug_id',
        'concentration',
        'package_form',
        'manufacturer',
        'unit_id',
        'quantity',
        'cost',
        'number',
        'expiry_date',
        'vat'
    ];
}
