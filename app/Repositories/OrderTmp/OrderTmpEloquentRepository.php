<?php

namespace App\Repositories\OrderTmp;

use App\Models\OrderTmp;
use App\Models\OrderDetailTmp;
use App\Repositories\AbstractBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\LibExtension\LogEx;

class OrderTmpEloquentRepository extends AbstractBaseRepository implements OrderTmpRepositoryInterface
{
    protected $className = "OrderTmpEloquentRepository";

    public function __construct(OrderTmp $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getDetailById($id) {
        LogEx::methodName($this->className, 'getDetailById');

        $data_order = DB::table("order_tmp")
            ->select(
                'order_tmp.*',
                'supplier.name as supplier_name'
            )
            ->leftjoin('supplier','supplier.id','order_tmp.supplier_id')
            ->where('order_tmp.id', $id)
            ->get()->toArray();
        $data_result['order_tmp'] = $data_order[0];

        $data_detail = DB::table("order_detail_tmp")
            ->select(
                'order_detail_tmp.*',
                'unit.name as unit_name',
                'drug.name as drug_name',
                'drug.image as image',
                'drug.drug_code',
                'drug.image'
            )
            ->join('unit', 'unit.id', 'order_detail_tmp.unit_id')
            ->join('drug', 'drug.id', 'order_detail_tmp.drug_id')
            ->where('order_id', $id)
            ->get();

        $data_result['order_detail_tmp'] = $data_detail;
        return $data_result;
    }


    public function getAllByDrugStore($drug_store_id){
        LogEx::methodName($this->className, 'getAllByDrugStore');

        $data = OrderTmp::select(
            'order_tmp.*',
            'supplier.id as supplier_id',
            'supplier.name as supplier_name',
            'supplier.address',
            'supplier.website',
            'supplier.email',
            'supplier.number_phone'
        )
            ->leftjoin('supplier','supplier.id','order_tmp.supplier_id')
            ->where('order_tmp.drug_store_id',$drug_store_id)->paginate(10);
        return $data;
    }
}
