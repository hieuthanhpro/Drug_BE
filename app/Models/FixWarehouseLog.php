<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\LibExtension\LogEx;

class FixWarehouseLog extends Model
{
    protected $className = "FixWarehouseLog";

    public $timestamps = false;

    protected $table = 'fix_warehouse_logs';

    protected $fillable = [
    	'wh_id',
    	'drug_store_id',
    	'old_value',
    	'new_value',
    	'desc',
    	'created',
    ];

    public function pushQuantityLog($wh_id, $drug_store_id, $oldVal, $newVal, $desc, $flush = false) {
        LogEx::methodName($this->className, 'pushQuantityLog');

        $warehouseLog = array(
            'wh_id' => $wh_id,
            'drug_store_id' => $drug_store_id,
            'old_value' => $oldVal,
            'new_value' => $newVal,
            'desc' => $desc,
        );


        return FixWarehouseLog::create($warehouseLog);
    }
}
