<?php

namespace App\Services;

use App\LibExtension\Utils;
use App\Repositories\Cashbook\CashbookRepositoryInterface;
use App\Repositories\CashType\CashTypeRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use Carbon\Carbon;
use App\LibExtension\LogEx;
use Illuminate\Support\Facades\DB;

/**
 * Class CashbookService
 * @package App\Services
 */
class CashbookService
{
    protected $className = "CashbookService";

    private $cashbook;
    private $cashType;
    private $invoice;

    public function __construct(CashTypeRepositoryInterface $cashType, CashbookRepositoryInterface $cashbook,
                                InvoiceRepositoryInterface $invoice)
    {
        LogEx::constructName($this->className, '__construct');

        $this->cashType = $cashType;
        $this->cashbook = $cashbook;
        $this->invoice = $invoice;
    }

    /**
     * Tạo mới phiếu sổ quỹ
     * @param $requestInput
     * @param $drugStoreId
     * @param null $userId
     * @return mixed|null
     */
    public function createCashbook($requestInput, $drugStoreId, $userId = null)
    {
        try {
            if(!isset($requestInput['code'])){
                if ($requestInput['type'] == 'PC') {
                    $code = 'PC' . Utils::getSequenceDB('PC');
                }else{
                    $code = 'PT' . Utils::getSequenceDB('PT');
                }
                $requestInput['code'] = $code;
            }

            $requestInput['method'] = 'manual';
            $requestInput['status'] = 'done';
            $requestInput['payment_method'] = 'cash';
            $requestInput['drug_store_id'] = $drugStoreId;
            $requestInput['created_by'] = $userId ?? $requestInput['created_by'] ?? 0;

            return $this->cashbook->create($requestInput);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
        }
        return null;
    }

    public function createModelCashbook($amount, $invoice, $user, $isPaydebt = false)
    {
        if (in_array($invoice->invoice_type, array('IV1', 'IV4'))) {
            $code = 'PT' . Utils::getSequenceDB('PT');
        } else if (in_array($invoice->invoice_type, array('IV2', 'IV3', 'IV7'))) {
            $code = 'PC' . Utils::getSequenceDB('PC');
        }
        if (isset($code)) {
            $cashType = $this->cashType->getCashTypeByInvoiceType($invoice->invoice_type);
            if (in_array($invoice->invoice_type, array('IV1', 'IV3'))) {
                $customerId = $invoice->customer_id;
                $name = $invoice->customer_name ?? 'Khách lẻ';
                $address = $invoice->address;
                $phone = $invoice->number_phone;
            } else if (in_array($invoice->invoice_type, array('IV2', 'IV4', 'IV7'))) {
                $supplierId = $invoice->customer_id;
                $name = $invoice->supplier_name;
                $address = $invoice->supplier_address;
                $phone = $invoice->supplier_phone;
            }

            if ($invoice->is_order) {
                $gdpId = $invoice->customer_id;
                $name = $invoice->supplier_name;
                $address = $invoice->supplier_address;
                $phone = $invoice->supplier_phone;
            }

            $reason = "";
            switch ($invoice->invoice_type) {
                case 'IV1':
                    if ($isPaydebt) {
                        $reason = "Phiếu thu công nợ cho hóa đơn bán hàng mã " . $invoice->invoice_code;
                    } else {
                        $reason = "Phiếu thu cho hóa đơn bán hàng mã " . $invoice->invoice_code;
                    }
                    break;
                case 'IV2':
                    $reason = "Phiếu chi cho hóa đơn nhập hàng mã " . $invoice->invoice_code;
                    break;
                case 'IV3':
                    $reason = "Phiếu chi cho hóa đơn khách trả hàng mã " . $invoice->invoice_code;
                    break;
                case 'IV4':
                    $reason = "Phiếu thu cho hóa đơn trả hàng NCC mã " . $invoice->invoice_code;
                    break;
                case 'IV7':
                    $reason = "Phiếu chi cho hóa đơn nhập tồn mã " . $invoice->invoice_code;
                    break;
            }

            return array(
                'drug_store_id' => $user->drug_store_id,
                'code' => $code,
                'cash_type' => $cashType->id,
                'customer_id' => $invoice->is_order ? null : $customerId ?? null,
                'supplier_id' => $invoice->is_order ? null : $supplierId ?? null,
                'gdp_id' => $invoice->is_order ? $gdpId : null,
                'invoice_id' => $invoice->id,
                'name' => $name ?? '',
                'address' => $address ?? '',
                'phone' => $phone ?? '',
                'reason' => $reason,
                'amount' => $amount,
                'created_by' => 0,
                'status' => 'done',
                'cash_date' => Carbon::now(),
                'method' => 'auto',
                'payment_method' => $invoice->payment_method
            );
        }
    }

    public function updateStatusByInvoiceId($invoiceId, $status)
    {
        $listCashbook = $this->cashbook->findManyBy("invoice_id", $invoiceId);
        if (isset($listCashbook) && $listCashbook->count() > 0) {
            $this->cashbook->updateManyBy("invoice_id", $invoiceId, ['status' => $status]);
        }
    }

    /**
     * Lọc sổ quỹ
     * @param $requestInput
     * @param $drugStoreId
     * @return mixed
     */
    public function filter($requestInput, $drugStoreId, $export = 0, $limit = 10) {
        return $this->cashbook->filter($requestInput, $drugStoreId, $export, $limit);
    }

    public function updateDebtInvoice ($invoice_id, $amount, $drug_store_id) {
        $data = DB::select('select v3.f_invoice_detail(?) as result', [json_encode(array('invoice_id' => $invoice_id, 'drug_store_id' => $drug_store_id))])[0]->result;
        $data = json_decode($data);
        if(isset($data) && isset($amount)){
            $dataInvoice = $data->invoice;
            $payAmountFinal = $amount + $dataInvoice->pay_amount;
            if($dataInvoice->invoice_type === "IV1"){
                if($dataInvoice->status != 'temp' && $dataInvoice->drug_store_id == $drug_store_id){
                    if($dataInvoice->shipping_status == 'done'){
                        $this->invoice->updateOneById($invoice_id, [
                            'pay_amount' => $payAmountFinal,
                            'status' => 'done',
                            'payment_status' => $payAmountFinal == $dataInvoice->amount ? 'paid' : 'partial_paid'
                        ]);
                    }else{
                        $this->invoice->updateOneById($invoice_id, [
                            'pay_amount' => $payAmountFinal,
                            'payment_status' => $payAmountFinal == $dataInvoice->amount ? 'paid' : 'partial_paid'
                        ]);
                    }
                }
            }else{
                $this->invoice->updateOneById($invoice_id, [
                    'pay_amount' => $payAmountFinal
                ]);
            }
        }
    }
}
