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
class DrugMaster extends Eloquent
{
    protected $table = 'drug_master_data';
    protected $fillable = [
        'drug_code',
        'barcode',
        'name',
        'short_name',
        'concentration',
        'substances',
        'country',
        'company',
        'package_form',
        'registry_number',
        'description',
        'expiry_date',
        'image',
        'active',
        'updated_at',
        'created_at',
    ];

    // Enum data type to fix some bug
    public $enum_mapping = [
        'active' => ['yes', 'no']
    ];

}
