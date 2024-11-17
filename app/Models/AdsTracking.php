<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 2/13/2019
 * Time: 9:07 AM
 */

namespace App\Models;

use App\Models\BaseModel as Eloquent;


class AdsTracking extends Eloquent
{
    protected $table = 'ads_tracking';
    protected $fillable = [
        'banner',
        'account',
        'create_time',
        'action_name',
    ];
}