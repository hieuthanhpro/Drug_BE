<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class CashType
 *
 *
 * @package App\Models
 */
class CashType extends Eloquent
{
    protected $className = "CashType";
    protected $table = 'cash_type';
    protected $fillable = [
        'drug_store_id',
        'name',
        'type',
        'invoice_type',
        'user_type',
        'is_hidden'
    ];

    public $enum_mapping = [
        'type' => ['receipt', 'pay_slip']
    ];
}
