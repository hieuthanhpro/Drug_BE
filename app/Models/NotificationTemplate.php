<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;
use App\LibExtension\LogEx;

/**
 * Class NotificationTemplate
 *
 *
 * @package App\Models
 */
class NotificationTemplate extends Eloquent
{
    protected $className = "NotificationTemplate";

    protected $table = 'notification_template';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $fillable = [
        'key',
        'name',
        'title',
        'content',
        'content_sms',
        'created_at',
        'updated_at',
    ];
}
