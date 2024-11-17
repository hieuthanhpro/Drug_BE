<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 5/4/2019
 * Time: 2:45 PM
 */


namespace App\Repositories\InvoiceDose;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceDose;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use App\LibExtension\LogEx;

class InvoiceDoseEloquentRepository extends AbstractBaseRepository implements InvoiceDoseRepositoryInterface
{
    protected $className = "InvoiceDoseEloquentRepository";
    public function __construct(InvoiceDose $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getDetailByInvoice($invoice_id, $drug_store_id){
        LogEx::methodName($this->className, 'getDetailByInvoice');

        $data = DB::table('invoice_dose')
            ->select(
                'invoice_dose.*',
                'dose_drug.name',
                'dose_drug.dose_code',
                'dose_drug.usage',
                'dose_drug.current_cost'
            )
            ->join('dose_drug','dose_drug.id','invoice_dose.dose_id')
            ->where('invoice_dose.invoice_id', $invoice_id)
            ->where('invoice_dose.drug_store_id',$drug_store_id)
            ->get();

        return $data;
    }

    public function getListInvoiceId($drug_store_id){
        LogEx::methodName($this->className, 'getListInvoiceId');

        $data = DB::table('invoice_dose')
            ->select('invoice_id')
            ->groupBy('invoice_id')
            ->where('invoice_dose.drug_store_id',$drug_store_id)
            ->paginate(20);
        return $data;
    }

    function getDetailByInvoiceId($invoice_id){
        LogEx::methodName($this->className, 'getDetailByInvoiceId');

    }

}
