<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 4/15/2019
 * Time: 1:34 PM
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
class Permissions extends Eloquent
{
    protected $table = 'permissions';
    protected $fillable = [
        'name',
        'title',
        'group_id',
        'created_at',
        'updated_at'
    ];
}
