<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class Bank
 *
 *
 * @package App\Models
 */
class Bank extends Eloquent
{
    protected $className = "Bank";
    protected $table = 'bank';
    protected $fillable = [
        'name',
    ];
}
