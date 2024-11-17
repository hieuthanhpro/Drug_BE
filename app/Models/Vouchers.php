<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 2/13/2019
 * Time: 9:10 AM
 */

namespace App\Models;

use App\Models\BaseModel as Eloquent;

class Vouchers extends Eloquent
{
    protected $table = 'vouchers';
    protected $fillable = [
        'type',
        'amount',
        'invoice_id',
        'drug_store_id',
        'invoice_type',
        'user_id',
        'customer_id',
        'supplier_id',
        'recipient_id',
        'code',
        'status',
        'note',
        'created_at',
        'updated_at',
    ];
}
