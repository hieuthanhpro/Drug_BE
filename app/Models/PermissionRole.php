<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 4/15/2019
 * Time: 1:37 PM
 */



namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class unit
 *
 * @property bool $status
 * @property string $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class PermissionRole extends Eloquent
{
    protected $table = 'permission_role';
    protected $fillable = [
        'role_id',
        'permission_id',
        'drug_store_id'
    ];


}
