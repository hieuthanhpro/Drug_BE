<?php

/**
 * Created by PhpStorm.
 * User: hieu
 * Date: 12/18/2018
 * Time: 10:22 PM
 */


namespace App\Services;

use App\Repositories\AdsTracking\AdsTrackingRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\InvoiceDetail\InvoiceDetailRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Repositories\WarehouseLog\WarehouseLogRepositoryInterface;
use App\Repositories\Prescription\PrescriptionRepositoryInterface;
use App\Repositories\Debt\DebtRepositoryInterface;
use App\Repositories\Vouchers\VouchersRepositoryInterface;
use App\Repositories\Supplier\SupplierRepositoryInterface;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\Unit\UnitRepositoryInterface;
use App\Repositories\Customer\CustomerRepositoryInterface;
use App\Repositories\DrugMaster\DrugMasterRepositoryInterface;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\Users\UserRepositoryInterface;
use App\Services\ApiServiceGPP;
use Illuminate\Http\Request;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\CommonConstant;
use Illuminate\Support\Facades\Log;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;


/**
 * Class LoginService
 * @package App\Services
 */
class DashboardService
{
    protected $className = "DashboardService";

    protected $invoice;
    protected $invoice_detail;
    protected $warehouse;
    protected $warehouse_log;
    protected $prescription;
    protected $debt;
    protected $vouchers;
    protected $api_gpp;
    protected $supplier;
    protected $drug;
    protected $unit;
    protected $customer;
    protected $master_data;
    protected $order;
    protected $drug_store;
    protected $users;
    protected $ads_tracking;
    public function __construct(
        InvoiceRepositoryInterface       $invoice,
        InvoiceDetailRepositoryInterface $invoice_detail,
        WarehouseRepositoryInterface     $warehouse,
        WarehouseLogRepositoryInterface  $warehouse_log,
        PrescriptionRepositoryInterface  $prescription,
        DebtRepositoryInterface          $debt,
        VouchersRepositoryInterface      $vouchers,
        ApiServiceGPP                    $api_gpp,
        SupplierRepositoryInterface      $supplier,
        DrugRepositoryInterface          $drug,
        UnitRepositoryInterface          $unit,
        CustomerRepositoryInterface      $customer,
        DrugMasterRepositoryInterface    $master_data,
        OrderRepositoryInterface         $order,
        DrugStoreRepositoryInterface     $drug_store,
        UserRepositoryInterface          $users,
        AdsTrackingRepositoryInterface   $ads_tracking
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->invoice = $invoice;
        $this->invoice_detail = $invoice_detail;
        $this->warehouse = $warehouse;
        $this->warehouse_log = $warehouse_log;
        $this->prescription = $prescription;
        $this->debt = $debt;
        $this->vouchers = $vouchers;
        $this->api_gpp = $api_gpp;
        $this->supplier = $supplier;
        $this->drug = $drug;
        $this->unit = $unit;
        $this->customer = $customer;
        $this->master_data = $master_data;
        $this->order = $order;
        $this->drug_store = $drug_store;
        $this->users = $users;
        $this->ads_tracking = $ads_tracking;
    }

    public function addViews(Request $request)
    {
        LogEx::methodName($this->className, 'addView');

        $userInfo = $request->userInfo;
        $inputData = $request['banner'];
        $data = 0;
        DB::beginTransaction();
        try {
            $unitsListRequest = [];
            $request = array(
                'banner' => $request['banner'],
                'action_name' => $request['action_name'],
                'created_time' => $request['date_time'],
                'account' => $userInfo['id'],
            );
            array_push($unitsListRequest, $request);
            $this->ads_tracking->insertBatchWithChunk($unitsListRequest, sizeof($unitsListRequest));
            DB::commit();
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }
}
