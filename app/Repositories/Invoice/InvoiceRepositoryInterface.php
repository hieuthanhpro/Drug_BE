<?php

namespace App\Repositories\Invoice;

use App\Http\Requests\Invoice\InvoiceFilterRequest;
use App\Repositories\RepositoryInterface;

interface InvoiceRepositoryInterface extends RepositoryInterface
{
    public function countInvoiceByTime($dug_store_id);

    public function getInvoiceByDay($dug_store_id);

    public function getInvoiceByYear($year,$dug_store_id);

    public function getInvoiceByCodition($drug_store_id,$form_date = null,$to_date = null,$invoice_code = null,$cutomer = null,$invoice_tye = null,$drug = null,$supplier_invoice_code=null, $addition = []);
    public function getDetailById($id);
    public function getHistory($list_drug,$drug_name,$from_date = null,$to_date =null);
    public function getInvoiceReturn($id);
    public function getDrugRemain($id);
    public function cancelInvoice($id);
    public function getListByCustomer($customer_id,$form_date=null,$to_date=null);
    public function getListDetailByCustomer($customer_id,$form_date=null,$to_date=null);
    public function getListBySupplier($supplier_id);
    public function getDetailForDose($id, $drug_store_id);
    public function getListInvoiceIV1($drug_store_id, $searchData);
    public function getListInvoiceIV2($drug_store_id, $searchData);
    public function getListInvoiceIV7($drug_store_id, $searchData);

    public function countInvoiceByStoreId($storeId);

    public function filter($invoiceFilterInput, $drugStoreId, $limit);
}