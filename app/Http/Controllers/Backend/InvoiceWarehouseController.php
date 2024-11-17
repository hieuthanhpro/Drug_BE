<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceWarehouse\InvoiceWarehouseFilterRequest;
use App\LibExtension\CommonConstant;
use App\LibExtension\Utils;
use App\Models\Invoice;
use App\Models\Unit;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\InvoiceDetail\InvoiceDetailRepositoryInterface;
use App\Repositories\InvoiceWarehouse\InvoiceWarehouseRepositoryInterface;
use App\Services\InvoiceWarehouseService;
use App\Services\WarehouseService;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;
use Illuminate\Support\Facades\DB;

class InvoiceWarehouseController extends Controller
{
    protected $className = "Backend\InvoiceWarehouseController";
    protected $invoiceWarehouse;
    protected $invoiceWarehouseService;
    protected $invoice;
    protected $invoiceDetail;

    public function __construct(InvoiceWarehouseRepositoryInterface $invoiceWarehouse,
                                WarehouseService $warehouseService,
                                InvoiceRepositoryInterface          $invoice,
                                InvoiceDetailRepositoryInterface $invoiceDetail,
                                InvoiceWarehouseService $invoiceWarehouseService)
    {
        LogEx::constructName($this->className, '__construct');
        $this->invoiceWarehouse = $invoiceWarehouse;
        $this->warehouseService = $warehouseService;
        $this->invoice = $invoice;
        $this->invoiceDetail = $invoiceDetail;
        $this->invoiceWarehouseService = $invoiceWarehouseService;
    }

    public function filterInvoiceWarehouse($type, Request $request)
    {
        try {
            $requestInput = $request->input();
            $userInfo = $request->user;
            $data = $this->invoiceWarehouse->filterInvoiceWarehouse($type, $requestInput, $userInfo);
            $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Có lỗi xảy ra vui lòng thử lại sau');
        }

        return response()->json($resp);
    }

    public function filter(InvoiceWarehouseFilterRequest $invoiceWarehouseFilterRequest)
    {
        LogEx::methodName($this->className, 'filter');
        $data = $this->invoiceWarehouse->filter($invoiceWarehouseFilterRequest->input(), $invoiceWarehouseFilterRequest->userInfo->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function export(InvoiceWarehouseFilterRequest $drugFilterRequest){
        LogEx::methodName($this->className, 'export');
        $data = $this->invoiceWarehouseService->export($drugFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function detailInvoiceWarehouse($id, Request $request)
    {
        try {
            $data = DB::select('select v3.f_invoice_warehouse_detail(?) as result', [Utils::getParams($request->input(), array('id' => $id))]);
            if (count($data) > 0) {
                $rs = json_decode($data[0]->result);
                if (isset($rs->invoice)) {
                    return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $rs);
                }
            }
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    /**
     * api v3
     * from detailInvoiceWarehouse
    */
    public function detailInvoiceWarehouseV3($id, Request $request)
    {
        $mobile = $request->input('mobile') ?? null;

        try {
            $data = !empty($mobile) ? $this->invoiceWarehouseService->invoiceWarehouseDetailV3($id, $request) :
                $this->invoiceWarehouseService->invoiceWarehouseDetailV3Old($id, $request);

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function invoiceWarehouseSave($type, Request $request)
    {
        LogEx::methodName($this->className, 'invoiceWarehouseSave');

        try {
            $requestInput = $request->input();
            $user = $request->userInfo;
            if ($type !== "export" && $type !== "import") {
                $resp = $this->responseApi(CommonConstant::BAD_REQUEST, 'Có lỗi xảy ra vui lòng thử lại sau', null);
                return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
            }
            $validation = $this->invoiceWarehouseService->validation($requestInput);
            if ($validation->fails()) {
                return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $validation->getMessageBag()->first());
            }
            if (isset($requestInput["id"])) {
                $invoice = $this->invoiceWarehouse->getByIdAndDrugStoreId($requestInput["id"], $user["drug_store_id"]);
                if (isset($invoice)) {
                    if($invoice->status === 'done' || $invoice->status === 'delivery'){
                        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, "Trạng thái phiếu không chính xác");
                    }
                    $dataInvoiceWarehouse = $this->invoiceWarehouseService->updateInvoiceWarehouseManual($type, $requestInput, $user);
                } else {
                    return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
                }
            } else {
                $dataInvoiceWarehouse = $this->invoiceWarehouseService->createInvoiceWarehouseManual($type, $requestInput, $user);
            }
            if ($dataInvoiceWarehouse == -10) {
                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, "Hạn dùng phải lớn hơn ngày hiện tại");
            }
            if (!isset($dataInvoiceWarehouse)) {
                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
            } else {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataInvoiceWarehouse);
            }
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function cancelInvoiceWarehouse($id)
    {
        LogEx::methodName($this->className, 'cancelInvoiceWarehouse');
        try {
            $data = $this->invoiceWarehouse->findOneById($id);
            if (!isset($data) || $data->status === 'done') {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
            }
            if ($data->status === 'temp') {
                $this->invoiceWarehouse->deleteOneById($id);
                $this->invoiceDetail->deleteManyBy("warehouse_invoice_id", $id);
            } else {
                $this->invoiceWarehouse->updateOneById($id, ['status' => 'cancel']);
                if (isset($data->invoice_id)) {
                    $this->invoice->updateOneById($data->invoice_id, ['status' => 'cancel']);
                }
            }
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function changeStatusInvoiceWarehouse($id, Request $request)
    {
        LogEx::methodName($this->className, 'changeStatusInvoiceWarehouse');
        $requestInput = $request->input();
        try {
            $data = $this->invoiceWarehouse->findOneById($id);
            if (!isset($data) || $data->status === 'done' || $requestInput['status'] === 'cancel') {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
            }
            $this->invoiceWarehouse->updateOneById($id, ['status' => $requestInput['status'], 'date' => $requestInput['status'] === 'delivery' ? Carbon::now() : null]);
            if (isset($data->invoice_id)) {
                if($requestInput['status'] === 'delivery'){
                    $this->invoice->updateOneById($data->invoice_id, ['shipping_status' => 'delivery']);
                }
            }
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function checkImportInvoiceWarehouse(Request $request)
    {
        LogEx::methodName($this->className, 'checkImportInvoiceWarehouse');
        $requestInput = $request->input();
        try {
            $data = Utils::executeRawQuery('select * from v3.f_invoice_warehouse_check_import(?)', [Utils::getParams($requestInput)]);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from checkImportInvoiceWarehouse
    */
    public function checkImportInvoiceWarehouseV3(Request $request)
    {
        LogEx::methodName($this->className, 'checkImportInvoiceWarehouseV3');
        $file = $request->file;
        $drug_store_id = $request->userInfo->drug_store_id;

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->setShouldFormatDates(true);
        $reader->open($file);

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getIndex() === 0) {
                foreach ($sheet->getRowIterator() as $key => $row) {
                    if ($key > 3) {
                        $code = $row->toArray()[0];//*
                        $date = \Illuminate\Support\Carbon::parse($row->toArray()[1])->format('Y-m-d');//*
                        $invoice_type = $row->toArray()[2];//*
                        $reason = $row->toArray()[3];
                        $provider_name = $row->toArray()[4];
                        $invoice_details = $row->toArray()[5];//*
                        //$drug_store_id = $drug_store_id;

                        if (!$code)
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập số mã hóa đơn');

                        if (!$date)
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập số ngày bán');

                        if (!$invoice_type)
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập loại hóa đơn');

                        $invoice_codes [] = $code;
                        $invoice_detailList [] = json_decode($invoice_details);

                        if (!(json_decode($invoice_details)))
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập chi tiết các thuốc');

                        foreach (json_decode($invoice_details) as $item) {
                            if (!$item->drug_code)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần mã thuốc');

                            if (!$item->drug_name)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập tên thuốc');

                            if (!$item->number)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập số lô');

                            if (!$item->expiry_date)
                                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR,
                                    'Cần nhập hạn dùng cần phải nhập theo định dạng DD/MM/YYYY: ');

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
                            "code" => $code,
                            "date" => $date,
                            "invoice_type" => $invoice_type,
                            "reason" => $reason,
                            "provider_name" => $provider_name,
                            "invoice_details" => json_decode($invoice_details)
                        ];
                    }

                }
                break;
            }
        }

        $reader->close();

        if (!(Unit::whereIn('name', $drug_names)->get()))
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR,
                'Đơn vị tính không tồn tại');

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function importInvoiceWarehouse(Request $request)
    {
        LogEx::methodName($this->className, 'importInvoiceWarehouse');
        $requestInput = $request->input();

        try {
            $data = Utils::executeRawQuery('select * from v3.f_invoice_warehouse_import(?) as result', [Utils::getParams($requestInput)]);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
    }
    /**
     * api v3
     * from importInvoiceWarehouse
    */
    public function importInvoiceWarehouseV3(Request $request)
    {
        LogEx::methodName($this->className, 'importInvoiceWarehouseV3');

        $user = $request->userInfo;
        $count_fasle = 0;
        $data = $request->input();
        $type = $request->input('type');

        if (count($data['datas']) === 0) return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, 'Không có hóa đơn nào cần import');

        $data = $data['datas'];

        DB::beginTransaction();
        try {
            $collections = collect($data)->chunk(50);
            foreach ($collections as $collection) {
                foreach ($collection as $value) {
                    $invoiceDetail = [];
                    $dataInvoice = array(
                        "code" => Utils::coalesce($value, 'code', null),
                        "date" => Utils::coalesce($value, 'date', null),
                        "invoice_type" => ($value["invoice_type"] === 1) ? "IV2" : "IV7",
                        "reason" => Utils::coalesce($value, 'reason', null),
                        "ref_code" => Utils::coalesce($value, 'ref_code', null),
                        "supplier_id" => Utils::coalesce($value, 'supplier_id', null),
                        "status" => "done",
                        "is_import" => true
                    );

                    foreach ($value['invoice_details'] as $item) {
                        $date = explode('/', $item['expiry_date']);
                        $invoiceDetail[] = [
                            "expiry_date" => "$date[2]/$date[1]/$date[0]",
                            //"is_import" => 1,
                            //"drug_code" => Utils::coalesce($item, 'drug_code', null),
                            //"drug_name" => Utils::coalesce($item, 'drug_name', null),
                            "number" => Utils::coalesce($item, 'number', null),
                            "mfg_date" => Utils::coalesce($item, 'mfg_date', null),
                            //"registry_number" => Utils::coalesce($item, 'registry_number', null),
                            "quantity" => Utils::coalesce($item, 'quantity', 0),
                            "cost" => Utils::coalesce($item, 'cost', 0),
                            //"unit_name" => Utils::coalesce($item, 'unit_name', 0),
                        ];
                    }

                    $dataInvoice['line_items'] = $invoiceDetail;
                    $listInvoiceID = $this->invoiceWarehouseService->createInvoiceWarehouseManual($type, $dataInvoice, $user);
                    if (!$listInvoiceID) $count_fasle += 1;
                }
            }
            DB::commit();
            $listInvoices = [
                'count' => count($data),
                'insert_false' => $count_fasle
            ];

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $listInvoices);
        } catch (\Exception $e) {
            DB::rollBack();
            LogEx::try_catch($this->className, $e);

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }
}
