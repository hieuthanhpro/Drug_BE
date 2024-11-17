<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/12/2018
 * Time: 11:09 AM
 */

namespace App\Repositories\InvoiceDetail;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceDetail;
use App\Repositories\AbstractBaseRepository;
use App\LibExtension\LogEx;

class InvoiceDetailEloquentRepository extends AbstractBaseRepository implements InvoiceDetailRepositoryInterface
{
    protected $className = "InvoiceDetailEloquentRepository";
    public function __construct(InvoiceDetail $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getTopDrug($drug_store_id){
        LogEx::methodName($this->className, 'getTopDrug');

        $data_result = DB::table("invoice_detail")
            ->select(DB::raw('SUM(quantity) as total_quantity'),'invoice_detail.drug_id')
            ->join('invoice','invoice.id','invoice_detail.invoice_id')
            ->where('drug_store_id',$drug_store_id)
            ->groupBy('invoice_detail.drug_id')
            ->first();
        return $data_result;
    }

}
