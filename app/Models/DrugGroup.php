<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class User
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $fullname
 * @property string $email
 * @property string $phone
 * @property int $company_id
 * @property bool $role_type
 * @property bool $status
 * @property string $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class DrugGroup extends Eloquent
{
    protected $table = 'drug_group';
    protected $fillable = [
        'drug_store_id',
        'name',
        'is_drug',
        'active',
        'image'
    ];

    // Enum data type to fix some bug
    public $enum_mapping = [
        'active' => ['yes', 'no']
    ];
}
