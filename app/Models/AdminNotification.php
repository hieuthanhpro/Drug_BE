<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class AdminNotification
 *
 *
 * @package App\Models
 */
class AdminNotification extends Eloquent
{
    protected $className = "AdminNotification";

    protected $table = 'admin_notification';
    protected $fillable = [
        'title',
        'content',
        'content_sms',
        'url',
        'type',
        'sent_type',
        'sent_to',
        'status'
    ];

    public $enum_mapping = [
        'type' => ['news', 'promotion'],
        'status' => ['waiting', 'done', 'error']
    ];
}
