<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/19/2018
 * Time: 3:19 PM
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
class WarehouseLog extends Eloquent
{
    protected $table = 'warehouse_log';
    protected $fillable = [
        'drug_store_id',
        'user_id',
        'action_type',
        'invoice_id',
        'description',
    ];
}
