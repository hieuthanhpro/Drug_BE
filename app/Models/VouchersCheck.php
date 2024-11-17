<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 4/7/2019
 * Time: 12:05 AM
 */


namespace App\Models;

use App\Models\BaseModel as Eloquent;

class VouchersCheck extends Eloquent
{
    protected $table = 'vouchers_check';
    protected $fillable = [
        'drug_store_id',
        'code',
        'note',
        'status',
        'updated_at',
        'created_at',
        'created_by',
        'check_status'
    ];
}
