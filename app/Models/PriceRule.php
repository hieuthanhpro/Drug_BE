<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class PriceRule
 *
 *
 * @package App\Models
 */
class PriceRule extends Eloquent
{
    protected $className = "PriceRule";

    protected $table = 'price_rule';
    protected $fillable = [
        'type',
        'prerequisite_selection',
        'value',
        'target_selection',
        'entitled_product_ids',
        'entitled_category_ids',
        'entitled_group_ids',
        'promotion_id'
    ];

    public $enum_mapping = [
        'type' => ['item_quantity', 'item_price'],
        'prerequisite_selection' => ['minimum', 'each'],
        'target_selection' => ['product', 'group', 'category'],
    ];
}
