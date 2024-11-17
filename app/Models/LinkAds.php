<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

class LinkAds extends Eloquent
{
    protected $table = 'link_ads';
    protected $fillable = [
        'id',
        'text',
        'link',
    ];
}