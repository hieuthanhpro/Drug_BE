<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\InvoiceFilterRequest;
use App\Http\Requests\InvoiceWarehouse\InvoiceWarehouseFilterRequest;
use App\Http\Requests\Warehouse\WarehouseFilterRequest;
use App\LibExtension\CommonConstant;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Services\InvoiceWarehouseService;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use App\LibExtension\Utils;
use App\LibExtension\LogEx;

class WarehouseController extends Controller
{
    protected $className = "Backend\WarehouseController";
    protected $warehouse;
    protected $warehouseService;

    public function __construct(
         WarehouseRepositoryInterface $warehouse,
         WarehouseService $WarehouseService
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->warehouse = $warehouse;
        $this->warehouseService = $WarehouseService;
    }

    //New
    public function filter(WarehouseFilterRequest $warehouseFilterRequest)
    {
        LogEx::methodName($this->className, 'filter');
        $data = $this->warehouse->filter($warehouseFilterRequest->input(), $warehouseFilterRequest->userInfo->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
    public function export(InvoiceWarehouseFilterRequest $invoiceWarehouseFilterRequest){
        LogEx::methodName($this->className, 'export');
        $data = $this->warehouseService->export($invoiceWarehouseFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
    /**
     * api v3
     * export InOut
    */
    public function exportInOut(InvoiceWarehouseFilterRequest $invoiceWarehouseFilterRequest) {
        LogEx::methodName($this->className, 'exportInOut');

        $data = $this->warehouseService->exportInout($invoiceWarehouseFilterRequest);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
    //=================================================//
    public function getListUnitByDrug($drug_id, Request $request)
    {
        LogEx::methodName($this->className, 'getListUnitByDrug');

        $drugStoreId = $request->userInfo->drug_store_id;
        $data = $this->warehouse->getListPackages($drugStoreId, $drug_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function autoListWithPackages4SaleShort($inputText, Request $request)
    {
        LogEx::methodName($this->className, 'autoListWithPackages4SaleShort');
        return $this->autoListWithPackages4Sale(1, $inputText, $request);
    }

    public function autoListWithPackages4Sale($modeSearch, $inputText, Request $request)
    {
        LogEx::methodName($this->className, 'autoListWithPackages4Sale');
        $userInfo = $request->userInfo;
        $data = $this->warehouse->getAutoListWithPacks4Sale($userInfo->drug_store_id, $modeSearch, $inputText);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    // Select favorite drug (warehouse) for sale drug
    public function autoListWithPackages4SaleFavorite(Request $request)
    {
        LogEx::methodName($this->className, 'autoListWithPackages4SaleFavorite');
        $userInfo = $request->userInfo;
        $data = $this->warehouse->getAutoListWithPacks4SaleFavorite($userInfo->drug_store_id, $request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getListUnitByDrug4Sale($drug_id, Request $request)
    {
        LogEx::methodName($this->className, 'getListUnitByDrug4Sale');

        $drugStoreId = $request->userInfo->drug_store_id;
        $data = $this->warehouse->getListPackages4Sale($drugStoreId, $drug_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getListUnitByDrugIds4Sale(Request $request)
    {
        LogEx::methodName($this->className, 'getListUnitByDrugIds4Sale');
        $requestInput = $request->input();
        $drugStoreId = $request->userInfo->drug_store_id;
        $data = $this->warehouse->getListPackages4SaleByDrugIds($drugStoreId, $requestInput["drug_ids"]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getStockList(Request $request)
    {
        LogEx::methodName($this->className, 'getStockList');

        $data = $this->warehouse->getStockList($request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getStockList
    */
    public function getStockListV3(Request $request)
    {
        LogEx::methodName($this->className, 'getStockList');

        $data = $this->warehouse->getStockListV3($request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * exportStockListV3
    */
    public function exportStockListV3(Request $request){
        LogEx::methodName($this->className, 'exportStockListV3');

        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = $this->getStockListV3($request, 1);

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->warehouse->getStockListV3($request, 1, 3500);
                    break;
                case "current_page":
                    $data = $this->warehouse->getStockListV3($request, 1);
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = $this->warehouse->getStockListV3($request, 1, 3500);
                    break;
            }
        }

        return $data;
    }

    public function getWarehouseInOut(Request $request)
    {
        LogEx::methodName($this->className, 'getWarehouseInOut');

        $requestInput = $request->input();

        if (!empty($requestInput['query'])) {
            $requestInput['search'] = $requestInput['query'];
        }

        try {
            $data = Utils::executeRawQuery('select * from v3.f_stockym_get_data(?)', [Utils::getParams($requestInput)], $request->url(), $requestInput);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * fom getWarehouseInOut
    */
    public function getWarehouseInOutV3(Request $request)
    {
        LogEx::methodName($this->className, 'getWarehouseInOut');

        $drugStoreID = $request->userInfo->drug_store_id;
        $requestInput = $request->input();

        if (!empty($requestInput['query'])) {
            $requestInput['search'] = $requestInput['query'];
        }

        try {
            $data = Utils::executeRawQuery(
                    'select * from v3.f_stockym_get_data(?)',
                    [Utils::getParams($requestInput)],
                    $request->url(),
                    $requestInput);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());

            //return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, []);
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
