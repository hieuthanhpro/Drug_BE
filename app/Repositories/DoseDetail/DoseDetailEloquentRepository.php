<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:43 AM
 */
namespace App\Repositories\DoseDetail;

use App\Models\DoseDetail;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;

class DoseDetailEloquentRepository extends AbstractBaseRepository implements DoseDetailRepositoryInterface
{
    protected $className = "DoseDetailEloquentRepository";

    public function __construct(DoseDetail $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getDetailById($dose_id){
        LogEx::methodName($this->className, 'getDetailById');

        $data_result = array();
        $list_detail = $this->findManyBy('dose_id',$dose_id);

        foreach ($list_detail as $value){
            $data = DoseDetail::join('drug', 'drug.id', '=', 'dose_detail.drug_id')
                ->join('unit', 'unit.id', '=', 'dose_detail.unit_id')
                ->select(
                    'dose_detail.*',
                    'drug.name as drug_name',
                    'unit.name as unit_name'
                )
            ->where('dose_detail.id',$value->id)
            ->first();
            $data_result[] = $data;
        }
        return $data_result;
    }

}
