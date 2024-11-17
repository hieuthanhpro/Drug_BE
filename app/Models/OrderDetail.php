<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 *
 * @package App\Models
 */
class OrderDetail extends Eloquent
{
    protected $table = 'order_detail';
    protected $fillable = [
        'order_id',
        'drug_id',
        'concentration',
        'package_form',
        'manufacturer',
        'unit_id',
        'quantity',
        'cost',
    ];
}
