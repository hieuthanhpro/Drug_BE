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
class DoseDrug extends Eloquent
{
    protected $table = 'dose_drug';
    protected $fillable = [
        'drug_store_id',
        'group_id',
        'category_id',
        'name',
        'usage',
        'dose_code',
        'current_cost',
        'active',
        'updated_at',
        'created_at'
    ];

    // Enum data type to fix some bug
    public $enum_mapping = [
        'active' => ['yes', 'no']
    ];
}
