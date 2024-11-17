<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/19/2018
 * Time: 3:16 PM
 */

namespace App\Repositories\WarehouseLog;

use App\Models\WarehouseLog;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Support\Facades\DB;
use app\libextension\logex;

class WarehouseLogEloquentRepository extends AbstractBaseRepository implements WarehouseLogRepositoryInterface
{
    protected $className = "WarehouseLogEloquentRepository";

    public function __construct(WarehouseLog $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


    public function getLogCurrentday($current_day, $drug_store_id)
    {
        LogEx::methodName($this->className, 'getLogCurrentday');

        $data = DB::table('warehouse_log')
            ->select(
                'warehouse_log.action_type',
                'warehouse_log.created_at',
                'users.name',
                'invoice.amount'
            )
            ->join('users', 'users.id', 'warehouse_log.user_id')
            ->join('invoice', 'invoice.id', 'warehouse_log.invoice_id')
            ->where('warehouse_log.drug_store_id', $drug_store_id)
            ->wheredate('warehouse_log.created_at', '>=', $current_day)
            ->orderByRaw('created_at desc')
            ->get();

        return $data;

    }

}
