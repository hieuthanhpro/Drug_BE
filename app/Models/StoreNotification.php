<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class StoreNotification
 *
 *
 * @package App\Models
 */
class StoreNotification extends Eloquent
{
    protected $className = "StoreNotification";
    protected $table = 'store_notification';
    protected $fillable = [
        'user_id',
        'user_phone',
        'title',
        'content',
        'content_sms',
        'url',
        'type',
        'is_read',
        'is_sent',
        'user_phone'
    ];

    public $enum_mapping = [
        'type' => ['system', 'news', 'order', 'promotion']
    ];
}
