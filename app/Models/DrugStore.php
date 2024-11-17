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
class DrugStore extends Eloquent
{
    protected $table = 'drugstores';
	protected $fillable = [
		'name',
		'address',
		'phone',
        'token',
		'status',
        'username',
        'password',
        'pharmacist',
        'base_code',
        'reg_number',
        'business_license',
        'warning_date',
        'start_time',
        'end_time',
        'vnpay_code'
	];
}
