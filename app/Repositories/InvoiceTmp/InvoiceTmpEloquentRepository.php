<?php

/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/12/2018
 * Time: 11:07 AM
 */

namespace App\Repositories\InvoiceTmp;

use App\Models\InvoiceTmp;
use App\Models\InvoiceDetailTmp;
use App\Models\Supplier;
use App\Repositories\AbstractBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\LibExtension\LogEx;

class InvoiceTmpEloquentRepository extends AbstractBaseRepository implements InvoiceTmpRepositoryInterface
{
    protected $className = "InvoiceTmpEloquentRepository";
    public function __construct(InvoiceTmp $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getDetailById($id)
    {
        LogEx::methodName($this->className, 'getDetailById');

        $invoice = array('invoice' => $this->findOneById($id));
        $invoice_info = $invoice['invoice'];
        if (empty($invoice_info)) {
            return [];
        }
        $supplier_name = null;
        $supplier = Supplier::select('supplier.name')->where('id', $invoice_info['customer_id'])->get();
        if (!empty($supplier) && !empty($supplier[0])) {
            $supplier_name = $supplier[0]['name'];
        }
        $invoice_info['supplier_id'] = $invoice_info['customer_id'];
        $invoice_info['supplier_name'] = $supplier_name;
        $invoice_detail = InvoiceDetailTmp::select('invoice_detail_tmp.*')->selectRaw('drug.name as drug_name, drug.drug_code, unit.name as unit_name')->leftJoin('drug', 'drug.id', 'invoice_detail_tmp.drug_id')->leftJoin('unit', 'unit.id', 'invoice_detail_tmp.unit_id')->where('invoice_id', $id)->get();
        LogEx::info($invoice_detail);
        if ($invoice_detail) {
            $invoice['invoice_detail'] = $invoice_detail;
        }
        return $invoice;
    }


    public function getAllByDrugStore($drug_store_id)
    {
        LogEx::methodName($this->className, 'getAllByDrugStore');

        $data = InvoiceTmp::select(
            'invoice_tmp.*',
            'supplier.id as supplier_id',
            'supplier.name as supplier_name',
            'supplier.address',
            'supplier.website',
            'supplier.email',
            'supplier.number_phone'
        )
            ->leftjoin('supplier', 'supplier.id', 'invoice_tmp.customer_id')
            ->where('invoice_tmp.drug_store_id', $drug_store_id)
            ->orderBy('invoice_tmp.updated_at', 'desc')
            ->paginate(10);
        return $data;
    }

    public function deleteTmpInvoice($id)
    {
        LogEx::methodName($this->className, 'deleteTmpInvoice');

        $check = $this->findOneById($id);
        if (empty($check)) {
            return false;
        }
        InvoiceDetailTmp::where('invoice_id', $id)->delete();
        $this->deleteOneById($id);
        return true;
    }
}
