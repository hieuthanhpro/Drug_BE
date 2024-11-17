<?php

/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 1/13/2019
 * Time: 6:07 PM
 */


namespace App\Repositories\DrugMaster;

use App\LibExtension\Utils;
use App\Models\DrugMaster;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;

class DrugMasterEloquentRepository extends AbstractBaseRepository implements DrugMasterRepositoryInterface
{
    protected $className = "DrugMasterEloquentRepository";
    public function __construct(DrugMaster $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getDrugMasterByName($name)
    {
        LogEx::methodName($this->className, 'getDrugMasterByName');

        $data = DB::table('drug_master_data')
            ->select(
                'drug_master_data.*',
                'unit.id as unit_id',
                'unit.name as unit_name'
            )
            ->join('unit', 'drug_master_data.unit_id', 'unit.id')
            ->where('drug_master_data.name', 'ilike', $name . '%')
            ->paginate(20);
        return $data;
    }

    /**
     * api v3
     * from f_drug_master_list on v3
    */
    public function drugMasterListV3($name)
    {
        LogEx::methodName($this->className, 'getDrugMasterByName');

        return DB::table(DB::raw('drug_master_data d'))
            ->select(
                'd.id',
                'd.name',
                'd.short_name',
                'd.drug_code',
                'd.barcode',
                'd.image',
                'd.drug_category_id',
                'd.drug_group_id',
                'd.country',
                'd.package_form',
                'd.company',
                'd.substances',
                'd.concentration',
                'd.registry_number',
                'd.unit_id',
                'u.name as unit_name'
            )
            ->join(DB::raw('unit u'),'u.id','=','d.unit_id')
            ->where('d.active','=','yes')
            ->when(!empty($name), function ($query) use ($name) {
                $name = trim($name);
                $whereRaw = "1 = 1 AND (d.name ~* '" . $name
                . "' or d.short_name  ~* '" . $name
                . "' or d.drug_code  ~* '" . $name
                . "' or d.barcode  ~* '" . $name
                . "' or d.substances  ~* '" . $name
                . "' or d.concentration  ~* '" . $name
                . "' or d.company  ~* '" . $name
                . "' or d.registry_number  ~* '" . $name
                . "' or u.name ~* '" . $name
                ."')";
                $query->whereRaw($whereRaw);
            })
            ->orderBy('d.id','DESC');
    }
}
