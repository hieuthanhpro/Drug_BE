<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 26 Jun 2018 17:34:11 +0700.
 */

namespace App\Models;

use App\Models\BaseModel as Eloquent;
use App\LibExtension\LogEx;

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
 * @property string $otp
 * @property \Carbon\Carbon $last_sent_otp
 * @property \Carbon\Carbon $first_sent_otp
 * @property int $count_otp
 *
 * @package App\Models
 */
class User extends Eloquent
{
    protected $className = "User";

    protected $casts = [
		'drug_store_id' => 'int',
	];

	protected $hidden = [
		'password',
	];

	protected $fillable = [
		'user_type',
		'username',
        'name',
		'password',
		'role_id',
		'full_name',
		'email',
		'number_phone',
		'active',
        'drug_store_id',
        'avatar',
        'user_role',
        'permission',
        'otp',
        'last_sent_otp',
        'first_sent_otp',
        'count_otp'
    ];

     // Enum data type to fix some bug
     public $enum_mapping = [
        'active' => ['yes', 'no'],
        'user_type' => ['owner', 'emloyee']

    ];

    public function drung_store(){
        LogEx::methodName($this->className, 'drung_store');

        return $this->hasOne('App\Models\DrugStore', 'id','drug_store_id');

    }
}
