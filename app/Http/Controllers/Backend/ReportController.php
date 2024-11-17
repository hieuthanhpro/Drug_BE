<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceWarehouse\InvoiceWarehouseFilterRequest;
use App\Http\Requests\Report\ReportGoodsInOutFilterRequest;
use App\Http\Requests\Report\ReportRevenueFilterRequest;
use App\Http\Requests\Report\ReportRevenueProfitFilterRequest;
use App\Http\Requests\Report\ReportSalePersonFilterRequest;
use App\LibExtension\CommonConstant;
use App\Repositories\Report\ReportRepositoryInterface;
use App\Services\ReportService;
use Illuminate\Http\Request;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;

class ReportController extends Controller
{
    protected $className = "Backend\ReportController";

    protected $invoice;
    protected $reportServices;
    protected $report;

    public function __construct(
        InvoiceRepositoryInterface $invoice,
        ReportService $reportServices
    ) {
        LogEx::constructName($this->className, '__construct');

        $this->invoice = $invoice;
        $this->reportServices = $reportServices;
    }

    public function reportInventory(Request $request)
    {
        LogEx::methodName($this->className, 'reportInventory');

        $user = $request->userInfo;
        $input = $request->input();
        $fromDate = $input['from_date'] ?? null;
        $toDate = $input['to_date'] ?? null;
        $category = $input['category'] ?? null;
        $groupDrug = $input['group_drug'] ?? null;
        $name = $input['name'] ?? null;
        $exportType = $input['export_type'] ?? null;

        if ($exportType != null) {
            $data = $this->reportServices->getInventoryByDrugstoreFull($user->drug_store_id);
        } else {
            $data = $this->reportServices->getInventoryByDrugstore($user->drug_store_id, $fromDate, $toDate, $category, $groupDrug, $name);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function reportDrugByType(Request $request)
    {
        LogEx::methodName($this->className, 'reportDrugByType');

        $user = $request->userInfo;
        $type = $request->input('type');
        $data = $this->reportServices->reportDrugByType($user->drug_store_id, $type);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function reportRevenueMoney(Request $request)
    {
        LogEx::methodName($this->className, 'reportRevenueMoney');

        $user = $request->userInfo;
        $input = $request->input();
        $form_date = $input['form_date'] ?? ($input['from_date'] ?? null);
        $to_date = $input['to_date'] ?? null;

        $data = Utils::executeRawQuery('select * from f_report_revenue(?, ?, ?)', [$user->drug_store_id, $form_date, $to_date], $request->url(), $input);
        $columnMap = function ($item) {
            return "sum(t.$item) as $item";
        };
        $sumColumns = array(
            'cash_amount',
            'not_cash_amount',
            'direct_amount',
            'not_direct_amount',
            'vat_amount',
            'amount',
            'discount',
            'total',
            'debt',
            'sumamount'
        );
        $data = Utils::getSumData($data, $input, 'select ' . join(',', array_map($columnMap, $sumColumns)) . ' from tmp_output t');
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from reportRevenueMoney and export
    */
    public function reportRevenueMoneyV3(Request $request)
    {
        LogEx::methodName($this->className, 'reportRevenueMoneyV3');

        $user = $request->userInfo;
        $input = $request->input();
        $form_date = $input['form_date'] ?? ($input['from_date'] ?? null);
        $to_date = $input['to_date'] ?? null;
        $query = $this->reportServices->reportRevenueV3($user->drug_store_id, $form_date, $to_date);
        $queries = $query;
        $query_sum = $query
            ->get()
            ->toArray();
        $data = Utils::executeRawQueryV3(
            $queries,
            $request->url(),
            $request->input()
        );
        $sum_data = [
            'cash_amount' => array_sum(array_column($query_sum, 'cash_amount')),
            'not_cash_amount' => array_sum(array_column($query_sum, 'not_cash_amount')),
            'not_direct_amount' => array_sum(array_column($query_sum, 'not_direct_amount')),
            'vat_amount' => array_sum(array_column($query_sum, 'vat_amount')),
            'amount' => array_sum(array_column($query_sum, 'amount')),
            'discount' => array_sum(array_column($query_sum, 'discount')),
            'total' => array_sum(array_column($query_sum, 'total')),
            'debt' => array_sum(array_column($query_sum, 'debt')),
            'sumamount' => array_sum(array_column($query_sum, 'sumamount')),
            'direct_amount' => array_sum(array_column($query_sum, 'direct_amount'))
        ];
        $datas = Utils::getSumDataV3($data, $request->input(), $sum_data);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $datas);
    }

    public function reportRevenueMoneyExportV3(ReportRevenueFilterRequest $reportRevenueFilterRequest){
        LogEx::methodName($this->className, 'reportRevenueMoneyExportV3');

        $requestInput = $reportRevenueFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $datas = null;
        $user = $reportRevenueFilterRequest->userInfo;
        $input = $reportRevenueFilterRequest->input();
        $form_date = $input['form_date'] ?? ($input['from_date'] ?? null);
        $to_date = $input['to_date'] ?? null;

        $query = $this->reportServices->reportRevenueV3($user->drug_store_id, $form_date, $to_date);
        $queries = $query;
        $query_sum = $query
            ->get()
            ->toArray();

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQueryV3(
                        $queries,
                        $reportRevenueFilterRequest->url(),
                        $reportRevenueFilterRequest->input(),
                        1,
                        3500
                    );
                    $sum_data = [
                        'cash_amount' => array_sum(array_column($query_sum, 'cash_amount')),
                        'not_cash_amount' => array_sum(array_column($query_sum, 'not_cash_amount')),
                        'not_direct_amount' => array_sum(array_column($query_sum, 'not_direct_amount')),
                        'vat_amount' => array_sum(array_column($query_sum, 'vat_amount')),
                        'amount' => array_sum(array_column($query_sum, 'amount')),
                        'discount' => array_sum(array_column($query_sum, 'discount')),
                        'total' => array_sum(array_column($query_sum, 'total')),
                        'debt' => array_sum(array_column($query_sum, 'debt')),
                        'sumamount' => array_sum(array_column($query_sum, 'sumamount'))
                    ];
                    $datas = Utils::getSumDataV3($data, $reportRevenueFilterRequest->input(), $sum_data);
                    break;
                case "current_page":
                    $data = Utils::executeRawQueryV3(
                        $queries,
                        $reportRevenueFilterRequest->url(),
                        $reportRevenueFilterRequest->input(),
                        1
                    );
                    $sum_data = [
                        'cash_amount' => array_sum(array_column($query_sum, 'cash_amount')),
                        'not_cash_amount' => array_sum(array_column($query_sum, 'not_cash_amount')),
                        'not_direct_amount' => array_sum(array_column($query_sum, 'not_direct_amount')),
                        'vat_amount' => array_sum(array_column($query_sum, 'vat_amount')),
                        'amount' => array_sum(array_column($query_sum, 'amount')),
                        'discount' => array_sum(array_column($query_sum, 'discount')),
                        'total' => array_sum(array_column($query_sum, 'total')),
                        'debt' => array_sum(array_column($query_sum, 'debt')),
                        'sumamount' => array_sum(array_column($query_sum, 'sumamount'))
                    ];
                    $datas = Utils::getSumDataV3($data, $reportRevenueFilterRequest->input(), $sum_data);
                    break;
                case "current_search":
                    $reportRevenueFilterRequest->request->remove("page");
                    $data = Utils::executeRawQueryV3(
                        $queries,
                        $reportRevenueFilterRequest->url(),
                        $reportRevenueFilterRequest->input(),
                        1,
                        3500
                    );
                    $sum_data = [
                        'cash_amount' => array_sum(array_column($query_sum, 'cash_amount')),
                        'not_cash_amount' => array_sum(array_column($query_sum, 'not_cash_amount')),
                        'not_direct_amount' => array_sum(array_column($query_sum, 'not_direct_amount')),
                        'vat_amount' => array_sum(array_column($query_sum, 'vat_amount')),
                        'amount' => array_sum(array_column($query_sum, 'amount')),
                        'discount' => array_sum(array_column($query_sum, 'discount')),
                        'total' => array_sum(array_column($query_sum, 'total')),
                        'debt' => array_sum(array_column($query_sum, 'debt')),
                        'sumamount' => array_sum(array_column($query_sum, 'sumamount'))
                    ];
                    $datas = Utils::getSumDataV3($data, $reportRevenueFilterRequest->input(), $sum_data);
                    break;
            }
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $datas);
    }

    public function reportRevenueInvoice(Request $request)
    {
        LogEx::methodName($this->className, 'reportRevenueInvoice');

        $user = $request->userInfo;
        $input = $request->input();
        $form_date = $input['form_date'] ?? ($input['from_date'] ?? null);
        $to_date = $input['to_date'] ?? null;
        $data = $this->reportServices->reportRevenueMoney($user->drug_store_id, 2, $form_date, $to_date);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function reportRevenueDrug(Request $request)
    {
        LogEx::methodName($this->className, 'reportRevenueDrug');

        $user = $request->userInfo;
        $input = $request->input();
        $formDate = $input['form_date'] ?? ($input['from_date'] ?? null);
        $toDate = $input['to_date'] ?? null;
        $data = $this->reportServices->reportRevennueMoney($user->drug_store_id, 3, $formDate, $toDate);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }


    public function getExportImport(Request $request)
    {
        LogEx::methodName($this->className, 'getExportImport');

        $user = $request->userInfo;
        $input = $request->input();
        $formDate = $input['form_date'] ?? ($input['from_date'] ?? null);
        $toDate = $input['to_date'] ?? null;
        $type = $input['type'] ?? null;
        $data = $this->reportServices->getListExportImport($user->drug_store_id, $type, $formDate, $toDate);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getGoodsInOut(Request $request)
    {
        LogEx::methodName($this->className, 'getGoodsInOut');

        $data = $this->reportServices->getGoodsInOut($request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getSalesPerson(Request $request)
    {
        LogEx::methodName($this->className, 'getSalesPerson');

        $data = $this->reportServices->getSalesPerson($request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getRevenueProfit(Request $request)
    {
        LogEx::methodName($this->className, 'getRevenueProfit');

        $data = $this->reportServices->getRevenueProfit($request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getPrescriptionStatistic(Request $request)
    {
        LogEx::methodName($this->className, 'getPrescriptionStatistic');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $fromDate = $requestInput['from_date'] ?? null;
        $toDate = $requestInput['to_date'] ?? null;
        $search = $requestInput['search'] ?? null;

        $data = Utils::executeRawQuery('select * from f_report_prescription_statistic(?, ?, ?, ?)', [$user->drug_store_id, $search, $fromDate, $toDate], $request->url(), $requestInput);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getPrescriptionStatistic and export
    */
    public function getPrescriptionStatisticV3(Request $request)
    {
        LogEx::methodName($this->className, 'getPrescriptionStatisticV3');

        $query = $this->reportServices->reportPrescriptionStatisticV3($request);
        $data = Utils::executeRawQueryV3(
            $query,
            $request->url(),
            $request->input()
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function exporPrescriptionStatisticV3(InvoiceWarehouseFilterRequest $invoiceWarehouseFilterRequest) {
        LogEx::methodName($this->className, 'exporPrescriptionStatisticV3');

        $data = $this->reportServices->exporPrescriptionStatisticV3($invoiceWarehouseFilterRequest);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getSpecialDrugReport(Request $request)
    {
        LogEx::methodName($this->className, 'getSpecialDrugReport');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $fromDate = $requestInput['from_date'] ?? null;
        $toDate = $requestInput['to_date'] ?? null;
        $search = $requestInput['search'] ?? null;
        $reportType = $requestInput['report_type'] ?? null;

        $data = Utils::executeRawQuery(
            'select * from f_report_special_drug(?, ?, ?, ?, ?, ?)',
            [$user->drug_store_id, $user->id, $search, $fromDate, $toDate, $reportType],
            $request->url(),
            $requestInput
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * getSpecialDrugReportExport
    */
    public function getSpecialDrugReportExport(Request $request)
    {
        LogEx::methodName($this->className, 'getSpecialDrugReportExport');

        $data = null;
        $user = $request->userInfo;
        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $fromDate = $requestInput['from_date'] ?? null;
        $toDate = $requestInput['to_date'] ?? null;
        $search = $requestInput['search'] ?? null;
        $reportType = $requestInput['report_type'] ?? null;

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQuery(
                        'select * from f_report_special_drug(?, ?, ?, ?, ?, ?)',
                        [$user->drug_store_id, $user->id, $search, $fromDate, $toDate, $reportType],
                        $request->url(),
                        $requestInput,
                        1,
                        3500
                    );
                    break;
                case "current_page":
                    $data = Utils::executeRawQuery(
                        'select * from f_report_special_drug(?, ?, ?, ?, ?, ?)',
                        [$user->drug_store_id, $user->id, $search, $fromDate, $toDate, $reportType],
                        $request->url(),
                        $requestInput,
                        1
                    );
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = Utils::executeRawQuery(
                        'select * from f_report_special_drug(?, ?, ?, ?, ?, ?)',
                        [$user->drug_store_id, $user->id, $search, $fromDate, $toDate, $reportType],
                        $request->url(),
                        $requestInput,
                        1,
                        3500
                    );
                    break;
            }
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getSpecialDrugReport
    */
    public function getSpecialDrugReportV3(Request $request)
    {
        LogEx::methodName($this->className, 'getSpecialDrugReportV3');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $fromDate = $requestInput['from_date'] ?? null;
        $toDate = $requestInput['to_date'] ?? null;
        $search = $requestInput['search'] ?? null;
        $reportType = $requestInput['report_type'] ?? null;

        $data = Utils::executeRawQueryV3(
            $this->reportServices->reportSpecialDrugV3(
                $user->drug_store_id, $user->id,
                $search, $fromDate,
                $toDate, $reportType
            ),
            $request->url(),
            $requestInput
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }


    // New
    public function filterRevenue(ReportRevenueFilterRequest $reportRevenueFilterRequest)
    {
        LogEx::methodName($this->className, 'filterRevenue');
        $data = $this->reportServices->filterRevenue($reportRevenueFilterRequest->input(),
            $reportRevenueFilterRequest->userInfo->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function filterRevenueProfit(ReportRevenueProfitFilterRequest $reportRevenueProfitFilterRequest){
        LogEx::methodName($this->className, 'filterRevenueProfit');

        $requestInput = $reportRevenueProfitFilterRequest->input();
        $requestInput['url'] = $reportRevenueProfitFilterRequest->url();
        $data = $this->reportServices->filterRevenueProfit($requestInput,
            $reportRevenueProfitFilterRequest->userInfo->drug_store_id);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function filterSalePerson(ReportSalePersonFilterRequest $reportSalePersonFilterRequest){
        LogEx::methodName($this->className, 'filterSalePerson');

        $requestInput = $reportSalePersonFilterRequest->input();
        $requestInput['url'] = $reportSalePersonFilterRequest->url();
        $data = $this->reportServices->filterSalePerson($requestInput,
            $reportSalePersonFilterRequest->userInfo->drug_store_id);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function filterGoodsInOut(ReportGoodsInOutFilterRequest $reportGoodsInOutFilterRequest){
        LogEx::methodName($this->className, 'filterGoodsInOut');
        $data = $this->reportServices->filterGoodsInOut($reportGoodsInOutFilterRequest->input(),
            $reportGoodsInOutFilterRequest->userInfo->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function exportRevenue(ReportRevenueFilterRequest $reportRevenueFilterRequest){
        LogEx::methodName($this->className, 'exportRevenue');
        $data = $this->reportServices->exportRevenue($reportRevenueFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function exportRevenueProfit(ReportRevenueProfitFilterRequest $reportRevenueProfitFilterRequest){
        LogEx::methodName($this->className, 'exportRevenueProfit');
        $data = $this->reportServices->exportRevenueProfit($reportRevenueProfitFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function exportSalePerson(ReportSalePersonFilterRequest $reportSalePersonFilterRequest){
        LogEx::methodName($this->className, 'exportSalePerson');
        $data = $this->reportServices->exportSalePerson($reportSalePersonFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function exportGoodsInOut(ReportGoodsInOutFilterRequest $reportGoodsInOutFilterRequest){
        LogEx::methodName($this->className, 'exportGoodsInOut');
        $data = $this->reportServices->exportGoodsInOut($reportGoodsInOutFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}

