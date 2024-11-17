<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 10:41 AM
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
class Unit extends Eloquent
{
    protected $table = 'unit';
    protected $fillable = [
        'name',
    ];


}
