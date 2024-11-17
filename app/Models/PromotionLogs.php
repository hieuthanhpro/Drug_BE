<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class PromotionLogs
 *
 *
 * @package App\Models
 */
class PromotionLogs extends Eloquent
{
    protected $className = "PromotionLogs";

    protected $table = 'promotion_logs';
    protected $fillable = [
        'invoice_id',
        'promotion_json',
        'drug_store_id',
        'order_id'
    ];
}
