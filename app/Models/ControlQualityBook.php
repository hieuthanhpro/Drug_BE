<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\LibExtension\LogEx;

class ControlQualityBook extends Model
{
    protected $className = "ControlQualityBook";

    protected $fillable = [
    	'drug_store_id',
    	'charge_person',
    	'tracking_staff'
    ];

    public function controlQualityBookDetails()
    {
        LogEx::methodName($this->className, 'controlQualityBookDetails');

    	return $this->hasMany(ControlQualityBookDetail::class, 'book_id');
    }
}
