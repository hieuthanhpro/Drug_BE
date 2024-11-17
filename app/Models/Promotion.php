<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class Promotion
 *
 *
 * @package App\Models
 */
class Promotion extends Eloquent
{
    protected $className = "Promotion";

    protected $table = 'promotion';
    protected $fillable = [
        'drug_store_id',
        'name',
        'start_date',
        'end_date',
        'discount_type',
        'status',
        'discount_type_selection',
        'entitled_product_ids',
        'entitled_category_ids',
        'entitled_group_ids',
        'value',
        'value_type',
        'created_by',
        'prerequisite_subtotal',
        'subtotal_selection',
        'customer_selection',
        'prerequisite_customer',
        'drug_store_selection',
        'prerequisite_drug_store',
    ];

    public $enum_mapping = [
        'discount_type' => ['discount', 'gift'],
        'discount_type_selection' => ['order', 'product', 'category', 'group'],
        'value_type' => ['percentage', 'amount'],
        'status' => ['pending', 'running', 'pause', 'ended'],
        'subtotal_selection' => ['minimum', 'each'],
        'customer_selection' => ['all', 'prerequisite'],
        'drug_store_selection' => ['all', 'prerequisite'],
    ];
}
