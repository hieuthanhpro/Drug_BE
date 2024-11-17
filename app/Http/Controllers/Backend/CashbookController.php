<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashbookRequest;
use App\Http\Requests\CashTypeRequest;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Repositories\Cashbook\CashbookRepositoryInterface;
use App\Repositories\CashType\CashTypeRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Services\CashbookService;
use Illuminate\Http\Request;



class CashbookController extends Controller
{
    protected $className = "Backend\CashbookController";

    protected $cashbook;
    protected $cashType;
    protected $invoice;
    protected $cashbookService;

    public function __construct(CashbookRepositoryInterface $cashbook, CashTypeRepositoryInterface $cashType,
                                InvoiceRepositoryInterface  $invoice, CashbookService $cashbookService)
    {
        LogEx::constructName($this->className, '__construct');

        $this->cashbook = $cashbook;
        $this->cashType = $cashType;
        $this->invoice = $invoice;
        $this->cashbookService = $cashbookService;
    }

    /**
     * Lấy danh sách phiếu thu hoặc phiếu chi
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'getList');
        $requestInput = $request->input();
        $user = $request->userInfo;
        $data = $this->cashbookService->filter($requestInput, $user->drug_store_id);
        $data1 = $this->cashbook->findManyBy('drug_store_id', $user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * export
     */
    public function export(Request $request)
    {
        LogEx::methodName($this->className, 'export');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->cashbookService->filter($requestInput, $user->drug_store_id, 1, 35000);
                    break;
                case "current_page":
                    $data = $this->cashbookService->filter($requestInput, $user->drug_store_id, 1);
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = $this->cashbookService->filter($requestInput, $user->drug_store_id, 1, 3500);
                    break;
            }
        }

        return $data;
    }

    /**
     * Tạo mã phiếu thu hoặc chi
     * @param $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCodeCashbook($type): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'getCodeCashbook');
        if ($type != 'PT' && $type != 'PC') {
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
        }
        if ($type == 'PC') {
            $code = 'PC' . Utils::getSequenceDB('PC');
        } else {
            $code = 'PT' . Utils::getSequenceDB('PT');
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $code);
    }

    /**
     * Tạo mới phiếu thu hoặc chi
     * @param CashbookRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(CashbookRequest $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'save');
        $user = $request->userInfo;
        $requestInput = $request->input();
        $data = $this->cashbookService->createCashbook($requestInput, $user->drug_store_id, $user->id);
        if (isset($data)) {
            if (isset($data->invoice_id)) {
                $this->cashbookService->updateDebtInvoice($data->invoice_id, $data->amount, $user->drug_store_id);
            }
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /**
     * Lấy danh sách các loại phiếu thu hoặc chi
     * @param $type
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCashType($type, Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'getCodeCashbook');
        $user = $request->userInfo;
        if ($type != 'PT' && $type != 'PC') {
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
        }
        $data = $this->cashType->getCashType($user->drug_store_id, $type);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * Tạo mới loại phiếu thu hoặc chi
     * @param CashTypeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCashType(CashTypeRequest $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'addCashType');
        $requestInput = $request->input();
        $user = $request->userInfo;
        $requestInput['drug_store_id'] = $user->drug_store_id;
        $data = $this->cashType->create($requestInput);
        if(isset($data)){
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /**
     * Hủy phiếu thu hoặc chi
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelCashbook($id): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'cancelCashbook');
        $data = $this->cashbook->findOneById($id);
        if (!isset($data)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $data = $this->cashbook->updateOneById($id, ['status' => 'cancel']);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
