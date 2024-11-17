<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class User
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class DoseDetail extends Eloquent
{
    protected $table = 'dose_detail';
    protected $fillable = [
        'dose_id',
        'drug_id',
        'unit_id',
        'quantity',
        'usage',
        'updated_at',
        'created_at'
    ];
}
