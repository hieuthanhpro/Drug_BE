<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drug\DrugRequest;
use App\Http\Requests\Invoice\InvoiceFilterRequest;
use App\Http\Requests\Invoice\InvoiceSalesRequest;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\InvoiceTmp;
use App\Models\Unit;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\DrugMaster\DrugMasterRepositoryInterface;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\InvoiceDetail\InvoiceDetailRepositoryInterface;
use App\Repositories\InvoiceTmp\InvoiceTmpRepositoryInterface;
use App\Repositories\InvoiceWarehouse\InvoiceWarehouseRepositoryInterface;
use App\Repositories\PaymentLogs\PaymentLogsRepositoryInterface;
use App\Repositories\Prescription\PrescriptionRepositoryInterface;
use App\Repositories\Supplier\SupplierRepositoryInterface;
use App\Repositories\Unit\UnitRepositoryInterface;
use App\Repositories\Vouchers\VouchersRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Services\ApiServiceGPP;
use App\Services\CashbookService;
use App\Services\ExcelService;
use App\Services\InvoiceService;
use App\Services\InvoiceWarehouseService;
use App\Services\PromotionLogsService;
use App\Services\PromotionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class InvoiceController extends Controller
{
    protected $className = "Backend\InvoiceController";

    protected $invoice;
    protected $invoiceService;
    protected $invoiceTmp;
    protected $drug;
    protected $invoiceDetail;
    protected $prescription;
    protected $drugStore;
    protected $apiService;
    protected $warehouse;
    protected $voucher;
    protected $masterData;
    protected $unit;
    protected $supplier;
    protected $excel;
    protected $cashbookService;
    protected $invoiceWarehouse;
    protected $invoiceWarehouseService;
    protected $paymentLogs;
    protected $tOrder;
    protected $promotionService;
    protected $promotionLogsService;

    public function __construct(
        InvoiceRepositoryInterface          $invoice,
        InvoiceService                      $invoiceService,
        InvoiceTmpRepositoryInterface       $invoiceTmp,
        DrugRepositoryInterface             $drug,
        InvoiceDetailRepositoryInterface    $invoiceDetail,
        PrescriptionRepositoryInterface     $prescription,
        DrugStoreRepositoryInterface        $drugStore,
        ApiServiceGPP                       $apiService,
        WarehouseRepositoryInterface        $warehouse,
        VouchersRepositoryInterface         $voucher,
        DrugMasterRepositoryInterface       $masterData,
        UnitRepositoryInterface             $unit,
        SupplierRepositoryInterface         $supplier,
        ExcelService                        $excel,
        CashbookService                     $cashbookService,
        InvoiceWarehouseService             $invoiceWarehouseService,
        InvoiceWarehouseRepositoryInterface $invoiceWarehouseRepository,
        PaymentLogsRepositoryInterface      $paymentLogsRepository,
        PromotionService                    $promotionService,
        PromotionLogsService                $promotionLogsService
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->invoice = $invoice;
        $this->invoiceService = $invoiceService;
        $this->invoiceTmp = $invoiceTmp;
        $this->drug = $drug;
        $this->invoiceDetail = $invoiceDetail;
        $this->prescription = $prescription;
        $this->drugStore = $drugStore;
        $this->apiService = $apiService;
        $this->warehouse = $warehouse;
        $this->voucher = $voucher;
        $this->masterData = $masterData;
        $this->unit = $unit;
        $this->supplier = $supplier;
        $this->excel = $excel;
        $this->cashbookService = $cashbookService;
        $this->invoiceWarehouseService = $invoiceWarehouseService;
        $this->invoiceWarehouse = $invoiceWarehouseRepository;
        $this->paymentLogs = $paymentLogsRepository;
        $this->promotionService = $promotionService;
        $this->promotionLogsService = $promotionLogsService;
    }

    // New
    public function filter(Request $invoiceFilterRequest){
        LogEx::methodName($this->className, 'filter');

        $invoiceFilterParam = $invoiceFilterRequest->input();
        $invoiceFilterParam['url'] = $invoiceFilterRequest->url();
        $data = $this->invoice->filter($invoiceFilterParam, $invoiceFilterRequest->userInfo->drug_store_id);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function export(Request $invoiceFilterRequest){
        LogEx::methodName($this->className, 'export');
        $data = $this->invoiceService->export($invoiceFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
    //=========================================//

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $condition = $request->input();
        $fromDate = $condition['from_date'] ?? null;
        $toDate = $condition['to_date'] ?? null;
        $invoiceCode = $condition['invoice_code'] ?? null;
        $customer = $condition['customer_name'] ?? null;
        $invoice_tye = $condition['invoice_type'] ?? null;
        $drug = $condition['drug_name'] ?? null;
        $supplierInvoiceCode = $condition['supplier_invoice_code'] ?? null;

        $addition = [];
        if (!empty($condition['drug_name'])) $addition['drug_name'] = $condition['drug_name'];
        if (!empty($condition['number'])) $addition['number'] = $condition['number'];
        if (!empty($condition['tax_number'])) $addition['tax_number'] = $condition['tax_number'];
        if (!empty($condition['number_phone'])) $addition['number_phone'] = $condition['number_phone'];
        if (!empty($condition['original_invoice_code'])) $addition['original_invoice_code'] = $condition['original_invoice_code'];

        $data = $this->invoice->getInvoiceByCodition($user->drug_store_id, $fromDate, $toDate, $invoiceCode, $customer, $invoice_tye, $drug, $supplierInvoiceCode, $addition);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function indexIV1(Request $request)
    {
        LogEx::methodName($this->className, 'indexIV1');

        $user = $request->userInfo;
        $searchData = $request->input();
        $data = $this->invoice->getListInvoiceIV1($user->drug_store_id, $searchData);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function indexIV2(Request $request)
    {
        LogEx::methodName($this->className, 'indexIV2');

        $user = $request->userInfo;
        $searchData = $request->input();
        $data = $this->invoice->getListInvoiceIV2($user->drug_store_id, $searchData);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function indexIV7(Request $request)
    {
        LogEx::methodName($this->className, 'indexIV7');

        $user = $request->userInfo;
        $searchData = $request->input();
        $data = $this->invoice->getListInvoiceIV7($user->drug_store_id, $searchData);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $users = $request->userInfo;
        $drugStoreInfo = $this->drugStore->findOneById($users->drug_store_id);
        $data = $request->input();
        unset($data['userInfo']);
        $detailInvoice = $data['invoice_detail'];
        $detailDb = $this->invoiceDetail->findManyBy('invoice_id', $id);

        DB::beginTransaction();
        try {
            foreach ($detailDb as $value) {
                $inArray = true;
                foreach ($detailInvoice as $item) {
                    $inArray = in_array([$value['drug_id'], $value['number']], $item);
                }
                if ($inArray == false) {
                    $isBasic = $this->warehouse->findOneByCredentials(['drug_id' => $value['drug_id'], 'unit_id' => $value['unit_id'], 'is_check' => 1]);
                    Log::info("Warehouse before update: " . json_encode($isBasic));
                    if ($isBasic->is_basic == 'yes') {
                        $qua = $value['quantity'];
                    } else {
                        $qua = $value['quantity'] * $isBasic->exchange;
                    }
                    $this->warehouse->updateAmount($value['drug_id'], $qua, null, $value['number']);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, "[update] Update quantity error" . $e);
            DB::rollback();
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ERROR);
        }

        $invoiceInsert = array(
            'amount' => $data['amount'],
            'pay_amount' => $data['pay_amount'],
            'invoice_type' => $data['invoice_type'],
            'customer_id' => $data['customer_id'] ?? null,
            'discount' => $data['discount'] ?? 0,
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? null,
            'payment_status' => $data['payment_status'] ?? null,
            'refer_id' => $data['refer_id'] ?? null,
            'receipt_date' => $data['receipt_date'] ?? null,
            'vat_amount' => $data['vat_amount'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'supplier_invoice_code' => $data['supplier_invoice_code'] ?? ''
        );
        $code = $this->invoice->findOneById($id);

        $supplier = $this->supplier->findOneById($data['customer_id']);

        $dataGpp = array(
            'ma_phieu' => $code->invoice_code,
            'ma_co_so' => $drugStoreInfo->base_code,
            'ngay_nhap' => str_replace('-', '', $data['receipt_date']),
            'loai_phieu_nhap' => 1,
            'ghi_chu' => $data['description'] ?? '',
            'ten_co_so_cung_cap' => $supplier->name
        );

        DB::beginTransaction();
        try {
            $this->invoice->updateOneById($id, $invoiceInsert);
            $this->invoiceDetail->deleteAllByCredentials(['invoice_id' => $id]);
            $isCheck = 0;
            foreach ($detailInvoice as $value) {
                // Check số lượng <= 0 hoặc lẻ
                if (!is_int($value['quantity']) || $value['quantity'] <= 0) {
                    $isCheck = 1;
                    break;
                }

                $value['invoice_id'] = $id;
                $value['drug_store_id'] = $users->drug_store_id;
                $itemInvoice = array(
                    'invoice_id' => $id,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'usage' => $value['usage'] ?? '',
                    'number' => $value['number'] ?? '',
                    'expiry_date' => $value['expiry_date'] ?? '',
                    'cost' => $value['main_cost'] ?? 0,
                    'vat' => $value['vat'] ?? 0
                );
                $check = $this->invoiceService->updateWarehouse($id, $value);
                $drugInfo = $this->drug->findOneById($value['drug_id']);
                $unitInfo = $this->unit->findOneById($value['unit_id']);
                $masterCheck = $this->masterData->findOneBy('drug_code', $drugInfo->drug_code);
                if (!empty($masterCheck)) {
                    $dataGpp['chi_tiet'][] = array(
                        "ma_thuoc" => $drugInfo->drug_code,
                        "ten_thuoc" => $drugInfo->name,
                        "so_lo" => $value['number'] ?? '',
                        'han_dung' => str_replace('-', '', $value['expiry_date']),
                        'so_dklh' => $drugInfo->registry_number,
                        'so_luong' => $value['quantity'],
                        "don_gia" => $value['main_cost'] ?? '',
                        "don_vi_tinh" => $unitInfo->name
                    );
                }
                if ($check == true) {
                    $this->invoiceDetail->create($itemInvoice);
                } else {
                    $isCheck = 2;
                    break;
                }
            }
            $this->voucher->updateManyBy('invoice_id', $id, ['status' => 0]);

            $newVouchers = array(
                'invoice_id' => $id,
                'code' => 'PC-' . $code->invoice_code,
                'invoice_type' => 'IV2',
                'supplier_id' => $data['customer_id'],
                'amount' => $data['pay_amount'],
                'drug_store_id' => $users->drug_store_id,
                'user_id' => $users->id,
                'type' => 0
            );
            $this->voucher->create($newVouchers);
            if ($isCheck == 0) {
                $method = "PUT";
                $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/phieu_nhap';
                $dataGpp = json_encode($dataGpp);
                $this->apiService->callAPI($method, $url, $dataGpp, $drugStoreInfo->token);
                DB::commit();
                $result = $this->invoice->getDetailById($id);
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
            } else if ($isCheck == 1) {
                $msg = "Đã có thuốc bán với số lượng không phải số nguyên lớn hơn không. Vui lòng kiểm tra lại";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            } else {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, "Không thể update hóa đơn này");
            }
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store ');

        $user = $request->userInfo;
        $data = $request->input();

        $drugStoreInfo = $this->drugStore->findOneById($user->drug_store_id);

        $imgBase64 = $data['image'] ?? '';
        if (!empty($imgBase64)) {
            $img = $this->generateImageFromBase64($imgBase64);
            $data['image'] = url("upload/images/" . $img);
        }

        $resultId = $this->invoiceService->createInvoice($data, $user, $drugStoreInfo);

        // Thông báo lỗi TH bán quá
        if ($resultId == -1) {
            $msg = "Đã có thuốc bán quá số lượng trong kho. Vui lòng kiểm tra lại";
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $msg);
        }

        // Thông báo lỗi TH bán quá
        if ($resultId == -2) {
            $msg = "Đã có thuốc bán với số lượng không phải số nguyên lớn hơn không. Vui lòng kiểm tra lại";
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $msg);
        }

        if ($resultId != false) {
            //$result = $this->invoice->getDetailById($resultId);
            $result = $this->invoiceService
                ->invoiceDetailV3([
                    'invoice_id' => $resultId,
                    'request' => $request
                ]);

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
        }

        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function getDetailInvoice($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailInvoice');

        $user = $request->userInfo;
        $check = $this->invoice->findOneById($id);
        if (!empty($check) && $check->drug_store_id == $user->drug_store_id) {
            $data = $this->invoice->getDetailById($id);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    public function invoiceIV1(Request $request)
    {
        LogEx::methodName($this->className, 'invoiceIV1');

        $user = $request->userInfo;
        $data = $request->input();

        $imgBase64 = $data['image'] ?? '';
        if (!empty($imgBase64)) {
            $img = $this->generateImageFromBase64($imgBase64);
            $data['image'] = url("upload/images/" . $img);
        }

        DB::beginTransaction();
        try {
            $resultId = $this->invoiceService->createInvoiceIV1($data, $user);

            // Thông báo lỗi TH bán quá
            if ($resultId == -1) {
                $msg = "Đã có thuốc bán quá số lượng trong kho. Vui lòng kiểm tra lại";
                $resp = $this->responseApi(CommonConstant::BAD_REQUEST, $msg, null);
                DB::rollback();
                return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $msg);
            }

            // Thông báo lỗi số lượng bán
            if ($resultId == -2) {
                $msg = "Đã có thuốc bán với số lượng không phải số nguyên lớn hơn không. Vui lòng kiểm tra lại.";
                $resp = $this->responseApi(CommonConstant::BAD_REQUEST, $msg, null);
                DB::rollback();
                return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $msg);
            }

            if (!$this->invoiceService->updateInvoiceDetailQuantity($resultId)) {
                DB::rollback();
                $msg = "Không thể cập nhật số lượng tồn kho.";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            }

            $result = $this->invoice->getDetailById($resultId);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }

        $this->invoiceService->syncDQGInvoice($resultId, $user);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
    }

    public function invoiceIV2(Request $request)
    {
        LogEx::methodName($this->className, 'invoiceIV2');

        $user = $request->userInfo;
        $data = $request->input();

        if (empty($data['customer_id'])) {
            DB::rollback();
            $msg = "Chưa nhập Nhà cung cấp.";
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $msg);
        }

        DB::beginTransaction();
        try {
            $isEdit = false;
            $mapFunc = function ($val) {
                return " (drug_id = {$val['drug_id']} and number = '{$val['number']}') ";
            };
            if ((!isset($data['is_temp']) || $data['is_temp']) !== true && isset($data['invoice_id'])) {
                $isEdit = true;
                $invoiceDetails = $this->invoiceDetail->findAllByCredentials(['invoice_id' => $data['invoice_id']])->toArray();
            }

            $resultId = $this->invoiceService->createInvoiceIV2IV7($data, $user);

            // Thông báo lỗi số lượng nhập
            if ($resultId == -2) {
                DB::rollback();
                $msg = "Đã có thuốc nhập với số lượng không phải số nguyên lớn hơn không. Vui lòng kiểm tra lại.";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            }

            if ($resultId == -3) {
                DB::rollback();
                $msg = "Số lô không hợp lệ.";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            }

            if (!$this->invoiceService->updateInvoiceDetailQuantity($resultId)) {
                $msg = "Không thể cập nhật số lượng tồn kho.";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            }

            // remove temp invoice
            if (isset($data['is_temp']) && $data['is_temp'] === true) {
                $this->invoiceTmp->deleteTmpInvoice($data['invoice_id']);
            }

            if ($isEdit) {
                $quantityCheck = DB::table('warehouse')
                    ->selectRaw('count(*) as cnt')
                    ->where('is_basic', 'yes')
                    ->where('quantity', '<', 0)
                    ->whereRaw('(' . join(' or ', array_map($mapFunc, $invoiceDetails)) . ')')
                    ->get()->toArray();

                if ($quantityCheck[0]->cnt > 0) {
                    DB::rollback();
                    $msg = "Số lượng tồn kho không hợp lệ";
                    return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
                }
            }

            $result = $this->invoice->getDetailById($resultId);
            DB::commit();
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e);
            DB::rollback();
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }

        $this->invoiceService->syncDQGInvoice($resultId, $user);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
    }

    public function invoiceIV7(Request $request)
    {
        LogEx::methodName($this->className, 'invoiceIV7');

        $user = $request->userInfo;
        $data = $request->input();

        DB::beginTransaction();
        try {
            $isEdit = false;
            $mapFunc = function ($val) {
                return " (drug_id = {$val['drug_id']} and number = '{$val['number']}') ";
            };
            if (isset($data['invoice_id'])) {
                $isEdit = true;
                $invoiceDetails = $this->invoiceDetail->findAllByCredentials(['invoice_id' => $data['invoice_id']])->toArray();
            }

            $resultId = $this->invoiceService->createInvoiceIV2IV7($data, $user);

            // Thông báo lỗi số lượng nhập
            if ($resultId == -2) {
                DB::rollback();
                $msg = "Đã có thuốc nhập với số lượng không phải số nguyên lớn hơn không. Vui lòng kiểm tra lại.";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            }

            if ($resultId == -3) {
                DB::rollback();
                $msg = "Số lô không hợp lệ.";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            }

            if (!$this->invoiceService->updateInvoiceDetailQuantity($resultId)) {
                DB::rollback();
                $msg = "Không thể cập nhật số lượng tồn kho.";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            }

            if ($isEdit) {
                $quantityCheck = DB::table('warehouse')
                    ->selectRaw('count(*) as cnt')
                    ->where('is_basic', 'yes')
                    ->where('quantity', '<', 0)
                    ->whereRaw('(' . join(' or ', array_map($mapFunc, $invoiceDetails)) . ')')
                    ->get()->toArray();

                if ($quantityCheck[0]->cnt > 0) {
                    DB::rollback();
                    $msg = "Số lượng tồn kho không hợp lệ";
                    return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
                }
            }

            $result = $this->invoice->getDetailById($resultId);
            DB::commit();
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e);
            DB::rollback();
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
    }

    public function getInvoiceIV7Detail($id, Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceIV7Detail');

        $user = $request->userInfo;
        $data = $this->invoice->getIV7DetailById($id, $user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getHistoryWareHouse(Request $request)
    {
        LogEx::methodName($this->className, 'getHistoryWareHouse');

        $user = $request->userInfo;
        $input = $request->input();
        $arrayId = array();
        $fromDate = $input['from_date'] ?? null;
        $toDate = $input['to_date'] ?? null;
        $drugName = $input['drug_name'] ?? '';
        $listDrug = $this->drug->getlistDrug($user->drug_store_id);
        foreach ($listDrug as $value) {
            $arrayId[] = $value->id;
        }
        $data = $this->invoice->getHistory($arrayId, $drugName, $fromDate, $toDate);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getInvoiceReturn($id)
    {
        LogEx::methodName($this->className, 'getInvoiceReturn');

        $data = $this->invoice->getInvoiceReturn($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getDrugRemain($id)
    {
        LogEx::methodName($this->className, 'getDrugRemain');

        $data = $this->invoice->getDrugRemain($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    private function deleteInvoice($drug_store, $invoice_detail)
    {
        LogEx::methodName($this->className, 'deleteInvoice');

        $type = $invoice_detail->invoice_type;
        $url = '';
        if ($type == "IV1") {
            $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/hoa_don/' . $drug_store->base_code . '/' . $invoice_detail->invoice_code;
        } elseif ($type == "IV2") {
            $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/phieu_nhap/' . $drug_store->base_code . '/' . $invoice_detail->invoice_code;
        } elseif ($type == "IV3") {
            $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/phieu_nhap/' . $drug_store->base_code . '/' . $invoice_detail->invoice_code;
        } elseif ($type == "IV4") {
            $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/phieu_xuat/' . $drug_store->base_code . '/' . $invoice_detail->invoice_code;
        }
        $method = "DELETE";
        return $this->apiService->callAPI($method, $url, $invoice_detail, $drug_store->token);
    }

    public function importInvoiceExcel(Request $request)
    {
        LogEx::methodName($this->className, 'importInvoiceExcel');

        $user = $request->userInfo;
        $drugStoreInfo = $this->drugStore->findOneById($user->drug_store_id);
        $input = $request->input();
        $file = $request->file('file');
        $allowed = array('xls', 'xlsx');
        $check = $this->excel->checkFileInput($file, $allowed);
        $invoiceDetail = array();
        /*thông tin hóa đơn*/
        $invoiceType = $input['invoice_type'];
        if ($invoiceType = "IV7") {
            $dataInvoice = array(
                "amount" => $input['amount'],
                "pay_amount" => $input['pay_amount'],
                "vat_amount" => $input['vat_amount'],
                "discount" => $input['discount'],
                "created_at" => $input['created_at'],
                "receipt_date" => $input['receipt_date'],
                "status" => $input['status'],
                "payment_status" => $input['payment_status'],
                "invoice_type" => $input['invoice_type'],
                'type_exist' => $input['type_exist'],
            );
        } else {
            $dataInvoice = array(
                "amount" => $input['amount'],
                "pay_amount" => $input['pay_amount'],
                "vat_amount" => $input['vat_amount'],
                "discount" => $input['discount'],
                "created_at" => $input['created_at'],
                "receipt_date" => $input['receipt_date'],
                "customer_id" => $input['customer_id'],
                "status" => $input['status'],
                "payment_status" => $input['payment_status'],
                "invoice_type" => $input['invoice_type'],
                'type_exist' => $input['type_exist'] ?? '',
            );
        }
        if ($check == false) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_EXCEL_FAIL);
        } else {
            $data = Excel::load($file)->get();

            // Limit 200 row
            if (count($data) > 200) {
                $msg = "File chỉ được tối đa 200 dòng";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            }

            DB::beginTransaction();
            try {
                foreach ($data as $key => $value) {
                    if (empty($value['ten_hang']) || empty($value['so_dang_ky']) || empty($value['so_lo'])) {
                        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_EXCEL_MISS);
                    } else {
                        // Thêm đk check theo mã dược quốc gia để biết thuốc có hay chưa
                        $condCheckDrug = ['name' => $value['ten_hang'], 'registry_number' => $value['so_dang_ky'], 'drug_store_id' => $user->drug_store_id];
                        if (!empty($value['ma_duoc_quoc_gia'])) {
                            $condCheckDrug['drug_code'] = $value['ma_duoc_quoc_gia'];
                        }

                        $checkDrug = $this->drug->findOneByCredentials($condCheckDrug);
                        if (!empty($checkDrug)) {
                            $unitId = $this->unit->findOneBy('name', $value['don_vi_nhap']);
                            $checkWarehouse = $this->warehouse->findOneByCredentials(
                                [
                                    'unit_id' => $unitId->id,
                                    'drug_id' => $checkDrug->id,
                                    'is_check' => 1
                                ]
                            );
                            if (empty($checkWarehouse)) {
                                // $msg = "Đơn vị nhập không có trong hệ thống";
                                $msg = "Đơn vị nhập ({$value['don_vi_nhap']}) của thuốc {$value['ten_hang']} (line " . ($key + 1) . ") không có trong hệ thống";
                                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
                            } else {
                                $tmp = array(
                                    'drug_id' => $checkDrug->id,
                                    'unit_id' => $unitId->id,
                                    'quantity' => $value['so_luong'],
                                    'main_cost' => $value['gia_nhap'],
                                    'vat' => 0,
                                    'number' => $value['so_lo'],
                                    'exchange' => $checkWarehouse->exchange,
                                    'expiry_date' => $value['han_su_dung'],
                                    'current_cost' => $value['gia_ban']

                                );
                                $invoiceDetail[] = $tmp;
                            }
                        } else {
                            if (!empty($value['ma_duoc_quoc_gia'])) {
                                $drugCode = $value['ma_duoc_quoc_gia'];
                                $isMasterData = null;
                            } else {
                                $drugCode = $drug_code = 'DRUG' . Utils::getSequenceDB('DRUG');
                                $isMasterData = 1;
                            }
                            $drugInfo = array(
                                'drug_store_id' => $user->drug_store_id,
                                'drug_code' => $drugCode,
                                'barcode' => $drugCode,
                                'vat' => $value['vat'] ?? 0,
                                'is_master_data' => $isMasterData,
                                'name' => $value['ten_hang'],
                                'substances' => $value['hoat_chat_chinh'] ?? '',
                                'country' => $value['nuoc_san_xuat'] ?? '',
                                'company' => $value['hang_san_xuat'] ?? '',
                                'package_form' => isset($input['quy_cach_dong_goi']) ? $value['quy_cach_dong_goi'] : '',
                                'registry_number' => $value['so_dang_ky'] ?? '',
                            );
                            $drugInsert = $this->drug->create($drugInfo);
                            $donViCoBan = $this->unit->findOneBy('name', $value['don_vi_co_ban']);
                            $donViNhap = $this->unit->findOneBy('name', $value['don_vi_nhap']);
                            if ($value['don_vi_co_ban'] != $value['don_vi_nhap']) {
                                $warehouse = array(
                                    array(
                                        'drug_store_id' => $user->drug_store_id,
                                        'drug_id' => $drugInsert->id,
                                        'unit_id' => $donViCoBan->id,
                                        'exchange' => 1,
                                        'is_basic' => 'yes',
                                        'warning_quantity' => 1000,
                                        'pre_cost' => 0,
                                        'quantity' => 0,
                                    ),
                                    array(
                                        'drug_store_id' => $user->drug_store_id,
                                        'drug_id' => $drugInsert->id,
                                        'unit_id' => $donViNhap->id,
                                        'exchange' => $value['gia_tri_quy_doi'],
                                        'is_basic' => 'no',
                                        'warning_quantity' => 0,
                                        'pre_cost' => 0,
                                        'quantity' => 0,
                                    ),
                                );
                            } else {
                                $warehouse = array(
                                    array(
                                        'drug_store_id' => $user->drug_store_id,
                                        'drug_id' => $drugInsert->id,
                                        'unit_id' => $donViCoBan->id,
                                        'exchange' => 1,
                                        'is_basic' => 'yes',
                                        'warning_quantity' => 1000,
                                        'pre_cost' => 0,
                                        'quantity' => 0,
                                    ),
                                );
                            }
                            foreach ($warehouse as $item) {
                                $this->warehouse->create($item);
                            }

                            $tmp = array(
                                'drug_id' => $drugInsert->id,
                                'unit_id' => $donViNhap->id,
                                'quantity' => $value['so_luong'],
                                'main_cost' => $value['gia_nhap'],
                                'vat' => 0,
                                'number' => $value['so_lo'],
                                'exchange' => $value['gia_tri_quy_doi'],
                                'expiry_date' => $value['han_su_dung'],
                                'current_cost' => $value['gia_ban']
                            );
                            $invoiceDetail[] = $tmp;
                        }
                    }
                }
                $dataInvoice['invoice_detail'] = $invoiceDetail;
                $resultId = $this->invoiceService->createInvoice($dataInvoice, $user, $drugStoreInfo);
                $result = $this->invoice->getDetailById($resultId);
                DB::commit();
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
            } catch (\Exception $e) {
                DB::rollBack();
                LogEx::try_catch($this->className, $e);
                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
            }
        }
    }

    /**
     * api v3
     * importInvoiceV3
    */
    public function importInvoiceV3(Request $request)
    {
        LogEx::methodName($this->className, 'importInvoiceV3');

        $user = $request->userInfo;
        $drugStoreInfo = $this->drugStore->findOneById($user->drug_store_id);
        $dataInvoiceList = [];
        $data = $request->input();
        $source = $request->input('source');
        $listInvoices = [];

        if (count($data['datas']) === 0) return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, 'Không có hóa đơn nào cần import');

        $data = $data['datas'];

        DB::beginTransaction();
        try {
            $collections = collect($data)->chunk(50);
            foreach ($collections as $collection) {
                foreach ($collection as $value) {
                    $invoiceDetail = [];
                    $dataInvoice = array(
                        "invoice_code" => Utils::coalesce($value, 'invoice_code', null),
                        "amount" => Utils::coalesce($value, 'amount', null),
                        "customer_id" => Utils::coalesce($value, 'customer_id', 0),
                        "method" => $value['pay_amount'] > 20000000 ? "online" : "direct",
                        "payment_method" => $value['pay_amount'] > 20000000 ? "banking" : "cash",
                        "pay_amount" => Utils::coalesce($value, 'pay_amount', null),
                        "date" => Utils::coalesce($value, 'date', null),
                        "debt" => Utils::coalesce($value, 'debt', null),
                        "vat_amount" => Utils::coalesce($value, 'vat_amount', null),
                        "description" => Utils::coalesce($value, 'description', null),
                        "source" => $source,
                        "discount" => Utils::coalesce($value, 'discount', null),
                        "discount_rate" => Utils::coalesce($value, 'discount_rate', null),
                        "invoice_type" => "IV1",
                        "gift_items" => [],
                        "status" => "done",
                        "receipt_date" => Utils::coalesce($value, 'receipt_date', null),
                        "customer_excel" => Utils::coalesce($value, 'customer_name', null),
                        "is_import" => 1
                    );

                    foreach ($value['invoice_details'] as $item) {
                        $date = explode('/', $item['expiry_date']);
                        $unit_id = Unit::where('name', $item["unit_name"])->first()['id'];//Unit::where('name', $item['unit_name'])->get()->toArray();
                        $invoiceDetail[] = [
                            "combo_name" => Utils::coalesce($item, 'combo_name', null),
                            "cost" => Utils::coalesce($item, 'cost', null),
                            "discount" => Utils::coalesce($item, 'discount', null),
                            "discount_promotion" => Utils::coalesce($item, 'discount_promotion', null),
                            "drug_id" => Utils::coalesce($item, 'drug_id', null),
                            "expiry_date" => "$date[2]/$date[1]/$date[0]",
                            "quantity" => Utils::coalesce($item, 'quantity', null),
                            "number" => Utils::coalesce($item, 'number', null),
                            "unit_id" => $unit_id,
                            "total_cost" => Utils::coalesce($item, 'total_cost', null),
                            "vat" => Utils::coalesce($item, 'vat', 0),
                            "is_import" => 1,
                            "drug_code" => Utils::coalesce($item, 'drug_code', null),
                            "drug_name" => Utils::coalesce($item, 'drug_name', null),
                            "mfg_date" => Utils::coalesce($item, 'mfg_date', null),
                            "unit_name" => Utils::coalesce($item, 'unit_name', null),
                            "substances" => Utils::coalesce($item, 'substances', null),
                            "registry_number" => Utils::coalesce($item, 'registry_number', null),
                            "total" => Utils::coalesce($item, 'total', null),
                            "exchange" => Utils::coalesce($item, 'exchange', null),
                        ];
                    }

                    $dataInvoice['invoice_detail'] = $invoiceDetail;
                    $dataInvoiceList [] = $dataInvoice;
                    $listInvoiceID[] = $this->invoiceService->createInvoice($dataInvoice, $user, $drugStoreInfo);
                }
            }
            DB::commit();
            $listInvoices = [
                'count' => count($data),
                'insert_false' => !empty(array_count_values($listInvoiceID)['false']) ? array_count_values($listInvoiceID)['false'] : 0
            ];

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $listInvoices);
        } catch (\Exception $e) {
            DB::rollBack();
            LogEx::try_catch($this->className, $e);

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function sell(Request $request)
    {
        LogEx::methodName($this->className, 'sell');

        $user = $request->userInfo;
        $data = $this->invoiceService->sell($request);

        // Add cashbook
        $dataInvoice = DB::select('select v3.f_invoice_detail(?) as result', [Utils::getParams($request->input(), array('invoice_id' => $data))])[0]->result;
        $dataInvoice = json_decode($dataInvoice);
        if (isset($dataInvoice) && !empty($dataInvoice->invoice->status) && !empty($dataInvoice->invoice->pay_amount)) {
            if ($dataInvoice->invoice->status === 'done' && $dataInvoice->invoice->pay_amount > 0) {
                $modelCashbook = $this->cashbookService->createModelCashbook($dataInvoice->invoice->pay_amount, $dataInvoice->invoice, $user, true);
                $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
            }
        }

        if ($data > 0) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, Utils::getErrorMessage('invoice', $data));
    }

    /**
     * api v3
     * form sell
    */
    public function sellV3(Request $request)
    {
        LogEx::methodName($this->className, 'sell');

        $user = $request->userInfo;
        $data = $this->invoiceService->sellV3($request);

        // Add cashbook
        $dataInvoice = DB::select('select v3.f_invoice_detail(?) as result', [Utils::getParams($request->input(), array('invoice_id' => $data))])[0]->result;
        $dataInvoice = json_decode($dataInvoice);
        if (isset($dataInvoice) && $dataInvoice->invoice->status === 'done' && $dataInvoice->invoice->pay_amount > 0) {
            $modelCashbook = $this->cashbookService->createModelCashbook($dataInvoice->invoice->pay_amount, $dataInvoice->invoice, $user, true);
            $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
        }

        if ($data > 0) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, Utils::getErrorMessage('invoice', $data));
    }

    public function warehousing(Request $request)
    {
        LogEx::methodName($this->className, 'warehousing');
        $user = $request->userInfo;
        $data = $this->invoiceService->warehousingInvoice($request);
        $drugStore = $this->drugStore->findOneById($user->drug_store_id);

        // Add cashbook
        $dataInvoice = DB::select('select v3.f_invoice_detail(?) as result', [Utils::getParams($request->input(), array('invoice_id' => $data))])[0]->result;
        $dataInvoice = json_decode($dataInvoice);
        if (isset($dataInvoice) && $dataInvoice->invoice->status === 'done' && $dataInvoice->invoice->pay_amount > 0 && $dataInvoice->invoice->invoice_type === "IV2") {
            $modelCashbook = $this->cashbookService->createModelCashbook($dataInvoice->invoice->pay_amount, $dataInvoice->invoice, $user, true);
            $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
        }
        if ($dataInvoice->invoice->status === 'done' && $drugStore->type === "GDP") {
            $dataInvoiceWarehouse = $this->invoiceWarehouse->findOneBy("invoice_id", $data);
            if ($dataInvoiceWarehouse) {
                $this->invoiceWarehouse->updateOneById($dataInvoiceWarehouse->id, ['status' => 'done', "ref_code" => $dataInvoice->invoice->supplier_invoice_code]);
            } else if ($dataInvoice->invoice->is_order && !isset($dataInvoice->invoice->warehouse_invoice_id)) {
                $modelInvoiceWarehouse = $this->invoiceWarehouseService->createModelByInvoice($dataInvoice->invoice, $user, $dataInvoice->invoice->is_order);
                if ($modelInvoiceWarehouse) {
                    $dataInvoiceWarehouse = $this->invoiceWarehouseService->createInvoiceWarehouse($modelInvoiceWarehouse);
                    if (isset($dataInvoiceWarehouse)) {
                        $this->invoiceDetail->updateManyBy('invoice_id', $dataInvoice->invoice->id, ['warehouse_invoice_id' => $dataInvoiceWarehouse->id]);
                    }
                }
            }
        }

        if ($data > 0) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, Utils::getErrorMessage('invoice', $data));
    }

    /**
     * api v3 convert
     * from warehousing
     */
    public function warehousingV3(Request $request)
    {
        LogEx::methodName($this->className, 'warehousing');
        $user = $request->userInfo;
        $data = $this->invoiceService->warehousingInvoiceV3($request);
        $drugStore = $this->drugStore->findOneById($user->drug_store_id);

        // Add cashbook
        $dataInvoice = $this->invoice->getDetailById($data);
        if (isset($dataInvoice) &&
            !empty($dataInvoice['invoice']->status) &&
            !empty($dataInvoice['invoice']->pay_amount) &&
            !empty($dataInvoice['invoice']->invoice_type)
        ) {
            if ($dataInvoice['invoice']->status === 'done' &&
                $dataInvoice['invoice']->pay_amount > 0 &&
                $dataInvoice['invoice']->invoice_type === "IV2"
            ) {
                $modelCashbook = $this->cashbookService->createModelCashbook($dataInvoice['invoice']->pay_amount, $dataInvoice['invoice'], $user, true);
                $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
            }
        }

        if (!empty($dataInvoice['invoice']->status) &&
            !empty($drugStore->type)
        ) {
            if ($dataInvoice['invoice']->status === 'done' &&
                $drugStore->type === 'GDP'
            ) {
                $dataInvoiceWarehouse = $this->invoiceWarehouse->findOneBy("invoice_id", $data);
                if ($dataInvoiceWarehouse) {
                    $this->invoiceWarehouse->updateOneById($dataInvoiceWarehouse->id, ['status' => 'done', "ref_code" => $dataInvoice['invoice']->supplier_invoice_code]);
                } else if ($dataInvoice['invoice']->is_order && !isset($dataInvoice['invoice']->warehouse_invoice_id)) {
                    $modelInvoiceWarehouse = $this->invoiceWarehouseService->createModelByInvoice($dataInvoice['invoice'], $user, $dataInvoice['invoice']->is_order);
                    if ($modelInvoiceWarehouse) {
                        $dataInvoiceWarehouse = $this->invoiceWarehouseService->createInvoiceWarehouse($modelInvoiceWarehouse);
                        if (isset($dataInvoiceWarehouse)) {
                            $this->invoiceDetail->updateManyBy('invoice_id', $dataInvoice['invoice']->id, ['warehouse_invoice_id' => $dataInvoiceWarehouse->id]);
                        }
                    }
                }
            }
        }

        if ($data > 0) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, Utils::getErrorMessage('invoice', $data));
    }

    public function warehousingTemp(Request $request)
    {
        LogEx::methodName($this->className, 'warehousingTemp');

        $data = $this->invoiceService->warehousingInvoiceTemp($request);
        if ($data > 0) {
            $update = InvoiceTmp::where('id', $data)->update(['created_at' => \Carbon\Carbon::now()->format('Y-m-d')]);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, Utils::getErrorMessage('invoice', $data));
    }

    /**
     * api v3
     * from warehousingTemp
     */
    public function warehousingTempV3(Request $request)
    {
        LogEx::methodName($this->className, 'warehousingTempV3');

        $inputData = $request->input();
        $user = $request->userInfo;
        $drugStoreInfo = $this->drugStore->findOneById($user->drug_store_id);
        $data = $this->invoiceService->warehousingInvoiceTempV3($inputData, $user, $drugStoreInfo);

        if ($data > 0) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }

        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, Utils::getErrorMessage('invoice', $data));
    }

    public function getList(Request $request)
    {
        LogEx::methodName($this->className, 'getList');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery('select * from v3.f_invoice_list(?)', [Utils::getParams($requestInput)], $request->url(), $requestInput);
        $data = Utils::getSumData(
            $data,
            $requestInput,
            'select sum(t.amount) as amount, 
            sum(t.vat_amount) as vat_amount, 
            sum(t.discount) as discount, 
            sum(t.pay_amount) as pay_amount, 
            sum(t.amount) + sum(t.vat_amount) - sum(t.discount) - sum(t.pay_amount) as debt from tmp_output t'
        );
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function warehousingStatistic(Request $request)
    {
        LogEx::methodName($this->className, 'warehousingStatistic');

        $data = $this->invoiceService->warehousingStatistic($request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from warehousingStatistic and export
    */
    public function warehousingStatisticV3(Request $request)
    {
        LogEx::methodName($this->className, 'warehousingStatisticV3');

        $data = $this->invoiceService->warehousingStatisticV3($request);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function exportWarehousingStatisticV3(Request $request) {
        LogEx::methodName($this->className, 'export');

        $data = $this->invoiceService->exportWarehousingStatisticV3($request);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getInvoiceDetail($type, $code, Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceDetail');

        $invoiceId = $type == 'id' ? $code : null;
        $invoiceCode = $type == 'code' ? $code : null;
        $data = DB::select('select v3.f_invoice_detail(?) as result', [Utils::getParams($request->input(), array('invoice_id' => $invoiceId, 'invoice_code' => $invoiceCode))]);
        if (sizeof($data) > 0) {
            $rs = json_decode($data[0]->result);
            if (isset($rs->invoice)) {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $rs);
            }
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    /**
     * api v3
     * from getInvoiceDetail
     */
    public function getInvoiceDetailV3($type, $code, Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceDetailV3');

        $invoiceId = $type == 'id' ? $code : null;
        $invoiceCode = $type == 'code' ? $code : null;
        $data = $this->invoiceService
            ->invoiceDetailV3([
                'invoice_id' => $invoiceId,
                'invoice_code' => $invoiceCode,
                'request' => $request
            ]);

        if (sizeof($data) > 0) {
            $rs = $data;
            if (isset($rs['invoice'])) {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $rs);
            }
        }

        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    public function getInvoiceDetailV3New($type, $code, Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceDetailV3');

        $invoiceId = $type == 'id' ? $code : null;
        $invoiceCode = $type == 'code' ? $code : null;
        $data = $this->invoiceService
            ->invoiceDetailV3New([
                'invoice_id' => $invoiceId,
                'invoice_code' => $invoiceCode,
                'request' => $request
            ]);

        if (sizeof($data) > 0) {
            $rs = $data;
            if (isset($rs['invoice'])) {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $rs);
            }
        }

        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    public function deleteInvoiceSell($id, Request $request)
    {
        LogEx::methodName($this->className, 'deleteInvoiceSell');

        $user = $request->userInfo;
        $data = $this->invoice->getDetailById($id);
        if (isset($data)) {
            if ($data['invoice']->status == 'temp' && $data['invoice']->drug_store_id == $user->drug_store_id) {
                $this->invoice->deleteOneById($id);
                $this->invoiceDetail->deleteManyBy('invoice_id', $id);
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
            }
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    public function getInvoiceDetailShort($type, $code, Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceDetailShort');

        $invoiceId = $type == 'id' ? $code : null;
        $invoiceCode = $type == 'code' ? $code : null;
        $data = DB::select('select v3.f_invoice_detail_short(?) as result', [Utils::getParams($request->input(), array('invoice_id' => $invoiceId, 'invoice_code' => $invoiceCode))]);
        if (sizeof($data) > 0) {
            $rs = json_decode($data[0]->result);
            if (isset($rs->invoice)) {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $rs);
            }
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    /**
     * api v3
     * from getInvoiceDetailShort
    */
    public function getInvoiceDetailShortV3($type, $code, Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceDetailShortV3');

        $invoiceId = $type == 'id' ? $code : null;
        $invoiceCode = $type == 'code' ? $code : null;
        $data = $this->invoiceService
            ->invoiceDetailShortV3([
                'invoice_id' => $invoiceId,
                'invoice_code' => $invoiceCode
            ]);

        if (sizeof($data) > 0) {
            $rs = $data;
            if (isset($rs['invoice'])) {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $rs);
            }
        }

        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    public function saveInvoice(Request $request)
    {
        LogEx::methodName($this->className, 'saveInvoice');
        $requestInput = $request->input();
        $user = $request->userInfo;
        $drugStore = $this->drugStore->findOneById($user->drug_store_id);
        try {
            if ($requestInput["invoice_type"] && $requestInput["invoice_type"] === 'IV1' && $drugStore->type === "GDP" && isset($requestInput["promotion_ids"]) && sizeOf($requestInput["promotion_ids"]) > 0) {
                if ($this->promotionService->validatePromotionByInvoiceOrOrder($user->drug_store_id, $requestInput) === false) {
                    return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, "Chương trình khuyến mại áp dụng không chính xác");
                };
            }
            $data = Utils::executeRawQuery('select * from v3.f_invoice_save(?) as result', [Utils::getParams($requestInput)]);
            // Add cashbook
            $dataInvoice = json_decode($data[0]->result);
            // Add promotion logs
            if ($requestInput["invoice_type"] === 'IV1' && $drugStore->type === "GDP" && isset($requestInput["promotion_ids"]) && sizeof($requestInput["promotion_ids"]) > 0) {
                $this->promotionLogsService->createPromotionLogByInvoice($requestInput, $dataInvoice->invoice->id, $user->drug_store_id);
            }

            if (isset($dataInvoice) && $dataInvoice->invoice->status === 'done' && $dataInvoice->invoice->pay_amount > 0) {
                $modelCashbook = $this->cashbookService->createModelCashbook($dataInvoice->invoice->pay_amount, $dataInvoice->invoice, $user, true);
                $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
            }
            if (isset($dataInvoice) && isset($drugStore) && $drugStore->type === "GDP" && $dataInvoice->invoice->status !== 'temp') {
                $modelInvoiceWarehouse = $this->invoiceWarehouseService->createModelByInvoice($dataInvoice->invoice, $user);
                if ($modelInvoiceWarehouse) {
                    $dataInvoiceWarehouse = $this->invoiceWarehouseService->createInvoiceWarehouse($modelInvoiceWarehouse);
                    if (isset($dataInvoiceWarehouse)) {
                        $this->invoiceDetail->updateManyBy('invoice_id', $dataInvoice->invoice->id, ['warehouse_invoice_id' => $dataInvoiceWarehouse->id]);
                    }
                }
            }
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, json_decode($data[0]->result));
    }

    /**
     * api v3
     * from saveInvoice
    */
    public function saveInvoiceV3(Request $request)
    {
        LogEx::methodName($this->className, 'saveInvoiceV3 ');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $drugStore = $this->drugStore->findOneById($user->drug_store_id);

        $imgBase64 = $requestInput['image'] ?? '';
        if (!empty($imgBase64)) {
            $img = $this->generateImageFromBase64($imgBase64);
            $requestInput['image'] = url("upload/images/" . $img);
        }

        $resultId = $this->invoiceService->createInvoice($requestInput, $user, $drugStore);

        // Thông báo lỗi TH bán quá
        if ($resultId == -1) {
            $msg = "Đã có thuốc bán quá số lượng trong kho. Vui lòng kiểm tra lại";
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $msg);
        }

        // Thông báo lỗi TH bán quá
        if ($resultId == -2) {
            $msg = "Vui lòng nhập số lượng lớn hơn 0";//"Đã có thuốc bán với số lượng không phải số nguyên lớn hơn không. Vui lòng kiểm tra lại";
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $msg);
        }

        if ($resultId == -10) {
            $msg = "Số lượng lớn hơn 0";
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $msg);
        }

        if ($resultId != false) {
            $result = $this->invoiceService
                ->invoiceDetailV3([
                    'invoice_id' => $resultId,
                    'request' => $request
                ]);

            if (!empty($requestInput['invoice_id'])) {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
            } else {
                try {
                    if ($requestInput["invoice_type"] &&
                        $requestInput["invoice_type"] === 'IV1' &&
                        $drugStore->type === "GDP" &&
                        isset($requestInput["promotion_ids"]) &&
                        sizeOf($requestInput["promotion_ids"]) > 0)
                    {
                        if ($this->promotionService->validatePromotionByInvoiceOrOrder($user->drug_store_id, $requestInput) === false) {
                            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, "Chương trình khuyến mại áp dụng không chính xác");
                        }
                    }

                    $dataInvoice = $result;
                    // Add promotion logs
                    if ($requestInput["invoice_type"] === 'IV1' && $drugStore->type === "GDP" && isset($requestInput["promotion_ids"]) && sizeof($requestInput["promotion_ids"]) > 0) {
                        $this->promotionLogsService->createPromotionLogByInvoice($requestInput, $dataInvoice['invoice']->id, $user->drug_store_id);
                    }

                    if (isset($dataInvoice) && $dataInvoice['invoice']->status === 'done' && $dataInvoice['invoice']->pay_amount > 0) {
                        $modelCashbook = $this->cashbookService->createModelCashbook($dataInvoice['invoice']->pay_amount, $dataInvoice['invoice'], $user, true);
                        $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
                    }
                    if (isset($dataInvoice) && isset($drugStore) && $drugStore->type === "GDP" && $dataInvoice['invoice']->status !== 'temp') {
                        $modelInvoiceWarehouse = $this->invoiceWarehouseService->createModelByInvoice($dataInvoice['invoice'], $user);
                        if ($modelInvoiceWarehouse) {
                            $dataInvoiceWarehouse = $this->invoiceWarehouseService->createInvoiceWarehouse($modelInvoiceWarehouse);
                            if (isset($dataInvoiceWarehouse)) {
                                $this->invoiceDetail->updateManyBy('invoice_id', $dataInvoice['invoice']->id, ['warehouse_invoice_id' => $dataInvoiceWarehouse->id]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    LogEx::info($e->getMessage());
                    return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
                }

                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
            }
        }

        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function updateStatus(Request $request)
    {
        LogEx::methodName($this->className, 'updateStatus');
        $requestInput = $request->input();

        try {
            $data = Utils::executeRawQuery('select * from v3.f_invoice_update_status(?) as result', [Utils::getParams($requestInput)]);
            $dataInvoiceWarehouse = $this->invoiceWarehouse->findOneBy("invoice_id", $requestInput["id"]);
            if ($dataInvoiceWarehouse) {
                $this->invoiceWarehouse->updateOneById($dataInvoiceWarehouse->id, ['status' => $requestInput['status']]);
            }
            if ($requestInput['status'] === "cancel") {
                $this->cashbookService->updateStatusByInvoiceId($requestInput["id"], "cancel");
            }
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, json_decode($data[0]->result));
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /**
     * api v3
     * from updateStatus
    */
    public function updateStatusV3(Request $request)
    {
        LogEx::methodName($this->className, 'updateStatus');
        $requestInput = $request->input();

        try {
            $data = $this->invoiceService->invoiceUpdateStatusV3($requestInput);
            if (is_string($data)) {
                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, $data);
            }
            $data = $this->invoiceService
                ->invoiceDetailV3([
                    'invoice_id' => $data['id'],
                    'request' => $request
                ]);
            $dataInvoiceWarehouse = $this->invoiceWarehouse->findOneBy("invoice_id", $requestInput["id"]);
            if ($dataInvoiceWarehouse) {
                $this->invoiceWarehouse->updateOneById($dataInvoiceWarehouse->id, ['status' => $requestInput['status']]);
            }
            if ($requestInput['status'] === "cancel") {
                $this->cashbookService->updateStatusByInvoiceId($requestInput["id"], "cancel");
            }
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
        }

        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function updateStatusShipping($id, Request $request)
    {
        LogEx::methodName($this->className, 'updateStatusShipping');
        $requestInput = $request->input();

        $user = $request->userInfo;
        $data = DB::select('select v3.f_invoice_detail(?) as result', [Utils::getParams($request->input(), array('invoice_id' => $id))])[0]->result;
        $data = json_decode($data);

        if (isset($data) && $requestInput['shipping_status']) {
            $dataInvoice = $data->invoice;
            if ($dataInvoice->status == 'processing' && $dataInvoice->drug_store_id == $user->drug_store_id) {
                if ($dataInvoice->payment_status == 'paid' || $dataInvoice->payment_status == 'partial_paid') {
                    $this->invoice->updateOneById($id, ['shipping_status' => $requestInput['shipping_status'], 'status' => $requestInput['shipping_status'] == 'done' ? 'done' : $dataInvoice->status]);
                    if ($requestInput['shipping_status'] == 'done') {
                        // Add cashbook
                        $modelCashbook = $this->cashbookService->createModelCashbook($dataInvoice->pay_amount, $dataInvoice, $user, true);
                        $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
                        // Update status invoice warehouse
                        $dataInvoiceWarehouse = $this->invoiceWarehouse->findOneBy("invoice_id", $dataInvoice->id);
                        if ($dataInvoiceWarehouse) {
                            $this->invoiceWarehouse->updateOneById($dataInvoiceWarehouse->id, ['status' => 'done']);
                        }
                    }
                } else {
                    $this->invoice->updateOneById($id, ['shipping_status' => $requestInput['shipping_status']]);
                }
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
            }
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    /**
     * api v3
     * from updateStatusShipping
    */
    public function updateStatusShippingV3($id, Request $request)
    {
        LogEx::methodName($this->className, 'updateStatusShippingV3');
        $requestInput = $request->input();

        $user = $request->userInfo;
        $data = $this->invoiceService
            ->invoiceDetailV3([
                'invoice_id' => $id,
                'request' => $request
            ]);

        if (isset($data['invoice']) && $requestInput['shipping_status']) {
            $dataInvoice = $data['invoice'];
            if ($dataInvoice->status == 'processing' && $dataInvoice->drug_store_id == $user->drug_store_id) {
                if ($dataInvoice->payment_status == 'paid' || $dataInvoice->payment_status == 'partial_paid') {
                    $this->invoice->updateOneById(
                        $id,
                        [
                            'shipping_status' => $requestInput['shipping_status'],
                            'status' => $requestInput['shipping_status'] == 'done' ? 'done' : $dataInvoice->status
                        ]
                    );
                    if ($requestInput['shipping_status'] == 'done') {
                        // Add cashbook
                        $modelCashbook = $this->cashbookService->createModelCashbook($dataInvoice->pay_amount, $dataInvoice, $user, true);
                        $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
                        // Update status invoice warehouse
                        $dataInvoiceWarehouse = $this->invoiceWarehouse->findOneBy("invoice_id", $dataInvoice->id);
                        if ($dataInvoiceWarehouse) {
                            $this->invoiceWarehouse->updateOneById($dataInvoiceWarehouse->id, ['status' => 'done']);
                        }
                    }
                } else {
                    $this->invoice->updateOneById($id, ['shipping_status' => $requestInput['shipping_status']]);
                }
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
            }
        }

        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    public function paymentDebt($id, Request $request)
    {
        LogEx::methodName($this->className, 'updateStatusShipping');

        $requestInput = $request->input();
        $payAmount = $requestInput['pay_amount'];
        $user = $request->userInfo;
        $drugStore = $this->drugStore->findOneById($user->drug_store_id);
        $data = DB::select('select v3.f_invoice_detail(?) as result', [Utils::getParams($request->input(), array('invoice_id' => $id))])[0]->result;
        $data = json_decode($data);

        if (isset($data) && isset($payAmount) && $payAmount > 0) {
            $dataInvoice = $data->invoice;
            $payAmountFinal = $payAmount + $dataInvoice->pay_amount;
            if ($dataInvoice->status != 'temp' && $dataInvoice->drug_store_id == $user->drug_store_id) {
                if ($dataInvoice->invoice_type === 'IV1' && $drugStore->type === "GDP") {
                    if ($dataInvoice->shipping_status == 'done') {
                        $this->invoice->updateOneById($id, [
                            'pay_amount' => $payAmountFinal,
                            'status' => 'done',
                            'payment_status' => $payAmountFinal == $dataInvoice->amount + $dataInvoice->vat_amount - ($dataInvoice->discount_promotion ?? 0) ? 'paid' : 'partial_paid'
                        ]);
                        $modelCashbook = $this->cashbookService->createModelCashbook($payAmount, $dataInvoice, $user, true);
                        $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);

                        $dataInvoiceWarehouse = $this->invoiceWarehouse->findOneBy("invoice_id", $id);
                        if ($dataInvoiceWarehouse) {
                            $this->invoiceWarehouse->updateOneById($dataInvoiceWarehouse->id, ['status' => 'done']);
                        }
                    } else {
                        $this->invoice->updateOneById($id, [
                            'pay_amount' => $payAmountFinal,
                            'payment_status' => $payAmountFinal == $dataInvoice->amount + $dataInvoice->vat_amount - ($dataInvoice->discount_promotion ?? 0) ? 'paid' : 'partial_paid'
                        ]);
                    }
                } else {
                    $this->invoice->updateOneById($id, [
                        'pay_amount' => $payAmountFinal,
                        'payment_status' => $payAmountFinal == $dataInvoice->amount + $dataInvoice->vat_amount - ($dataInvoice->discount_promotion ?? 0) ? 'paid' : 'partial_paid'
                    ]);
                    $modelCashbook = $this->cashbookService->createModelCashbook($payAmount, $dataInvoice, $user, true);
                    $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
                }
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
            }
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /**
     * api v3
     * from paymentDebt
    */
    public function paymentDebtV3($id, Request $request)
    {
        LogEx::methodName($this->className, 'paymentDebtV3');

        $requestInput = $request->input();
        $payAmount = $requestInput['pay_amount'];
        $user = $request->userInfo;
        $drugStore = $this->drugStore->findOneById($user->drug_store_id);
        $data = $this->invoiceService
            ->invoiceDetailV3([
                'invoice_id' => $id,
                'request' => $request
            ]);

        if (isset($data['invoice']) && isset($payAmount) && $payAmount > 0) {
            $dataInvoice = $data['invoice'];
            $payAmountFinal = $payAmount + $dataInvoice->pay_amount;
            if ($dataInvoice->status != 'temp' && $dataInvoice->drug_store_id == $user->drug_store_id) {
                if ($dataInvoice->invoice_type === 'IV1' && $drugStore->type === "GDP") {
                    if ($dataInvoice->shipping_status == 'done') {
                        $this->invoice->updateOneById($id, [
                            'pay_amount' => $payAmountFinal,
                            'status' => 'done',
                            'payment_status' => $payAmountFinal == $dataInvoice->amount + $dataInvoice->vat_amount - ($dataInvoice->discount_promotion ?? 0) ? 'paid' : 'partial_paid'
                        ]);
                        $modelCashbook = $this->cashbookService->createModelCashbook($payAmount, $dataInvoice, $user, true);
                        $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);

                        $dataInvoiceWarehouse = $this->invoiceWarehouse->findOneBy("invoice_id", $id);
                        if ($dataInvoiceWarehouse) {
                            $this->invoiceWarehouse->updateOneById($dataInvoiceWarehouse->id, ['status' => 'done']);
                        }
                    } else {
                        $this->invoice->updateOneById($id, [
                            'pay_amount' => $payAmountFinal,
                            'payment_status' => $payAmountFinal == $dataInvoice->amount + $dataInvoice->vat_amount - ($dataInvoice->discount_promotion ?? 0) ? 'paid' : 'partial_paid'
                        ]);
                    }
                } else {
                    $this->invoice->updateOneById($id, [
                        'pay_amount' => $payAmountFinal,
                        'payment_status' => $payAmountFinal == $dataInvoice->amount + $dataInvoice->vat_amount - ($dataInvoice->discount_promotion ?? 0) ? 'paid' : 'partial_paid'
                    ]);
                    $modelCashbook = $this->cashbookService->createModelCashbook($payAmount, $dataInvoice, $user, true);
                    $this->cashbookService->createCashbook($modelCashbook, $user->drug_store_id);
                }
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
            }
        }

        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /**
     * Kiểm tra import hóa đơn để liên thông Dược Quốc Gia
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkImportInvoice(Request $request)
    {
        LogEx::methodName($this->className, 'checkImportInvoice');
        $requestInput = $request->input();
        try {
            $data = Utils::executeRawQuery('select * from v3.f_invoice_check_import(?)', [Utils::getParams($requestInput)]);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from checkImportInvoice
    */
    public function checkImportInvoiceV3(Request $request)
    {
        LogEx::methodName($this->className, 'checkImportInvoiceV3');

        $file = $request->file;
        $drug_store_id = $request->userInfo->drug_store_id;

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->setShouldFormatDates(true);
        $reader->open($file);

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getIndex() === 0) {
                foreach ($sheet->getRowIterator() as $key => $row) {
                    if ($key > 3) {
                        $invoice_code = $row->toArray()[0];//*
                        $receipt_date = Carbon::parse($row->toArray()[1])->format('Y-m-d');//*
                        $amount = $row->toArray()[2];//*
                        $vat_amount = $row->toArray()[3];
                        $discount = $row->toArray()[4];
                        $pay_amount = $row->toArray()[5];//*
                        $sale_name = $row->toArray()[6];//*
                        $customer_name = $row->toArray()[7];
                        $invoice_details = $row->toArray()[8];//*

                        if (!$invoice_code)
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập số mã hóa đơn');

                        if (!$receipt_date)
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập số ngày bán');

                        if (!$amount)
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập tổng tiền');

                        if (!$pay_amount)
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập thanh toán');

                        if (!$sale_name)
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập tên người bán');

                        $invoice_codes [] = $invoice_code;
                        $invoice_detailList [] = json_decode($invoice_details);

                        if (!(json_decode($invoice_details)))
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập chi tiết hóa đơn');

                        foreach (json_decode($invoice_details) as $item) {
                            if (!$item->drug_name)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập tên thuốc');

                            if (!$item->number)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập số lô');

                            if (!$item->expiry_date)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập hạn dùng');

                            if (!$item->unit_name)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR,
                                            'Cần nhập đơn vị tính');
                            $drug_names [] = $item->unit_name;

                            if ($item->quantity < 0)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Số lượng cần phải dương');

                            if ($item->cost < 0)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Đơn giá cần phải dương');
                        }

                        $data[] = [
                            "invoice_code" => "$invoice_code",
                            "receipt_date" => $receipt_date,
                            "amount" => $amount,
                            "customer_name" => $customer_name,
                            "discount" => $discount,
                            "drug_store_id" => $drug_store_id,
                            "pay_amount" => $pay_amount,
                            "sale_name" => $sale_name,
                            "vat_amount" => $vat_amount,
                            "invoice_details" => json_decode($invoice_details)
                        ];
                    }
                }
                break;
            }
        }

        $reader->close();

        if (!(Unit::whereIn('name', $drug_names)->get()))
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR,'Đơn vị tính không tồn tại');

        if (Invoice::whereIn('invoice_code', $invoice_codes)->get()->count())
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Mã hóa đơn đã tồn tại');

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * Xử lý thông tin import hóa đơn được import bằng phương pháp đọc excel từ phía client => truyền mảng data xuống api
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importInvoice(Request $request)
    {
        LogEx::methodName($this->className, 'importInvoice');
        $requestInput = $request->input();
        try {
            $data = Utils::executeRawQuery('select * from v3.f_invoice_import(?) as result', [Utils::getParams($requestInput)]);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, json_decode($data[0]->result));
    }

    /**
     * Upload file hóa đơn định dạng xml
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadXml(Request $request)
    {
        try {
            $file = $request->file("xmlFile");

            if ($file == null) {
                $file = $request->file("zipFile");
            }

            $userInfo = $request->userInfo;

            $xmlString = file_get_contents($file);
            $check = $file->getClientOriginalName();
            $type = substr($check, strrpos($check, "."));
            $type = strtolower($type);
            $successFilesName = [];
            $failFilesName = [];
            $totalFilesName = [];
            if (in_array($type, ['.xml', '.zip'])) {
                if ($type == '.zip') {
                    $fileBasePath = env('UPLOAD_ZIP_FILE', '');

                    $zip = new \ZipArchive();
                    $zipStatus = $zip->open($file->path());

                    if ($zipStatus == true) {
                        $zip->extractTo($fileBasePath . $userInfo->username);

                        $countType = 0;
                        $countSuccess = 0;
                        $countError = 0;
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $fileName = $zip->getNameIndex($i);

                            $type = substr($fileName, strpos($fileName, "."));
                            $type = strtolower($type);

                            if ($type != '.xml') {
                                continue;
                            }
                            $countType++;

                            $fileXmlObjects = simplexml_load_string($zip->getFromName($fileName));
                            $nsArrays = $fileXmlObjects->getNamespaces(true);

                            $ns = '';
                            foreach ($nsArrays as $key => $namespace) {
                                $ns = $namespace;
                                break;
                            }

                            $sentResults = $this->parserXmlFormat($zip->getFromName($fileName), $ns, $userInfo);

                            if ($sentResults['ERR_CODE']) {
                                $countSuccess++;
                                $successFilesName[] = $sentResults;
                                continue;
                            }
                            $countError++;
                            $failFilesName[] = $sentResults;
                        }
                        $totalFilesName = array_merge($failFilesName, $successFilesName);
                        if ($countType === 0) {
                            $response = $this->responseApi(CommonConstant::INTERNAL_SERVER_ERROR, "Không có file nào đúng định dạng", null);

                            return response()->json($response);
                        }
                        $response = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, array(
                            "count_success" => $countSuccess,
                            "count_error" => $countError,
                            "totalFiles" => $totalFilesName,
                        ));

                        return response()->json($response);
                    }
                } else {
                    $xmlObject = simplexml_load_string($xmlString);
                    $nsArray = $xmlObject->getNamespaces(true);

                    $ns = '';
                    foreach ($nsArray as $key => $namespace) {
                        $ns = $namespace;
                        break;
                    }

                    $result = $this->parserXmlFormat($xmlString, $ns, $userInfo);
                    if ($result['ERR_CODE']) {
                        return response()->json([
                            'ERR_CODE' => '200',
                            'ERR_MSG' => 'Thành công',
                            'RESULT' => $result['RESULT']
                        ]);
                    }

                    return \App\Helper::errorResponse(
                        CommonConstant::INTERNAL_SERVER_ERROR,
                        $result['ERR_MSG'] ?? CommonConstant::MSG_ERROR
                    );
                }
            }
        } catch (\Exception $e) {
            LogEx::error($e->getMessage());
        }
    }

    private function parserXmlFormat($xmlString, $ns, $userInfo)
    {
        $xmlObject = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS, $ns);
        if (empty($xmlObject)) {
            $xmlObject = simplexml_load_string($xmlString);
        }
        $json = json_encode($xmlObject);
        $phpArray = json_decode($json, true);
        $phpArray = $phpArray['invoiceData'] ?? $phpArray['DLHDon'];
        $receiptDate = $phpArray['signedDate'] ?? $phpArray['invoiceSignedDate'] ?? $phpArray['TTChung']['NLap'];
        $receiptDate = str_replace('/', '-', $receiptDate);
        $receiptDate = date('Y-m-d', strtotime($receiptDate));

        DB::beginTransaction();
        try {
            // 1. Lấy ra thông tin của invoice
            $invoice = new Invoice();
            $invoice['drug_store_id'] = $userInfo->drug_store_id;
            $invoice['invoice_code'] = isset($phpArray['invoiceSeries']) ?
                $phpArray['invoiceSeries'] . $phpArray['invoiceNumber'] :
                $phpArray['TTChung']['KHHDon'];
            $invoice['invoice_type'] = 'IV1';
            $invoice['warehouse_action_id'] = 'Bán hàng cho khách';
            $invoice['customer_id'] = 0; //Không cần xác định bán cho khách hàng nào
            $invoice['amount'] = isset($phpArray['totalAmountWithoutVAT']) ?
                (int)$phpArray['totalAmountWithoutVAT'] :
                (int)$phpArray['NDHDon']['TToan']['TgTCThue'];
            $invoice['vat_amount'] = isset($phpArray['totalVATAmount']) ?
                (int)$phpArray['totalVATAmount'] :
                (int)$phpArray['NDHDon']['TToan']['TgTThue'];
            $invoice['pay_amount'] = isset($phpArray['totalAmountWithVAT']) ?
                (int)$phpArray['totalAmountWithVAT'] :
                (int)$phpArray['NDHDon']['TToan']['TgTTTBSo'];
            $invoice['discount'] = isset($phpArray['NDHDon']['TToan']['THTTLTSuat']['LTSuat']['TSuat']) ?
                (int)$phpArray['NDHDon']['TToan']['THTTLTSuat']['LTSuat']['TSuat'] :
                ((isset($phpArray['discountAmount']) ? (int)$phpArray['discountAmount'] : 0));
            $invoice['created_by'] = $userInfo->id;
            $invoice['status'] = 'done';
            $invoice['receipt_date'] = $receiptDate;
            $invoice['customer_excel'] = isset($phpArray['buyerLegalName']) ?
                $phpArray['buyerLegalName'] :
                (isset($phpArray['buyer']['buyerLegalName']) ? $phpArray['buyer']['buyerLegalName'] : null);

            if ($invoice['pay_amount'] > 20000000) {
                $invoice['method'] = 'online';
                $invoice['payment_method'] = 'banking';
            } else {
                $invoice['method'] = 'direct';
                $invoice['payment_method'] = 'cash';
            }
            $invoice['source'] = 'gdp';
            $invoice['is_import'] = true;
            $invoice->save();

            $invoiceId = $invoice->id;
            $expiryDate = Utils::addMonths($receiptDate, 24);

            // Chỉ có 1 phần tử
            if (isset($phpArray['NDHDon']['DSHHDVu']['HHDVu']['THHDVu']) ??
                isset($phpArray['items']['item']['itemName']))
            {
                $phpArray['NDHDon']['DSHHDVu']['HHDVu']['THHDVu'] ??
                $phpArray['items']['item'] = [0 => $phpArray['NDHDon']['DSHHDVu']['HHDVu']['THHDVu'] ??
                $phpArray['items']['item']];
            }

            // 2. Lấy ra thông tin của invoice_detail
            foreach ($phpArray['items']['item'] ?? $phpArray['NDHDon']['DSHHDVu']['HHDVu'] as $item) {
//                $drugInfo = DB::select(
//                    'select sphacy_v1_new.f_drug_from_invoice(?, ?, ?) as result',
//                    [$invoice['drug_store_id'],
//                    preg_replace('!\s+!', ' ', $item['itemName'] ?? $item['THHDVu']),
//                    $item['unitName'] ?? $item['DVTinh']]
//                )[0]->result;
//
//                $drugInfo = json_decode($drugInfo);dd($drugInfo);

                $drugStore = $invoice['drug_store_id'];
                $drugName = $item['itemName'] ?? $item['THHDVu'];
                $unitName = $item['unitName'] ?? $item['DVTinh'];

                $drugInfo = $this->drugFromInvoiceV3(
                    $drugStore,
                    $drugName,
                    $unitName
                );

                //if ($drugInfo->drug_id == 0 || $drugInfo->unit_id == 0) {
                if ($drugInfo['drug_id'] == 0 || $drugInfo['unit_id'] == 0) {
                    $message = '';
                    //if ($drugInfo->drug_id == 0) {
                    if ($drugInfo['drug_id'] == 0) {
                        $message = "Tên thuốc không tồn tại: " . ($item['itemName'] ?? $item['THHDVu']);
                        // đoạn này dùng để loại bỏ khoảng trắng thừa giữa các từ : preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $item['THHDVu'])
                    }

                    //if ($drugInfo->unit_id == 0) {
                    if ($drugInfo['unit_id'] == 0) {
                        $message = "Tên đơn vị không tồn tại: " . ($item['unitName'] ?? $item['DVTinh']);
                    }

                    LogEx::try_catch($this->className, $message);
                    DB::rollback();
                    return [
                        'ERR_CODE' => false,
                        'ERR_MSG' => $message,
                        'RESULT' => $invoice
                    ];
                }

                $invoiceDetail = new InvoiceDetail();
                $invoiceDetail['invoice_id'] = $invoiceId; //Thêm mới invoice ở trên thì ra invoice_id
                $invoiceDetail['drug_id'] = $drugInfo['drug_id'] ?: 0;//$drugInfo->drug_id ?: 0; //Từ tên sang drug_id
                $invoiceDetail['unit_id'] = $drugInfo['unit_id'] ?: 0;//$drugInfo->unit_id ?: 0; //Từ tên sang unit_id
                $invoiceDetail['number'] = 'NONE'; //Số lô (chưa có thông tin từ invoice)
                $invoiceDetail['expiry_date'] = date('Y-m-d H:i:s', $expiryDate); //Hạn dùng (chưa có thông tin từ invoice)
                $invoiceDetail['usage'] = ''; //Liều dùng (không quan trọng)
                $invoiceDetail['warehouse_id'] = null;
                $invoiceDetail['quantity'] = isset($item['quantity']) ? (int)$item['quantity'] : (int)$item['SLuong'];
                $invoiceDetail['vat'] = isset($item['vatPercentage']) ? (int)$item['vatPercentage'] : (int)$item['TSuat'];
                $invoiceDetail['exchange'] = $drugInfo['exchange'] ?: 1;//$drugInfo->exchange ?: 1;
                $invoiceDetail['cost'] = isset($item['unitPrice']) ? (int)$item['unitPrice'] : (int)$item['DGia']; //Giá bán
                if ($invoiceDetail['cost'] <= 0) {
                    $invoiceDetail['cost'] = 1;
                }
                $invoiceDetail->save();
            }

            DB::commit();
            return [
                'ERR_CODE' => true,
                'RESULT' => $invoice
            ];
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e);
            DB::rollback();
            return [
                'ERR_CODE' => false,
                'ERR_MSG' => $e
            ];
        }
    }

    /**
     * api v3
     * from sphacy_v1_new.f_drug_from_invoice
     */
    public function drugFromInvoiceV3($drugStoreId , $drugName, $unitName)
    {
        LogEx::methodName($this->className, 'drugFromInvoiceV3');

        $drugID = DB::table("drug")
            ->select("id")
            ->where("drug_store_id", "=", $drugStoreId)
            ->where(
                (DB::raw('lower(vn_unaccent(name))')),
                'ILIKE',
                '%' . strtolower(Utils::unaccent($drugName)) . '%')
            ->limit(1)
            ->first();

        $unitID = DB::table("unit")
            ->select("id")
            ->where(
                (DB::raw('lower(vn_unaccent(name))')),
                'ILIKE',
                '%' . strtolower(Utils::unaccent($unitName)) . '%')
            ->limit(1)
            ->first();

        if ($drugID && $unitID) {
            $exchange = DB::table("warehouse")
                ->select("exchange")
                ->where("drug_store_id", "=", $drugStoreId)
                ->where("drug_id", "=", $drugID->id)
                ->where("unit_id", "=", $unitID->id)
                ->limit(1)
                ->first();

            return [
                "drug_id" => $drugID->id,
                "unit_id" => $unitID->id,
                "exchange" => ($exchange) ? $exchange->exchange : 0
            ];
        }

        return [
            "drug_id" => !empty($drugID->id) ? $drugID->id : 0,
            "unit_id" => !empty($unitID->id) ? $unitID->id : 0,
            "exchange" => !empty($drugID->exchange) ? $drugID->exchange : 0,
        ];
    }

    /**
     * Tạo url chuyển hướng thanh toán
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentInvoice(Request $request)
    {
        LogEx::methodName($this->className, 'paymentInvoice');
        $requestInput = $request->input();
        $user = $request->userInfo;
        $drugStore = $this->drugStore->findOneById($user->drug_store_id);
        $vnp_TxnRef = $requestInput['invoice_code']; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        $invoice = $this->invoice->findOneBy("invoice_code", $vnp_TxnRef);
        if (isset($invoice)) {
            $vnp_TmnCode = env('VNP_TMNCODE', '');
            if ($invoice->invoice_type === 'IV1') {
                if (isset($drugStore->vnpay_code)) {
                    $vnp_TmnCode = $drugStore->vnpay_code;
                } else {
                    $resp = $this->responseApi(CommonConstant::INTERNAL_SERVER_ERROR, "Bạn chưa cấu hình mã VNPay", null);
                    return response()->json($resp);
                }
            }
            $vnp_HashSecret = env('VNP_HASHSECRET', '');
            $vnp_Url = env('VNP_URL', '');
            if ($invoice->invoice_type === 'IV1') {
                $vnp_Returnurl = env('VNP_RETURNURL_IV1', '');
            } else {
                $vnp_Returnurl = env('VNP_RETURNURL_IV2', '');
            }
            $vnp_Returnurl = str_replace(':code', $vnp_TxnRef, $vnp_Returnurl);
            $vnp_Returnurl = str_replace(':type', strtolower($drugStore->type), $vnp_Returnurl);

            //Config input format
            //Expire
            $startTime = date("YmdHis");
            $expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));
            $vnp_OrderInfo = $requestInput['order_desc'];
            $vnp_Amount = ($invoice->amount + $invoice->vat_amount - $invoice->discount - $invoice->pay_amount - ($invoice->discount_promotion ?? 0)) * 100;
            $vnp_BankCode = $requestInput['bank_code'];
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

            //Billing
            $vnp_Bill_Mobile = $requestInput['txt_billing_mobile'];
            $vnp_Bill_Email = $requestInput['txt_billing_email'];
            $fullName = trim($requestInput['txt_billing_fullname']);

            if (isset($fullName) && trim($fullName) != '') {
                $name = explode(' ', $fullName);
                $vnp_Bill_FirstName = array_shift($name);
                $vnp_Bill_LastName = array_pop($name);
            }
            $vnp_Bill_Address = $requestInput['txt_inv_addr1'];
            $vnp_Bill_City = $requestInput['txt_bill_city'];
            $vnp_Bill_Country = $requestInput['txt_bill_country'];
            $vnp_Bill_State = $requestInput['txt_bill_state'];

            // Invoice
            $vnp_Inv_Phone = $requestInput['txt_inv_mobile'];
            $vnp_Inv_Email = $requestInput['txt_inv_email'];
            $vnp_Inv_Customer = $requestInput['txt_inv_customer'];
            $vnp_Inv_Address = $requestInput['txt_inv_addr1'];
            $vnp_Inv_Company = $requestInput['txt_inv_company'];
            $vnp_Inv_TaxCode = $requestInput['txt_inv_taxcode'];
            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => 'vn',
                "vnp_OrderInfo" => Utils::removeSymbolAndUnaccent($vnp_OrderInfo),
                "vnp_OrderType" => "270000",
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
                "vnp_ExpireDate" => $expire,
                "vnp_Inv_Type" => "O"
            );
            if (!empty($vnp_Bill_Mobile)) {
                $inputData['vnp_Bill_Mobile'] = Utils::removeSymbolAndUnaccent($vnp_Bill_Mobile);
            }
            if (!empty($vnp_Bill_Email)) {
                $inputData['vnp_Bill_Email'] = Utils::removeSymbolAndUnaccent($vnp_Bill_Email);
            }
            if (!empty($vnp_Bill_FirstName)) {
                $inputData['vnp_Bill_FirstName'] = Utils::removeSymbolAndUnaccent($vnp_Bill_FirstName);
            }
            if (!empty($vnp_Bill_LastName)) {
                $inputData['vnp_Bill_LastName'] = Utils::removeSymbolAndUnaccent($vnp_Bill_LastName);
            }
            if (!empty($vnp_Bill_Address)) {
                $inputData['vnp_Bill_Address'] = Utils::removeSymbolAndUnaccent($vnp_Bill_Address);
            }
            if (!empty($vnp_Bill_City)) {
                $inputData['vnp_Bill_City'] = Utils::removeSymbolAndUnaccent($vnp_Bill_City);
            }
            if (!empty($vnp_Bill_Country)) {
                $inputData['vnp_Bill_Country'] = Utils::removeSymbolAndUnaccent($vnp_Bill_Country);
            }
            if (!empty($vnp_Inv_Phone)) {
                $inputData['vnp_Inv_Phone'] = Utils::removeSymbolAndUnaccent($vnp_Inv_Phone);
            }
            if (!empty($vnp_Inv_Email)) {
                $inputData['vnp_Inv_Email'] = Utils::removeSymbolAndUnaccent($vnp_Inv_Email);
            }
            if (!empty($vnp_Inv_Customer)) {
                $inputData['vnp_Inv_Customer'] = Utils::removeSymbolAndUnaccent($vnp_Inv_Customer);
            }
            if (!empty($vnp_Inv_Address)) {
                $inputData['vnp_Inv_Address'] = Utils::removeSymbolAndUnaccent($vnp_Inv_Address);
            }
            if (!empty($vnp_Inv_Company)) {
                $inputData['vnp_Inv_Company'] = Utils::removeSymbolAndUnaccent($vnp_Inv_Company);
            }
            if (!empty($vnp_Inv_TaxCode)) {
                $inputData['vnp_Inv_Taxcode'] = Utils::removeSymbolAndUnaccent($vnp_Inv_TaxCode);
            }
            if (isset($vnp_BankCode) && $vnp_BankCode != "") {
                $inputData['vnp_BankCode'] = Utils::removeSymbolAndUnaccent($vnp_BankCode);
            }
            if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
                $inputData['vnp_Bill_State'] = Utils::removeSymbolAndUnaccent($vnp_Bill_State);
            }

            ksort($inputData);
            $query = "";
            $i = 0;
            $hashData = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashData .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $vnp_Url);
        } else {
            $resp = $this->responseApi(CommonConstant::INTERNAL_SERVER_ERROR, "Không tìm thấy hóa đơn", null);
        }
        return response()->json($resp);
    }

    /**
     * Giao tiếp xác nhận trạng thái thanh toán vnpay call sang sphacy
     * @param Request $request
     */
    public function paymentIPN(Request $request)
    {
        $vnp_HashSecret = env('VNP_HASHSECRET', '');
        LogEx::methodName($this->className, 'paymentIPN');

        $inputData = $request->input();
        if (isset($inputData['vnp_SecureHash'])) {
            $vnp_SecureHash = $inputData['vnp_SecureHash'];
            unset($inputData['vnp_SecureHash']);
            ksort($inputData);
            $i = 0;
            $hashData = "";
            $inputDataVnpay = array();
            foreach ($inputData as $key => $value) {
                if (substr($key, 0, 4) == "vnp_") {
                    if ($i == 1) {
                        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
                    } else {
                        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                        $i = 1;
                    }
                    $inputDataVnpay = array_merge($inputDataVnpay, [$key => $value]);
                }
            }

            $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
            $vnpTranId = $inputData['vnp_TransactionNo'] ?? ''; //Mã giao dịch tại VNPAY
            $vnp_BankCode = $inputData['vnp_BankCode'] ?? ''; //Ngân hàng thanh toán
            $vnp_Amount = $inputData['vnp_Amount'] / 100; // Số tiền thanh toán VNPAY phản hồi
            $invoiceCode = $inputData['vnp_TxnRef'];

            try {
                //Check invoiceCode
                //Kiểm tra checksum của dữ liệu
                if ($secureHash == $vnp_SecureHash) {
                    //Lấy thông tin đơn hàng lưu trong Database và kiểm tra trạng thái của đơn hàng, mã đơn hàng là: $invoiceCode
                    //Việc kiểm tra trạng thái của đơn hàng giúp hệ thống không xử lý trùng lặp, xử lý nhiều lần một giao dịch
                    $invoice = $this->invoice->findOneBy('invoice_code', $invoiceCode);

                    if (isset($invoice)) {
                        $dataInvoice = DB::select('select v3.f_invoice_detail(?) as result', [json_encode(array('invoice_id' => $invoice->id, 'drug_store_id' => $invoice->drug_store_id))])[0]->result;
                        $invoice = json_decode($dataInvoice)->invoice;
                        //Kiểm tra số tiền thanh toán của giao dịch: giả sử số tiền kiểm tra là đúng.
                        if (($invoice->amount + $invoice->vat_amount - $invoice->discount - $invoice->pay_amount - ($invoice->discount_promotion ?? 0)) == $vnp_Amount) {
                            if ($invoice->payment_status === 'unpaid' || $invoice->payment_status === 'fail') {
                                if ($inputData['vnp_ResponseCode'] == '00' || $inputData['vnp_TransactionStatus'] == '00') {
                                    // Trạng thái thanh toán thành công
                                    $status = 1;
                                } else {
                                    // Trạng thái thanh toán thất bại / lỗi
                                    $status = 2;
                                }
                                $modelLog = array(
                                    "drug_store_id" => $invoice->drug_store_id,
                                    "amount" => $vnp_Amount,
                                    "cash_date" => Carbon::createFromFormat('YmdHis', $inputData["vnp_PayDate"])->toDateTimeString(),
                                    "payment_method" => 'vnpay',
                                    "invoice_id" => $invoice->id,
                                    "invoice_type" => $invoice->invoice_type,
                                    "reason" => $status == 2 && $inputData['vnp_ResponseCode'] ? Utils::MSG_VNPAY[$inputData['vnp_ResponseCode']] : 'Giao dịch thành công',
                                    "status" => $status == 2 ? "fail" : "success",
                                    "body" => json_encode($inputDataVnpay, JSON_FORCE_OBJECT)
                                );

                                $this->paymentLogs->create($modelLog);
                                $modelUpdateInvoice = array(
                                    "payment_status" => $status == 2 ? "fail" : "paid",
                                    "pay_amount" => $status == 2 ? 0 : $vnp_Amount
                                );
                                if (($invoice->invoice_type == 'IV1' && isset($invoice->shipping_status) && $invoice->shipping_status == 'done' && $status == 1) ||
                                    ($invoice->invoice_type == 'IV1' && !isset($invoice->shipping_status) && $status == 1)) {
                                    $modelUpdateInvoice = array_merge($modelUpdateInvoice, array("status" => 'done'));
                                }
                                $this->invoice->updateOneById($invoice->id, $modelUpdateInvoice);
                                if ($status == 1) {
                                    $collectionIn = new Collection();
                                    $collectionIn->drug_store_id = $invoice->drug_store_id;
                                    $this->cashbookService->createModelCashbook($vnp_Amount, $invoice, $collectionIn, false);
                                }

                                if ($invoice->is_order == true && $invoice->invoice_type == 'IV2') {
                                    $order = $this->tOrder->findOneBy("in_invoice_id", $invoice->id);
                                    if (isset($order)) {
                                        $invoiceOut = $this->invoice->findOneById($order->out_invoice_id);
                                        $dataInvoiceOut = DB::select('select v3.f_invoice_detail(?) as result', [Utils::getParams($request->input(), array('invoice_id' => $invoiceOut->id))])[0]->result;
                                        $invoiceOut = json_decode($dataInvoiceOut)->invoice;
                                        if ($invoiceOut) {
                                            $modelLogOut = array(
                                                "drug_store_id" => $invoiceOut->drug_store_id,
                                                "amount" => $vnp_Amount,
                                                "cash_date" => Carbon::createFromFormat('YmdHis', $inputData["vnp_PayDate"])->toDateTimeString(),
                                                "payment_method" => 'vnpay',
                                                "invoice_id" => $invoiceOut->id,
                                                "invoice_type" => $invoiceOut->invoice_type,
                                                "reason" => $status == 2 && $inputData['vnp_ResponseCode'] ? Utils::MSG_VNPAY[$inputData['vnp_ResponseCode']] : 'Giao dịch thành công',
                                                "status" => $status == 2 ? "fail" : "success",
                                                "body" => json_encode($inputDataVnpay, JSON_FORCE_OBJECT)
                                            );
                                            $this->paymentLogs->create($modelLogOut);
                                            $this->invoice->updateOneById($invoiceOut->id, [
                                                "payment_status" => $status == 2 ? "fail" : "paid",
                                                "pay_amount" => $status == 2 ? 0 : $vnp_Amount,
                                                "status" => $invoiceOut->shipping_status == 'done' && $status == 1 ? 'done' : $invoiceOut->status
                                            ]);
                                            if ($status == 1) {
                                                $collectionOut = new Collection();
                                                $collectionOut->drug_store_id = $invoiceOut->drug_store_id;
                                                $this->cashbookService->createModelCashbook($vnp_Amount, $invoiceOut, $collectionOut, true);
                                            }
                                        }
                                    }
                                }

                                //Cài đặt Code cập nhật kết quả thanh toán, tình trạng đơn hàng vào DB
                                //Trả kết quả về cho VNPAY: Website/APP TMĐT ghi nhận yêu cầu thành công
                                $returnData['RspCode'] = '00';
                                $returnData['Message'] = 'Confirm Success';
                            } else {
                                $returnData['RspCode'] = '02';
                                $returnData['Message'] = 'Order already confirmed';
                            }
                        } else {
                            $returnData['RspCode'] = '04';
                            $returnData['Message'] = 'invalid amount';
                        }
                    } else {
                        $returnData['RspCode'] = '01';
                        $returnData['Message'] = 'Order not found';
                    }
                } else {
                    $returnData['RspCode'] = '97';
                    $returnData['Message'] = 'Invalid signature';
                }
            } catch (Exception $e) {
                $returnData['RspCode'] = '99';
                $returnData['Message'] = 'Unknow error';
            }
        } else {
            $returnData['RspCode'] = '99';
            $returnData['Message'] = 'Invalid request';
        }
        //Trả lại VNPAY theo định dạng JSON
        return response()->json($returnData);
    }

    /**
     * Xác nhận trạng thái giao dịch (thành công/thất bại)
     * @param Request $request
     */
    public function paymentVerify(Request $request)
    {
        $vnp_TmnCode = env('VNP_TMNCODE', '');
        $vnp_HashSecret = env('VNP_HASHSECRET', '');
        $vnp_apiUrl = env('VNP_APIURL', '');

        LogEx::methodName($this->className, 'paymentVerify');
        $requestInput = $request->input();

        $invoiceCode = $requestInput["invoice_code"];
        $hashSecret = $vnp_HashSecret;
        $ipaddr = $_SERVER['REMOTE_ADDR'];
        $inputData = array(
            "vnp_Version" => '2.1.0',
            "vnp_Command" => "querydr",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_TxnRef" => $invoiceCode,
            "vnp_OrderInfo" => 'Noi dung thanh toan',
            "vnp_TransDate" => $requestInput['paymentdate'],
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_IpAddr" => $ipaddr
        );
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_apiUrl = $vnp_apiUrl . "?" . $query;
        if (isset($hashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_apiUrl .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        $ch = curl_init($vnp_apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);

        /**
         * Trả về kết quả cho bên gọi kiểm tra
         */
        $resp = [
            'ERR_CODE' => '00',
            'ERR_MSG' => 'success',
            'RESULT' => $data
        ];
        return response()->json($resp);
    }

    public function getHistoryPayment($type, Request $request)
    {
        LogEx::methodName($this->className, 'getHistoryPayment');

        if ($type !== 'IV1' && $type !== 'IV2') {
            return response()->json($this->responseApi(CommonConstant::FORBIDDEN, 'Loại hóa đơn không đúng', null));
        }
        $data = $this->paymentLogs->filter($type, $request->input(), $request->userInfo);
        $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        return response()->json($resp);
    }

    /**
     * api v3
     * export history payment
    */
    public function exportHistoryPayment($type, Request $request)
    {
        LogEx::methodName($this->className, 'exportHistoryPayment');

        if ($type !== 'IV1' && $type !== 'IV2') {
            return response()->json($this->responseApi(CommonConstant::FORBIDDEN, 'Loại hóa đơn không đúng', null));
        }

        $data = $this->paymentLogs->filter($type, $request->input(), $request->userInfo);
        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->paymentLogs->filter($type, $request->input(), $request->userInfo, 1, 35000);
                    break;
                case "current_page":
                    $data = $this->paymentLogs->filter($type, $request->input(), $request->userInfo, 1);
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = $this->paymentLogs->filter($type, $request->input(), $request->userInfo, 1, 3500);
                    break;
            }
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    //new

    public function saveInvoiceSales(InvoiceSalesRequest $request)
    {
        LogEx::methodName($this->className, 'saveInvoiceSales');
//        LogEx::info($request);
//        $userInfo = $request->userInfo;
//        $requestInput = $request->input();
//        if (isset($requestInput["id"])) {
//            $data = $this->invoiceService->getDetail($requestInput["id"], $userInfo->drug_store_id);
//            if (!$data) {
//                return \App\Helper::successResponse(CommonConstant::NOT_FOUND, CommonConstant::MSG_NOTFOUND);
//            }
//        }
//        if(!empty($requestInput["is_master_data"]) && $requestInput["is_master_data"] === "true" && isset($requestInput["drug_master_data_id"])){
//            $existMasterData = $this->invoiceService->checkExistDrugMaster($requestInput["drug_master_data_id"], $userInfo->drug_store_id);
//            if($existMasterData){
//                if($existMasterData === 'no_drug_master'){
//                    return \App\Helper::successResponse(CommonConstant::NOT_FOUND, "Không tìm thấy thông tin thuốc Dược quốc gia");
//                }else{
//                    return \App\Helper::successResponse(CommonConstant::CONFLICT, CommonConstant::MSG_DATA_EXISTS);
//                }
//            }
//        }


        $data = $this->invoiceService->createOrUpdate($request);
        if ($data) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::successResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function testCall()
    {
        LogEx::methodName($this->className, 'testCall');
        $data = \App\Models\Invoice::limit(10)->get();
        return \App\Helper::successResponse(CommonConstant::INTERNAL_SERVER_ERROR, $data);
    }
}
