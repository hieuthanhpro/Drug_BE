<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 10:39 AM
 */

namespace App\Models;
use Illuminate\Support\Facades\DB;
use App\Models\BaseModel as Eloquent;
use App\LibExtension\LogEx;

/**
 * Class User
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $fullname
 * @property string $email
 * @property string $phone
 * @property int $company_id
 * @property bool $role_type
 * @property bool $status
 * @property string $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class Warehouse extends Eloquent
{
    protected $className = "Warehouse";

    protected $table = 'warehouse';
    protected $fillable = [
        'drug_store_id',
        'drug_id',
        'unit_id',
        'is_basic',
        'exchange',
        'quantity',
        'main_cost',
        'pre_cost',
        'current_cost',
        'warning_quantity',
        'description',
        'expiry_date',
        'is_check',
        'number',
        'updated_at',
        'created_at',
    ];

    // Enum data type to fix some bug
    public $enum_mapping = [
        'type' => ['import', 'export'],
        'is_basic' => ['yes', 'no'],
    ];

    public function unit() {
        LogEx::methodName($this->className, 'unit');

        return $this->hasOne('App\Models\Unit', 'id', 'unit_id');
    }


    function getTotal($drug_store_id){
        LogEx::methodName($this->className, 'getTotal');

        $query = DB::select('select count(*) as total from (select count(*)  from warehouse
                            where drug_store_id = ?  and is_check = 0
                            GROUP BY drug_id, number) as x',[$drug_store_id]);
        return $query;
    }


    function getListDrugAllUnit($drug_store_id, $page, $size){
        LogEx::methodName($this->className, 'getListDrugAllUnit');

        $list_drug = DB::select('select  warehouse.number, count(drug_id) as uniitNumber, warehouse.id
                  from warehouse
                    JOIN drug on drug.id = warehouse.drug_id
                    where warehouse.drug_store_id = 3  and is_check = 0
                    GROUP BY warehouse.drug_id, warehouse.number
                    limit 0 , 100');
        return $list_drug;
    }


    function getAllUnitByDrug($array){
        LogEx::methodName($this->className, 'getAllUnitByDrug');

        $data = DB::select('select unit_id, drug_id, number from warehouse
                WHERE drug_store_id = 3 and (drug_id, number)
                in (?)',[$array]);
        return $data;
    }

}
