<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlQualityBookDetail extends Model
{
    protected $fillable = [
    	'book_id',
    	'date',
    	'drug_id',
    	'unit_id',
    	'number',
    	'expire_date',
    	'quantity',
    	'sensory_quality',
    	'conclude',
    	'reason',
    ];
}
