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
class DoseGroup extends Eloquent
{
    protected $table = 'dose_group';
    protected $fillable = [
        'drug_store_id',
        'name',
        'active',
        'image'
    ];

    // Enum data type to fix some bug
    public $enum_mapping = [
        'active' => ['yes', 'no']
    ];
}
