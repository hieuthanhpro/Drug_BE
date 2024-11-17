<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 11:05 AM
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
class Role extends Eloquent
{
    protected $table = 'role';
    protected $fillable = [
        'name',
        'drug_store_id',
        'active',
    ];

    // Enum data type to fix some bug
    public $enum_mapping = [
        'active' => ['yes', 'no']
    ];
}
