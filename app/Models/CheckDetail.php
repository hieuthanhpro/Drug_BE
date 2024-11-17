<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

class CheckDetail extends Eloquent
{
    protected $table = 'check_detail';
    protected $fillable = [
        'vouchers_check_id',
        'drug_id',
        'number',
        'diff_amount',
        'current_amount',
        'note',
        'unit_id',
        'drug_code',
        'expiry_date',
        'amount',
        'main_cost',
        'diff_value',
        'updated_at',
        'created_at'
    ];
}
