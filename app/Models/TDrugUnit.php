<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class TDrugUnit
 *
 *
 * @package App\Models
 */
class TDrugUnit extends Eloquent
{
    protected $className = "TDrugUnit";

    protected $table = 't_drug_unit';
    protected $fillable = [
        'drug_store_id',
        'drug_id',
        'unit_id',
        'exchange',
        'is_basis',
        'in_price',
        'in_price_org',
        'out_price',
    ];
}
