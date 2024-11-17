<?php

namespace App\Repositories\TDrugUnit;

use App\LibExtension\LogEx;
use App\Models\TDrugUnit;
use App\Repositories\AbstractBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TDrugUnitEloquentRepository extends AbstractBaseRepository implements TDrugUnitRepositoryInterface
{
    protected $className = "TDrugUnitEloquentRepository";

    public function __construct(TDrugUnit $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function copyTDrugUnitByStoreId($destStoreId)
    {
        $now = Carbon::now();
        DB::statement("insert into t_drug_unit select w.drug_store_id,
            w.drug_id,
            w.unit_id,
            w.exchange,
            max(case when w.is_basic = 'yes' then 1 else 0 end) = 1,
            max(coalesce(w.main_cost, 0)),
            max(coalesce(w.pre_cost, 0)),
            max(coalesce(w.current_cost, 0)),
            '" . $now . "',
            '" . $now . "' from    warehouse w
    where   w.is_check      = true
    and     w.drug_id       > 0
    and     w.drug_store_id = " . $destStoreId . "
    group by w.drug_store_id, w.drug_id, w.unit_id, w.exchange");
    }
}
