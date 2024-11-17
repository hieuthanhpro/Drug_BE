<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Repositories\DoseDrug\DoseDrugRepositoryInterface;
use App\Repositories\DoseDetail\DoseDetailRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\InvoiceDose\InvoiceDoseRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Services\BuyDoseService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;

class DoseDrugController extends Controller
{
    protected $className = "Backend\DoseDrugController";

    protected $doseDrug;
    protected $doseDetail;
    protected $warehouse;
    protected $drug;
    protected $buyDose;
    protected $invoiceService;
    protected $invoice;
    protected $drugStore;
    protected $invoiceDose;

    public function __construct(
        DoseDrugRepositoryInterface    $doseDrug,
        DoseDetailRepositoryInterface  $doseDetail,
        WarehouseRepositoryInterface   $warehouse,
        DrugRepositoryInterface        $drug,
        DrugStoreRepositoryInterface   $drugStore,
        BuyDoseService                 $buyDose,
        InvoiceService                 $invoiceService,
        InvoiceRepositoryInterface     $invoice,
        InvoiceDoseRepositoryInterface $invoiceDose
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->doseDrug = $doseDrug;
        $this->doseDetail = $doseDetail;
        $this->warehouse = $warehouse;
        $this->drug = $drug;
        $this->buyDose = $buyDose;
        $this->invoiceService = $invoiceService;
        $this->invoice = $invoice;
        $this->drugStore = $drugStore;
        $this->invoiceDose = $invoiceDose;
    }

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $data = $request->input();
        $name = $data['name'] ?? null;
        $group = $data['group'] ?? null;
        $category = $data['category'] ?? null;
        $result = $this->doseDrug->getListDoseDrug($user->drug_store_id, $name, $category, $group);

        $result = json_decode(json_encode($result), true);

        $dataResult = array(
            'current_page' => $result['current_page'],
            'first_page_url' => $result['first_page_url'],
            'from' => $result['from'],
            'last_page' => $result['last_page'],
            'to' => $result['to'],
            'total' => $result['total'],
            'path' => $result['path'],
            'last_page_url' => $result['last_page_url'],
            'next_page_url' => $result['next_page_url'],
        );

        foreach ($result['data'] as $value) {
            $dose_total = array();
            $detail = $this->doseDetail->findManyBy('dose_id', $value['id']);
            foreach ($detail as $item) {
                $count = $this->warehouse->countQuantityByDrug($user->drug_store_id, $item->drug_id, $item->unit_id);
                if (!empty($count)) {
                    $dose_total[] = $count->total / $item->quantity;
                } else {
                    $dose_total[] = 0;
                    break;
                }
            }
            $value['dose_total'] = min($dose_total);
            $dataResult['data'][] = $value;
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataResult);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $data = $request->input();
        $doseDrug = array(
            'group_id' => $data['group_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'current_cost' => $data['current_cost'],
            'usage' => $data['usage']
        );
        $doseDetial = $data['dose_detial'];
        $check = $this->doseDrug->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        } else {
            DB::beginTransaction();
            try {
                $this->doseDrug->updateOneById($id, $doseDrug);
                $this->doseDetail->deleteManyBy('dose_id', $id);
                foreach ($doseDetial as $value) {
                    $value['dose_id'] = $id;
                    $this->doseDetail->create($value);
                }
                DB::commit();
                unset($data['userInfo']);
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
            } catch (\Exception $e) {
                DB::rollback();
                LogEx::try_catch($this->className, $e);
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
            }
        }
    }


    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $data = $request->input();
        $doseCode = 'TL' . Utils::getSequenceDB('TL');
        $doseDrug = array(
            'drug_store_id' => $user->drug_store_id,
            'group_id' => $data['group_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'dose_code' => $doseCode,
            'current_cost' => $data['current_cost'],
            'usage' => $data['usage']
        );
        $doseDetial = $data['dose_detial'];

        $check = $this->doseDrug->findOneByCredentials(['drug_store_id' => $user->drug_store_id, 'name' => $data['name']]);
        if (!empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::CONFLICT, CommonConstant::MSG_ALREADY_EXISTS);
        } else {
            DB::beginTransaction();
            try {
                $insert = $this->doseDrug->create($doseDrug);
                $last_id_dose = $insert->id;
                foreach ($doseDetial as $value) {
                    $value['dose_id'] = $last_id_dose;
                    $this->dose_detail->create($value);
                }
                DB::commit();
                unset($data['userInfo']);
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
            } catch (\Exception $e) {
                DB::rollback();
                LogEx::try_catch($this->className, $e);
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ERROR);
            }
        }
    }

    public function getDetailDose($id)
    {
        LogEx::methodName($this->className, 'getDetailDose');

        $doseDrug = $this->doseDrug->getDetailDose($id);
        $doseDetail = $this->doseDetail->getDetailById($id);
        $doseDrug->dose_detail = $doseDetail;
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $doseDrug);
    }

    public function getListDrugBuy($id)
    {
        LogEx::methodName($this->className, 'getListDrugBuy');

        $dataResult = array();
        $detail = $this->doseDetail->findManyBy('dose_id', $id);
        foreach ($detail as $value) {
            $drug = $this->drug->getDrugForDose($value->drug_id, $value->unit_id);
            if (!empty($drug)) {
                foreach ($drug as $item) {
                    $dataResult[] = $item;
                }
            }
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataResult);
    }

    public function buyDose(Request $request)
    {
        LogEx::methodName($this->className, 'buyDose');

        $user = $request->userInfo;
        $invoiceDetail = null;
        $input = $request->input();
        $doseDrug = $input['dose'];
        foreach ($doseDrug as $value) {
            $detail_dose = $this->doseDetail->findManyBy('dose_id', $value['id']);
            foreach ($detail_dose as $item) {
                $tmp = $this->buyDose->getNumberBuy($item->drug_id, $item->unit_id, $item->quantity * $value['quantity'], $item->usage);
                foreach ($tmp as $detail) {
                    $detail['expiry_date'] = date_format(date_create($detail['expiry_date']), "Y-m-d");
                    $invoice_detail[] = $detail;
                }
            }
        }

        $img_base64 = $input['image'] ?? '';
        $imagePath = "";
        if (!empty($img_base64)) {
            $img = $this->generateImageFromBase64($img_base64);
            $image_path = url("upload/images/" . $img);
        }

        $data = array(
            "amount" => $input['amount'],
            "pay_amount" => $input['pay_amount'],
            "vat_amount" => $input['vat_amount'],
            "discount" => $input['discount'],
            "customer_id" => $input['customer_id'],
            "status" => $input['status'],
            "payment_status" => $input['payment_status'],
            "invoice_type" => $input['invoice_type'],
            "created_at" => $input['created_at'],
            "image" => $imagePath,
            "invoice_detail" => $invoiceDetail,
        );
        $drugStoreInfo = $this->drugStore->findOneById($user->drug_store_id);
        $resultId = $this->invoiceService->createInvoice($data, $user, $drugStoreInfo);

        // Thông báo lỗi TH bán quá
        if ($resultId == -1) {
            $msg = "Liều thuốc có thuốc bán quá số lượng trong kho. Vui lòng kiểm tra lại";
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
        }

        // Thông báo lỗi TH bán số lẻ
        if ($resultId == -2) {
            $msg = "Liều thuốc có thuốc chứa số lượng lẻ. Vui lòng kiểm tra lại";
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
        }

        if ($resultId != false) {
            foreach ($doseDrug as $item) {
                $saveDose = array(
                    'drug_store_id' => $user->drug_store_id,
                    'invoice_id' => $resultId,
                    'quantity' => $item['quantity'],
                    'dose_id' => $value['id']
                );
                $this->invoiceDose->create($saveDose);
            }
            $invoice = $this->invoice->getDetailById($resultId);
            $doseDetail = $this->invoiceDose->getDetailByInvoice($resultId, $user->drug_store_id);
            $invoice['dose_detail'] = $doseDetail;
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $invoice);
        } else {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ERROR);
        }
    }

    public function show(Request $request)
    {
        LogEx::methodName($this->className, 'show');

        $user = $request->userInfo;
        $listInvoiceId = $this->invoiceDose->getListInvoiceId($user->drug_store_id)->toArray();

        $dataResult = array(
            'current_page' => $listInvoiceId['current_page'],
            'first_page_url' => $listInvoiceId['first_page_url'],
            'from' => $listInvoiceId['from'],
            'last_page' => $listInvoiceId['last_page'],
            'to' => $listInvoiceId['to'],
            'total' => $listInvoiceId['total'],
            'path' => $listInvoiceId['path'],
            'last_page_url' => $listInvoiceId['last_page_url'],
            'next_page_url' => $listInvoiceId['next_page_url'],
        );

        foreach ($listInvoiceId['data'] as $item) {
            $invoice = $this->invoice->getDetailForDose($item->invoice_id, $user->drug_store_id);
            $dataResult['data'][] = $invoice;
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataResult);
    }

    public function getDetailInvoiceDose($invoice_id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailInvoiceDose');

        $user = $request->userInfo;
        $invoice = $this->invoice->getDetailForDose($invoice_id, $user->drug_store_id);

        if (count($invoice) == 0) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $invoice['invoice_detail'] = $this->invoiceDose->getDetailByInvoice($invoice_id, $user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $invoice);
    }
}
