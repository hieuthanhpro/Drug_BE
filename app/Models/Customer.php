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
class Customer extends Eloquent
{
    protected $table = 'customer';
    protected $fillable = [
        'drug_store_id',
        'name',
        'number_phone',
        'email',
        'name',
        'gender',
        'birthday',
        'address',
        'website',
        'status',
        'country',
        'tax_number',
        'created_at',
        'updated_at',
    ];

    public $enum_mapping = [
        'gender' => ['company', 'male', 'fmale']
    ];
}
