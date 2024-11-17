<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 *
 * @package App\Models
 */
class OrderDetailTmp extends Eloquent
{
    protected $table = 'order_detail_tmp';
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

    // Enum data type to fix some bug
    public $enum_mapping = [
        'status' => ['done', 'cancel', 'modify', 'ordering', 'confirm']
    ];

}
