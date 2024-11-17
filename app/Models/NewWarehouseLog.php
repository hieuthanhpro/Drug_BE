<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\LibExtension\LogEx;

class NewWarehouseLog extends Model
{
    protected $className = "NewWarehouseLog";

    public $timestamps = false;

    protected $table = 'new_warehouse_logs';

    protected $fillable = [
    	'wh_id',
    	'drug_store_id',
    	'ref',
    	'action',
    	'old_value',
    	'new_value',
    	'created_user_id',
    	'created_username',
    	'desc',
    	'created',
    ];

    const ACT_STATUS_CREATE = '';

    public $logNames = [
        self::ACT_STATUS_CREATE => [
            'title' => '',
            'class' => ''
        ],
    ];

    public function pushLog($wh_id, $drug_store_id, $ref, $action, $oldVal, $newVal, $desc, $flush = false) {
        LogEx::methodName($this->className, 'pushLog');

    	if (empty($desc)) {
            if (!empty($this->logNames[$action])) {
                $title = $this->logNames[$action]['title'];
                $desc = $title;
                if (!empty($oldVal)) $desc .= " từ <b>%oldVal%</b>";
                if (!empty($newVal)) $desc .= " thành <b>%newVal%</b>";
            } else {
                $desc = "Thay đổi '<b>$field</b>' từ '<b>%oldVal%</b>' => '<b>%newVal%</b>'";
            }
        }

        $warehouseLog = array(
            'wh_id' => $wh_id,
            'drug_store_id' => $drug_store_id,
            'package' => json_encode($package),
            'action' => $action,
            'old_value' => $oldVal,
            'new_value' => $newVal,
            'desc' => $desc,
        );


        if (!empty($flush)) {
            return $this->create($warehouseLog);
        } else {
            // $this->_warehouseLogs[] = $warehouseLog;
        }
    }

    public function pushQuantityLog($wh_id, $drug_store_id, $ref, $action, $oldVal, $newVal, $desc, $flush = false) {
        LogEx::methodName($this->className, 'pushQuantityLog');

        $warehouseLog = array(
            'wh_id' => $wh_id,
            'drug_store_id' => $drug_store_id,
            'ref' => $ref,
            'action' => $action,
            'old_value' => $oldVal,
            'new_value' => $newVal,
            'desc' => $desc,
        );


        return NewWarehouseLog::create($warehouseLog);
        if (!empty($flush)) {
            // return NewWarehouseLog::create($warehouseLog);
        } else {
            // $this->_warehouseLogs[] = $warehouseLog;
        }
    }
}
