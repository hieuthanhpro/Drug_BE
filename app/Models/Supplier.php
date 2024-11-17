<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:10 AM
 */

namespace App\Models;

use App\Models\BaseModel as Eloquent;

 /*
 * @package App\Models
 */
class Supplier extends Eloquent
{
    protected $table = 'supplier';
    protected $fillable = [
        'drug_store_id',
        'name',
        'number_phone',
        'email',
        'tax_number',
        'website',
        'address',
        'status',
    ];
}
