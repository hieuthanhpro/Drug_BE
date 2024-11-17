<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;


class Debt extends Eloquent
{
    protected $table = 'debt';
    protected $fillable = [
        'customer_id',
        'supplier_id',
        'amount',
        'type',
        'invoice_id',
        'total_payment',
        'total_amount',
        'created_at',
        'updated_at',
    ];
}
