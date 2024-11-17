<?php

namespace App\Services;

use App\Http\Requests\Invoice\InvoiceFilterRequest;
use App\LibExtension\CommonConstant;
use App\Models\Drug;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
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
use Illuminate\Http\Request;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use Illuminate\Support\Str;


/**
 * Class LoginService
 * @package App\Services
 */
class InvoiceService
{
    protected $className = "InvoiceService";

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
        UserRepositoryInterface          $users
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
    }

    public function createInvoice($data, $user, $drug_store_info)
    {
        LogEx::methodName($this->className, 'createInvoice');

        $invoice_type = $data['invoice_type'];
        switch ($invoice_type) {
            case "IV2":
            case "IV7":
                $result = $this->warehousing($data, $user, $drug_store_info);
                break;

            case "IV3":
                $result = $this->salesToGuests($data, $user, $invoice_type, true, $drug_store_info);
                break;
            case "IV4":
            case "IV8":
                $result = $this->salesToGuests($data, $user, $invoice_type, null, $drug_store_info);
                break;

            default:
                // Bán thuốc
                $result = $this->saveInvoiceIV1($data, $user, $drug_store_info);
        }
        return $result;
    }


    private function postInvoice($type, $data, $token)
    {
        LogEx::methodName($this->className, 'postInvoice');

        if ($type == "IV2" || $type == "IV7") {
            $method = "POST";
            $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/phieu_nhap';
        } elseif ($type == "IV1") {
            $method = "POST";
            $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/hoa_don';
        } elseif ($type == "IV4") {
            $method = "POST";
            $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/phieu_xuat';
        } elseif ($type == "IV3") {
            $method = "POST";
            $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/phieu_nhap';
        } elseif ($type == "IV8") {
            $method = "POST";
            $url = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/phieu_xuat';
        }
        try {
            $data = json_encode($data);
            $result = $this->api_gpp->callAPI($method, $url, $data, $token);
            if ($result) {
                $result_encode = json_decode($result);
                if (isset($result_encode->code) && $result_encode->code == 200) {
                    LogEx::notice("[DQG]" . $result);
                    return $result;
                } else {
                    LogEx::info($data);
                    LogEx::notice("[DQG]Lỗi liên thông. " . ($result_encode->message ?? ''));
                    return false;
                }
            } else {
                LogEx::info($data);
                LogEx::error("[DQG]ERROR" . $result);
                return false;
            }
        } catch (\Exception $e) {
            LogEx::info($data);
            LogEx::error("[DQG]EXCEPTION" . $e);
            return false;
        }
    }


    private function warehousing($data, $user, $drug_store_info)
    {
        LogEx::methodName($this->className, 'warehousing');

        $code = 'PN' . Utils::getSequenceDB('PN');
        $code_pn = 'PC-' . $code;
        if (!empty($data['customer_id'])) {
            $supplier = $this->supplier->findOneById($data['customer_id']);
        }
        if ($data['invoice_type'] == "IV2") {
            $loai_phieu = 1;
            $name = $supplier->name;
            $action = CommonConstant::ACTIION_TYPE['AC2'];
            // IV7
        } else {
            $loai_phieu = 3;
            $name = '';
            $action = CommonConstant::ACTIION_TYPE['AC7'];
        }

        $invoice_insert = Utils::coalesceMapping(
            $data,
            [
                'amount' => '-',
                'pay_amount' => '-',
                'invoice_type' => '-',
                'customer_id' => ['-', null],
                'discount' => ['-', 0],
                'description' => ['-', ''],
                'refer_id' => ['-', null],
                'receipt_date' => ['-', null],
                'vat_amount' => ['-', null],
                'created_at' => ['-', null],
                'supplier_invoice_code' => ['-', ''],
                'status' => ['-', null],
                'payment_status' => ['-', null],
            ]
        );
        $invoice_insert = array_merge($invoice_insert, [
            'drug_store_id' => $user->drug_store_id,
            'created_by' => $user->id,
            'warehouse_action_id' => $action,
            'invoice_code' => $code,
        ]);

        $detail_invoice = $data['invoice_detail'];

        $data_gpp = array(
            'ma_phieu' => $code,
            'ma_co_so' => $drug_store_info->base_code,
            'ngay_nhap' => str_replace('-', '', $data['receipt_date']),
            'loai_phieu_nhap' => $loai_phieu,
            'ghi_chu' => Utils::coalesce($data, 'description', ''),
            'ten_co_so_cung_cap' => $name
        );

        DB::beginTransaction();
        try {
            $insert = $this->invoice->create($invoice_insert);
            $last_id_invoice = $insert->id;

            // Trường hợp nhập hàng từ NCC thông qua đặt hàng
            if (isset($data['order_id'])) {
                $this->order->updateOneById($data['order_id'], array('status' => 'done', 'invoice_id' => $last_id_invoice));
            }

            $ware_house_log = array(
                'drug_store_id' => $user->drug_store_id,
                'user_id' => $user->id,
                'action_type' => $action,
                'invoice_id' => $last_id_invoice,
                'description' => ''
            );

            $type_exist = Utils::coalesce($data, 'type_exist', '');
            if ($type_exist != 1) {
                /*phi?u thu*/
                $vouchers = array(
                    'user_id' => $user->id,
                    'invoice_id' => $last_id_invoice,
                    'drug_store_id' => $user->drug_store_id,
                    'type' => 0,
                    'code' => $code_pn,
                    'amount' => $data['pay_amount'],
                    'supplier_id' => $data['customer_id'],
                    'invoice_type' => "IV2"
                );
                $this->vouchers->create($vouchers);
            }

            $this->warehouse_log->create($ware_house_log);
            foreach ($detail_invoice as $detail) {
                // Check số lượng == 0 hoặc lẻ
                if (!is_int($detail['quantity']) || $detail['quantity'] <= 0) {
                    DB::rollback();
                    return -2;
                }
                $drug_info = $this->drug->findOneById($detail['drug_id']);
                $unit_info = $this->unit->findOneById($detail['unit_id']);
                // minduc: ???
                $master_check = $this->master_data->findOneBy('drug_code', $drug_info->drug_code);
                if (!empty($master_check)) {
                    $data_gpp['chi_tiet'][] = array(
                        "ma_thuoc" => $drug_info->drug_code,
                        "ten_thuoc" => $drug_info->name,
                        "so_lo" => Utils::coalesce($detail, 'number', ''),
                        'han_dung' => str_replace('-', '', $detail['expiry_date']),
                        'so_dklh' => $drug_info->registry_number,
                        'so_luong' => $detail['quantity'],
                        "don_gia" => Utils::coalesce($detail, 'main_cost', ''),
                        "don_vi_tinh" => $unit_info->name
                    );
                }
                $item_invoice = Utils::coalesceMapping(
                    $detail,
                    [
                        'drug_id' => '-',
                        'unit_id' => '-',
                        'quantity' => '-',
                        'usage' => ['-', ''],
                        'number' => ['-', ''],
                        'expiry_date' => ['-', ''],
                        'cost' => ['pre_cost', 0],
                        'vat' => ['-', 0],
                        'exchange' => ['-', 1]
                    ]
                );

                $item_invoice['invoice_id'] = $last_id_invoice;
                // Create invoice data
                $this->invoice_detail->create($item_invoice);
                $check = $this->warehouse->findOneByCredentials(
                    Utils::coalesceMapping($detail, ['drug_id' => '-', 'number' => '-', 'unit_id' => '-'])
                );

                Log::info("Kiểm tra lỗi" . json_encode($check));
                if (!empty($check)) {
                    $main_cost = Utils::coalesce($detail, 'main_cost', null);
                    $current_cost = Utils::coalesce($detail, 'current_cost', null);
                    $this->warehouse->updateOneById($check->id, ['current_cost' => $current_cost, 'main_cost' => $main_cost]);
                    if ($check->is_basic == 'yes') {
                        $this->warehouse->updateAmount($detail['drug_id'], $detail['quantity'], true, $detail['number']);
                    } else {
                        $quantity = $detail['quantity'] * $check->exchange;
                        $this->warehouse->updateAmount($detail['drug_id'], $quantity, true, $detail['number']);
                    }
                } else {
                    $this->warehouse->creareByNumber($detail, $user->drug_store_id);
                }
            }
            if (!empty($drug_store_info->token) && !empty($data_gpp['chi_tiet'])) {
                $this->postInvoice("IV2", $data_gpp, $drug_store_info->token);
            }

            DB::commit();
            return $last_id_invoice;
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e);
            DB::rollback();
            return false;
        }
    }

    /**
     * api v3
     * from warehousing
    */
    private function warehousingV3($data, $user, $drug_store_info)
    {
        LogEx::methodName($this->className, 'warehousingV3');

        $code = 'PN' . Utils::getSequenceDB('PN');
        $code_pn = 'PC-' . $code;
        if (!empty($data['customer_id'])) {
            $supplier = $this->supplier->findOneById($data['customer_id']);
        }
        if ($data['invoice_type'] == "IV2") {
            $loai_phieu = 1;
            $name = !empty($supplier->name) ? $supplier->name : "";
            $action = CommonConstant::ACTIION_TYPE['AC2'];
            // IV7
        } else {
            $loai_phieu = 3;
            $name = '';
            $action = CommonConstant::ACTIION_TYPE['AC7'];
        }

        $invoice_insert = Utils::coalesceMapping(
            $data,
            [
                'amount' => '-',
                'pay_amount' => '-',
                'invoice_type' => '-',
                'customer_id' => ['-', null],
                'discount' => ['-', 0],
                'description' => ['-', ''],
                'refer_id' => ['-', null],
                'receipt_date' => ['-', null],
                'vat_amount' => ['-', null],
                'created_at' => ['-', null],
                'supplier_invoice_code' => ['-', ''],
                'status' => ['-', null],
                'payment_status' => ['-', null],
            ]
        );
        $invoice_insert = array_merge($invoice_insert, [
            'drug_store_id' => $user->drug_store_id,
            'created_by' => $user->id,
            'warehouse_action_id' => $action,
            'invoice_code' => $code,
        ]);

        $detail_invoice = $data['invoice_detail'];

        $data_gpp = array(
            'ma_phieu' => $code,
            'ma_co_so' => $drug_store_info->base_code,
            'ngay_nhap' => str_replace('-', '', $data['receipt_date']),
            'loai_phieu_nhap' => $loai_phieu,
            'ghi_chu' => Utils::coalesce($data, 'description', ''),
            'ten_co_so_cung_cap' => $name
        );

        DB::beginTransaction();
        try {
            $insert = $this->invoice->create($invoice_insert);
            $last_id_invoice = $insert->id;

            // Trường hợp nhập hàng từ NCC thông qua đặt hàng
            if (isset($data['order_id'])) {
                $this->order->updateOneById($data['order_id'], array('status' => 'done', 'invoice_id' => $last_id_invoice));
            }

            $ware_house_log = array(
                'drug_store_id' => $user->drug_store_id,
                'user_id' => $user->id,
                'action_type' => $action,
                'invoice_id' => $last_id_invoice,
                'description' => ''
            );

            $type_exist = Utils::coalesce($data, 'type_exist', '');
            if ($type_exist != 1) {
                /*phi?u thu*/
                $vouchers = array(
                    'user_id' => $user->id,
                    'invoice_id' => $last_id_invoice,
                    'drug_store_id' => $user->drug_store_id,
                    'type' => 0,
                    'code' => $code_pn,
                    'amount' => $data['pay_amount'],
                    'supplier_id' => $data['customer_id'],
                    'invoice_type' => "IV2"
                );
                $this->vouchers->create($vouchers);
            }

            $this->warehouse_log->create($ware_house_log);
            foreach ($detail_invoice as $detail) {
                // Check số lượng == 0 hoặc lẻ
                if (!is_int($detail['quantity']) || $detail['quantity'] <= 0) {
                    DB::rollback();
                    return -2;
                }
                $drug_info = $this->drug->findOneById($detail['drug_id']);
                $unit_info = $this->unit->findOneById($detail['unit_id']);
                // minduc: ???
                $master_check = $this->master_data->findOneBy('drug_code', $drug_info->drug_code);
                if (!empty($master_check)) {
                    $data_gpp['chi_tiet'][] = array(
                        "ma_thuoc" => $drug_info->drug_code,
                        "ten_thuoc" => $drug_info->name,
                        "so_lo" => Utils::coalesce($detail, 'number', ''),
                        'han_dung' => str_replace('-', '', $detail['expiry_date']),
                        'so_dklh' => $drug_info->registry_number,
                        'so_luong' => $detail['quantity'],
                        "don_gia" => Utils::coalesce($detail, 'main_cost', ''),
                        "don_vi_tinh" => $unit_info->name
                    );
                }
                $item_invoice = Utils::coalesceMapping(
                    $detail,
                    [
                        'drug_id' => '-',
                        'unit_id' => '-',
                        'quantity' => '-',
                        'usage' => ['-', ''],
                        'number' => ['-', ''],
                        'expiry_date' => ['-', ''],
                        'cost' => ['pre_cost', 0],
                        'vat' => ['-', 0],
                        'exchange' => ['-', 1]
                    ]
                );

                $item_invoice['invoice_id'] = $last_id_invoice;
                // Create invoice data
                $this->invoice_detail->create($item_invoice);
                $check = $this->warehouse->findOneByCredentials(
                    Utils::coalesceMapping($detail, ['drug_id' => '-', 'number' => '-', 'unit_id' => '-'])
                );

                Log::info("Kiểm tra lỗi" . json_encode($check));
                if (!empty($check)) {
                    $main_cost = Utils::coalesce($detail, 'main_cost', null);
                    $current_cost = Utils::coalesce($detail, 'current_cost', null);
                    $this->warehouse->updateOneById($check->id, ['current_cost' => $current_cost, 'main_cost' => $main_cost]);
                    if ($check->is_basic == 'yes') {
                        $this->warehouse->updateAmount($detail['drug_id'], $detail['quantity'], true, $detail['number']);
                    } else {
                        $quantity = $detail['quantity'] * $check->exchange;
                        $this->warehouse->updateAmount($detail['drug_id'], $quantity, true, $detail['number']);
                    }
                } else {
                    $this->warehouse->creareByNumber($detail, $user->drug_store_id);
                }
            }
            if (!empty($drug_store_info->token) && !empty($data_gpp['chi_tiet'])) {
                $this->postInvoice("IV2", $data_gpp, $drug_store_info->token);
            }

            DB::commit();
            return $last_id_invoice;
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e);
            DB::rollback();
            return false;
        }
    }

    public function syncDQGInvoice($invoiceId, $userInfo)
    {
        LogEx::methodName($this->className, 'syncDQGInvoice');
        if (isset($userInfo->username) && $userInfo->username !== '' && isset($userInfo->password) && $userInfo->password !== '') {
            LogEx::info("[DQG] sync invoice $userInfo->drug_store_id");
            try {
                shell_exec("/sphacy_gppbatch/sync.sh $userInfo->drug_store_id");
            } catch (\Exception $e) {
                LogEx::warning("[DQG] execute failed");
            }
        }
    }

    private function syncDQGInvoiceIV1($invoice)
    {
        LogEx::methodName($this->className, 'syncDQGInvoiceIV1');

        $drug_store_info = $this->drug_store->findOneById($invoice->drug_store_id);
        if (empty($drug_store_info)) {
            LogEx::warning("[DQG]Nhà thuốc không tồn tại. (drug_store_id={$invoice->drug_store_id},invoice_id={$invoice->id})");
            return false;
        }
        if (empty($drug_store_info->token)) {
            LogEx::warning("[DQG]Không có token Dược Quốc Gia. (drug_store_id={$drug_store_info->id},invoice_id={$invoice->id})");
            return false;
        }
        $invoice_details = $this->invoice_detail->findAllByCredentials(['invoice_id' => $invoice->id]);
        if (empty($invoice_details)) {
            LogEx::warning("[DQG]Chi tiết hoá đơn không tồn tại. (invoice_id={$invoice->id})");
            return false;
        }
        $user_info = $this->users->findOneById($invoice->created_by);
        $customer_info = $this->customer->findOneById($invoice->customer_id);
        $data_gpp = array(
            'ma_hoa_don' => $invoice->invoice_code,
            'ma_co_so' => $drug_store_info->base_code,
            'ngay_ban' => substr(str_replace('-', '', $invoice->receipt_date), 0, 8),
            'ho_ten_nguoi_ban' => $user_info->name ?? $invoice->created_by,
            'ho_ten_khach_hang' => $customer_info->name ?? 'Khách lẻ',
            'ghi_chu' => Utils::coalesce($invoice, 'description', '')
        );
        foreach ($invoice_details as $invoice_detail) {
            $drug_info = $this->drug->findOneById($invoice_detail->drug_id);
            $master_data = $this->master_data->findOneBy('drug_code', $drug_info->drug_code);
            if (!empty($master_data)) {
                $warehouse_basic = $this->warehouse->findOneByCredentials(['drug_id' => $invoice_detail->drug_id, 'is_check' => true, 'is_basic' => 'yes']);
                $unit_info = $this->unit->findOneById($warehouse_basic->unit_id);
                $data_gpp['hoa_don_chi_tiet'][] = array(
                    "ma_thuoc" => $drug_info->drug_code,
                    "ten_thuoc" => $drug_info->name,
                    "so_lo" => $invoice_detail->number,
                    'han_dung' => substr(str_replace('-', '', $invoice_detail->expiry_date), 0, 8),
                    'don_vi_tinh' => $unit_info->name,
                    'ham_luong' => $drug_info->substances,
                    'lieu_dung' => $invoice_detail->usage,
                    'so_dang_ky' => $drug_info->registry_number,
                    'so_luong' => $invoice_detail->quantity * $invoice_detail->exchange,
                    'don_gia' => $invoice_detail->cost / $invoice_detail->exchange,
                    'thanh_tien' => $invoice_detail->cost * $invoice_detail->quantity,
                    'ty_le_quy_doi' => 1
                );
            }
        }
        if (empty($data_gpp['hoa_don_chi_tiet'])) {
            LogEx::warning("[DQG]Không có dữ liệu cần đồng bộ. (invoice_id={$invoice->id})");
            return false;
        }
        return $this->postInvoice('IV1', $data_gpp, $drug_store_info->token);
    }

    private function syncDQGInvoiceIV2($invoice)
    {
        LogEx::methodName($this->className, 'syncDQGInvoiceIV2');

        $drug_store_info = $this->drug_store->findOneById($invoice->drug_store_id);
        if (empty($drug_store_info)) {
            LogEx::warning("[DQG]Nhà thuốc không tồn tại. (drug_store_id={$invoice->drug_store_id},invoice_id={$invoice->id})");
            return false;
        }
        if (empty($drug_store_info->token)) {
            LogEx::warning("[DQG]Không có token Dược Quốc Gia. (drug_store_id={$drug_store_info->id},invoice_id={$invoice->id})");
            return false;
        }
        $invoice_details = $this->invoice_detail->findAllByCredentials(['invoice_id' => $invoice->id]);
        if (empty($invoice_details)) {
            LogEx::warning("[DQG]Chi tiết hoá đơn không tồn tại. (invoice_id={$invoice->id})");
            return false;
        }
        $supplier = $this->supplier->findOneById($invoice->customer_id);
        $data_gpp = array(
            'ma_phieu' => $invoice->invoice_code,
            'ma_co_so' => $drug_store_info->base_code,
            'ngay_nhap' => substr(str_replace('-', '', $invoice->receipt_date), 0, 8),
            'loai_phieu_nhap' => 1,
            'ghi_chu' => Utils::coalesce($invoice, 'description', ''),
            'ten_co_so_cung_cap' => $supplier->name
        );
        foreach ($invoice_details as $invoice_detail) {
            $drug_info = $this->drug->findOneById($invoice_detail->drug_id);
            $master_data = $this->master_data->findOneBy('drug_code', $drug_info->drug_code);
            if (!empty($master_data)) {
                $warehouse_basic = $this->warehouse->findOneByCredentials(['drug_id' => $invoice_detail->drug_id, 'is_check' => true, 'is_basic' => 'yes']);
                $unit_info = $this->unit->findOneById($warehouse_basic->unit_id);
                $data_gpp['chi_tiet'][] = array(
                    "ma_thuoc" => $drug_info->drug_code,
                    "ten_thuoc" => $drug_info->name,
                    "so_lo" => $invoice_detail->number,
                    'han_dung' => substr(str_replace('-', '', $invoice_detail->expiry_date), 0, 8),
                    'so_dklh' => $drug_info->registry_number,
                    'so_luong' => $invoice_detail->quantity * $invoice_detail->exchange,
                    'don_gia' => $invoice_detail->cost / $invoice_detail->exchange,
                    "don_vi_tinh" => $unit_info->name
                );
            }
        }
        if (empty($data_gpp['chi_tiet'])) {
            LogEx::warning("[DQG]Không có dữ liệu cần đồng bộ. (invoice_id={$invoice->id})");
            return false;
        }
        return $this->postInvoice('IV2', $data_gpp, $drug_store_info->token);
    }

    // Bán hàng - IV1
    public function createInvoiceIV1($data, $user)
    {
        LogEx::methodName($this->className, 'createInvoiceIV1');

        $type = "IV1";
        $type_warehouse = null;

        if ($data['customer_id']) {
            $cutomer = $this->customer->findOneById($data['customer_id']);
        }


        $date = Carbon::now()->format('Y-m-d');

        $code = 'HD' . Utils::getSequenceDB('HD');
        $code_pcn = 'PT-' . $code;
        $action = CommonConstant::ACTIION_TYPE['AC1'];
        $type_vouchers = 1;
        $data['receipt_date'] = $date;


        $invoice_insert = array(
            'drug_store_id' => $user->drug_store_id,
            'amount' => $data['amount'],
            'pay_amount' => $data['pay_amount'],
            'created_by' => $user->id,
            'invoice_type' => $type,
            'warehouse_action_id' => $action,
            'customer_id' => Utils::coalesce($data, 'customer_id', null),
            'discount' => Utils::coalesce($data, 'discount', 0),
            'invoice_code' => $code,
            'description' => Utils::coalesce($data, 'description', ''),
            'status' => Utils::coalesce($data, 'status', null),
            'payment_status' => Utils::coalesce($data, 'payment_status', null),
            'refer_id' => Utils::coalesce($data, 'refer_id', null),
            'receipt_date' => Utils::coalesce($data, 'receipt_date', null),
            'vat_amount' => Utils::coalesce($data, 'vat_amount', null),
            'created_at' => Utils::coalesce($data, 'created_at', null),
            'image' => Utils::coalesce($data, 'image', null),
        );
        $detail_invoice = $data['invoice_detail'];

        $insert = $this->invoice->create($invoice_insert);
        $last_id_invoice = $insert->id;
        if (!empty($data['clinic'])) {
            $prescription = $data['clinic'];
            $prescription['invoice_id'] = $last_id_invoice;
            $prescription['code_invoice'] = $code;
            $this->prescription->create($prescription);
        }
        $ware_house_log = array(
            'drug_store_id' => $user->drug_store_id,
            'user_id' => $user->id,
            'action_type' => $action,
            'invoice_id' => $last_id_invoice,
            'description' => ''
        );
        /*phiếu thu*/
        $vouchers = array(
            'user_id' => $user->id,
            'invoice_id' => $last_id_invoice,
            'type' => $type_vouchers,
            'amount' => $data['pay_amount'],
            'drug_store_id' => $user->drug_store_id,
            'customer_id' => Utils::coalesce($data, 'customer_id', ''),
            'code' => $code_pcn,
            'invoice_type' => $type
        );

        /*Công nợ*/
        $this->warehouse_log->create($ware_house_log);
        $quantityNotEnoughFlag = false; // Dùng check bán quá
        foreach ($detail_invoice as $detail) {
            // Check số lượng == 0 hoặc lẻ
            if (!is_int($detail['quantity']) || $detail['quantity'] <= 0) {
                return -2;
            }
            $warehouse_data = $this->warehouse->findOneByCredentials(['unit_id' => $detail['unit_id'], 'drug_id' => $detail['drug_id'], 'number' => $detail['number']]);
            if ($warehouse_data['quantity'] < $detail['quantity']) {
                $quantityNotEnoughFlag = true;
                break;
            }
            $invoiceDetail = array(
                'invoice_id' => $last_id_invoice,
                'drug_id' => $detail['drug_id'],
                'unit_id' => $detail['unit_id'],
                'quantity' => $detail['quantity'],
                'number' => Utils::coalesce($detail, 'number', ''),
                'expiry_date' => Utils::coalesce($detail, 'expiry_date', ''),
                'cost' => $detail['cost'],
                'usage' => Utils::coalesce($detail, 'usage', ''),
                'vat' => Utils::coalesce($detail, 'vat', 0),
                'exchange' => !empty($detail['exchange']) ? $detail['exchange'] : (!empty($warehouse_data) && !empty($warehouse_data['exchange']) ? $warehouse_data['exchange'] : 1)
            );

            $this->invoice_detail->create($invoiceDetail);
        }

        if ($quantityNotEnoughFlag) {
            return -1;
        }

        LogEx::methodName('After Flag', '');

        return $last_id_invoice;
    }

    public function createInvoiceIV2IV7($data, $user)
    {
        LogEx::methodName($this->className, 'createInvoiceIV2IV7');

        $code = 'PN' . Utils::getSequenceDB('PN');
        // $code_pn = 'PC-' . $code;
        $action = CommonConstant::ACTIION_TYPE[$data['invoice_type'] == 'IV2' ? 'AC2' : 'AC7'];

        $recInvoice = Utils::coalesceMapping(
            $data,
            [
                'amount' => '-',
                'pay_amount' => '-',
                'invoice_type' => '-',
                'customer_id' => ['-', null],
                'discount' => ['-', 0],
                'description' => ['-', ''],
                'refer_id' => ['-', null],
                'receipt_date' => ['-', null],
                'vat_amount' => ['-', null],
                'created_at' => ['-', null],
                'supplier_invoice_code' => ['-', ''],
                'status' => ['-', null],
                'payment_status' => ['-', null],
                'payment_method' => ['-', null]
            ]
        );
        $recInvoice = array_merge($recInvoice, [
            'drug_store_id' => $user->drug_store_id,
            'created_by' => $user->id,
            'warehouse_action_id' => $action,
            'invoice_code' => $code,
        ]);

        $detail_invoice = $data['invoice_detail'];

        if ((isset($data['is_temp']) == false || $data['is_temp'] !== true) && isset($data['invoice_id'])) {
            $this->updateInvoiceDetailQuantity($data['invoice_id'], true, true, $recInvoice);
            $this->invoice_detail->deleteManyBy('invoice_id', $data['invoice_id']);
            //$this->invoice->updateOneById($data['invoice_id'], $recInvoice);
            $last_id_invoice = $data['invoice_id'];
        } else {
            $insert = $this->invoice->create($recInvoice);
            $last_id_invoice = $insert->id;
        }

        // Trường hợp nhập hàng từ NCC thông qua đặt hàng
        if (isset($data['order_id'])) {
            $this->order->updateOneById($data['order_id'], array('status' => 'done', 'invoice_id' => $last_id_invoice));
        }

        foreach ($detail_invoice as $detail) {
            // Check số lượng == 0 hoặc lẻ
            if (!is_int($detail['quantity']) || $detail['quantity'] <= 0) {
                return -2;
            }

            if (empty($detail['number'])) {
                return -3;
            }

            $warehouse_check_data = $this->warehouse->findOneByCredentials(['unit_id' => $detail['unit_id'], 'drug_id' => $detail['drug_id'], 'is_check' => true]);

            $detail['exchange'] = isset($warehouse_check_data) ? $warehouse_check_data->exchange : 1;

            $invoiceDetail = [
                "drug_id" => Utils::coalesce($detail, 'drug_id', null),
                "unit_id" => Utils::coalesce($detail, 'unit_id', null),
                "number" => Utils::coalesce($detail, 'number', ''),
                "expiry_date" => Utils::coalesce($detail, 'expiry_date', ''),
                "usage" => Utils::coalesce($detail, 'usage', null),
                "warehouse_id" => Utils::coalesce($detail, 'warehouse_id', null),
                "exchange" => Utils::coalesce($detail, 'exchange', 1),
                "quantity" => Utils::coalesce($detail, 'quantity', null),
                "vat" => Utils::coalesce($detail, 'vat', 0),
                "cost" => Utils::coalesce($detail, 'cost', 0),
                "combo_name" => Utils::coalesce($detail, 'combo_name', null),
                "org_cost" => Utils::coalesce($detail, 'org_cost', null),
                "mfg_date" => Utils::coalesce($detail, 'mfg_date', null),
                "warehouse_invoice_id" => Utils::coalesce($detail, 'warehouse_invoice_id', null),
                "note" => Utils::coalesce($detail, 'note', null),
                "discount_promotion" => Utils::coalesce($detail, 'discount_promotion', null),
                "is_gift" => Utils::coalesce($detail, 'is_gift', null)
            ];

            $warehouse_invoice_id = Utils::coalesce($detail, 'warehouse_invoice_id', null);
            $invoiceDetail['invoice_id'] = $last_id_invoice;

            if (!isset($detail['pre_cost'])) {
                $detail['pre_cost'] = $detail['cost'];
            }
            if (!isset($detail['main_cost'])) {
                $detail['main_cost'] = $detail['cost'] * (1 + $detail['vat'] / 100);
            }
            // Create invoice data
            //$this->invoice_detail->create($invoiceDetail);
            //$create_detail = InvoiceDetail::find($create_detail->id);
            //$create_detail->warehouse_invoice_id = $invoiceDetail['warehouse_invoice_id'];
            //$create_detail->save();

            //$create_detail = InvoiceDetail::create($invoiceDetail);
            //$create_detail->warehouse_invoice_id = $invoiceDetail['warehouse_invoice_id'];
            //$create_detail->save();
            $detail_id = DB::table('invoice_detail')->insertGetId($invoiceDetail);
            $update = InvoiceDetail::where('id', $detail_id)
                ->update(['warehouse_invoice_id' => $warehouse_invoice_id]);

            // Update warehouse data (update, insert)
            $recWarehouse = $this->warehouse->findOneByCredentials(
                ['drug_id' => $detail['drug_id'], 'number' => $detail['number'], 'unit_id' => $detail['unit_id']]
            );

            $vat = isset($detail['vat']) && is_numeric($detail['vat']) ? $detail['vat'] : 0;
            $current_cost_basic = $detail['current_cost'] / $detail['exchange'];
            $pre_cost_basic = $detail['cost'] / $detail['exchange'];
            $main_cost_basic = $pre_cost_basic * (1 + $vat / 100);

            // Update quantity of warehouse data
            if (empty($recWarehouse)) {
                // Insert new record to warehouse data
                $detail['quantity'] = 0;
                $this->warehouse->creareByNumber($detail, $user->drug_store_id);
            } else {
                $detail_quantity = $detail['quantity'];
                $now = Carbon::now()->format('Y-m-d H:i:s');
                DB::statement('
                    update warehouse set pre_cost = round(' . $pre_cost_basic . ' * exchange),
                    quantity = quantity + ' . $detail_quantity . ', main_cost = round(' . $main_cost_basic . ' * exchange),
                    expiry_date = ?, updated_at = \'' . $now . '\' where drug_id = ? and number = ?',
                    [$detail['expiry_date'], $detail['drug_id'], $detail['number']]
                );
            }

            $this->warehouse->updateCosts($detail['drug_id'], $pre_cost_basic, $main_cost_basic, $current_cost_basic);
        }

        // Write log for ImportIv7
        $ware_house_log = array(
            'drug_store_id' => $user->drug_store_id,
            'user_id' => $user->id,
            'action_type' => $action,
            'invoice_id' => $last_id_invoice,
            'description' => ''
        );
        $this->warehouse_log->create($ware_house_log);

        return $last_id_invoice;
    }

    public function updateInvoiceDetailQuantity($invoice_id, $reverse_flag = false, $ignore_negative = false, $data = [])
    {
        LogEx::methodName($this->className, 'updateInvoiceDetailQuantity');

        $invoice = $this->invoice->findOneById($invoice_id);
        if (empty($invoice)) {
            return false;
        }
        unset($data['invoice_code']);
        $this->invoice->updateOneById($invoice_id, array_filter($data));
        $invoice_details = $this->invoice_detail->findAllByCredentials(['invoice_id' => $invoice_id]);
        if (empty($invoice_details)) {
            return false;
        }
        switch ($invoice->invoice_type) {
            case 'IV1':
                $decrease_flag = true;
                break;
            case 'IV2':
            case 'IV7':
                $decrease_flag = false;
                break;
        }
        if ($reverse_flag) {
            $decrease_flag = !$decrease_flag;
        }
        foreach ($invoice_details as $invoice_detail) {
            $updateQuantityResult = $this->warehouse->updateInvoiceAmount(
                $invoice_detail->drug_id,
                $invoice_detail->number,
                $invoice_detail->quantity * $invoice_detail->exchange,
                $decrease_flag,
                $ignore_negative
            );
            if (!$updateQuantityResult) {
                return false;
            }
        }
        return true;
    }

    private function salesToGuests($data, $user, $type, $type_warehouse, $drug_store_info)
    {
        LogEx::methodName($this->className, 'salesToGuests');

        /* insert database */
        if ($type != "IV8") {
            $cutomer = $this->customer->findOneById($data['customer_id']);
            $supplier = $this->supplier->findOneById($data['customer_id']);
        }

        $date = Carbon::now()->format('Y-m-d');

        if ($type == 'IV1') {
            $code = 'HD' . Utils::getSequenceDB('HD');
            $code_pcn = 'PT-' . $code;
            $action = CommonConstant::ACTIION_TYPE['AC1'];
            $type_vouchers = 1;
            $data_gpp = array(
                'ma_hoa_don' => $code,
                'ma_co_so' => $drug_store_info->base_code,
                'ngay_ban' => str_replace('-', '', $date),
                'ho_ten_nguoi_ban' => $user->name,
                'ho_ten_khach_hang' => $cutomer->name ?? '',
            );
            $data['receipt_date'] = $date;
        } elseif ($type == 'IV3') {
            $code = 'HDT' . Utils::getSequenceDB('HDT');
            $code_pcn = 'PC-' . $code;
            $data_gpp = array(
                'ma_phieu' => $code,
                'ma_co_so' => $drug_store_info->base_code,
                'ngay_nhap' => str_replace('-', '', $data['receipt_date']),
                'loai_phieu_nhap' => 1,
                'ghi_chu' => Utils::coalesce($data, 'description', ''),
                'ten_co_so_cung_cap' => $supplier->name ?? '',
            );
            $type_vouchers = 0;
            $action = CommonConstant::ACTIION_TYPE['AC3'];
        } elseif ($type == 'IV4') {
            $code = 'PTH' . Utils::getSequenceDB('PTH');
            $code_pcn = 'PT-' . $code;
            $data_gpp = array(
                'ma_phieu' => $code,
                'ma_co_so' => $drug_store_info->base_code,
                'ngay_xuat' => str_replace('-', '', $data['receipt_date']),
                'loai_phieu_xuat' => 2,
                'ghi_chu' => Utils::coalesce($data, 'description', ''),
                'ten_co_so_cung_cap' => $supplier->name ?? '',
            );
            $type_vouchers = 1;
            $action = CommonConstant::ACTIION_TYPE['AC4'];
        } elseif ($type == "IV8") {
            $code = 'HD' . Utils::getSequenceDB('HD');
            $code_pcn = 'PC-' . $code;
            $type_vouchers = 1;
            $data_gpp = array(
                'ma_phieu' => $code,
                'ma_co_so' => $drug_store_info->base_code,
                'ngay_xuat' => str_replace('-', '', $data['receipt_date']),
                'loai_phieu_xuat' => 3,
                'ghi_chu' => Utils::coalesce($data, 'description', ''),
                'ten_co_so_cung_cap' => $supplier->name ?? '',
            );
            $action = CommonConstant::ACTIION_TYPE['AC8'];
        }
        $invoice_insert = array(
            'drug_store_id' => $user->drug_store_id,
            'amount' => $data['amount'],
            'pay_amount' => $data['pay_amount'],
            'created_by' => $user->id,
            'invoice_type' => $type,
            'warehouse_action_id' => $action,
            'customer_id' => Utils::coalesce($data, 'customer_id', null),
            'discount' => Utils::coalesce($data, 'discount', 0),
            'invoice_code' => $code,
            'description' => Utils::coalesce($data, 'description', ''),
            'status' => Utils::coalesce($data, 'status', null),
            'payment_status' => Utils::coalesce($data, 'payment_status', null),
            'refer_id' => Utils::coalesce($data, 'refer_id', null),
            'receipt_date' => Utils::coalesce($data, 'receipt_date', null),
            'vat_amount' => Utils::coalesce($data, 'vat_amount', null),
            'created_at' => Utils::coalesce($data, 'created_at', null),
            'image' => Utils::coalesce($data, 'image', null),
            'method' => Utils::coalesce($data, 'method', null),
            'payment_method' => Utils::coalesce($data, 'payment_method', null),
        );

        if (count($data['invoice_detail']) == 0) return -10;

        $detail_invoice = $data['invoice_detail'];

        DB::beginTransaction();
        try {
            $insert = $this->invoice->create($invoice_insert);
            $last_id_invoice = $insert->id;
            if (!empty($data['clinic']) && $type == "IV1") {
                $linic = $data['clinic'];
                $linic['invoice_id'] = $last_id_invoice;
                $linic['code_invoice'] = $code;
                $this->prescription->create($linic);
            }
            $ware_house_log = array(
                'drug_store_id' => $user->drug_store_id,
                'user_id' => $user->id,
                'action_type' => $action,
                'invoice_id' => $last_id_invoice,
                'description' => ''
            );
            /*phiếu thu*/
            $vouchers = array(
                'user_id' => $user->id,
                'invoice_id' => $last_id_invoice,
                'type' => $type_vouchers,
                'amount' => $data['pay_amount'],
                'drug_store_id' => $user->drug_store_id,
                'customer_id' => Utils::coalesce($data, 'customer_id', null),
                'code' => $code_pcn,
                'invoice_type' => $type
            );
            if ($type != "IV8") {
                $this->vouchers->create($vouchers);
            }
            // Công nợ
            $this->warehouse_log->create($ware_house_log);
            $flag = false; // Dùng check bán quá
            foreach ($detail_invoice as $value) {
                // Check số lượng == 0 hoặc lẻ
                if (!is_int($value['quantity']) || $value['quantity'] <= 0) {
                    DB::rollback();
                    return -2;
                }
                $is_basic = $this->warehouse->findOneByCredentials([
                    'unit_id' => $value['unit_id'],
                    'drug_id' => $value['drug_id'],
                    'number' => $value['number']
                    ]);

                $item_invoice = array(
                    'invoice_id' => $last_id_invoice,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'number' => Utils::coalesce($value, 'number', ''),
                    'expiry_date' => Utils::coalesce($value, 'expiry_date', ''),
                    'cost' => $value['cost'],
                    'combo_name' => Utils::coalesce($value, 'combo_name', ''),
                    'org_cost' => Utils::coalesce($value, 'org_cost', 0),
                    'usage' => Utils::coalesce($value, 'usage', ''),
                    'vat' => Utils::coalesce($value, 'vat', 0),
                    'exchange' => !empty($value['exchange']) ? $value['exchange'] : 1
                );
                $drug_info = $this->drug->findOneById($value['drug_id']);
                $unit_info = $this->unit->findOneById($value['unit_id']);
                $master_check = $this->master_data->findOneBy('drug_code', $drug_info->drug_code);
                if (!empty($master_check)) {
                    if ($type == "IV1") {
                        $data_gpp['hoa_don_chi_tiet'][] = array(
                            "ma_thuoc" => $drug_info->drug_code,
                            "ten_thuoc" => $drug_info->name,
                            "so_lo" => Utils::coalesce($value, 'number', ''),
                            'han_dung' => str_replace('-', '', $value['expiry_date']),
                            'so_dklh' => $drug_info->registry_number,
                            'so_luong' => $value['quantity'],
                            "don_gia" => $value['cost'],
                            "don_vi_tinh" => $unit_info->name,
                            'ham_luong' => $drug_info->substances,
                            'lieu_dung' => $value['usage'],
                            'so_dang_ky' => $drug_info->registry_number,
                            'so_luong' => $value['quantity'],
                            'don_gia' => $value['cost'],
                            'thanh_tien' => $value['cost'],
                            'duong_dung' => Utils::coalesce($value, 'usage', ''),
                            'ty_le_quy_doi' => $is_basic->exchange ?? null
                        );
                    } elseif ($type == "IV4") {
                        $data_gpp['chi_tiet'][] = array(
                            "ma_thuoc" => $drug_info->drug_code,
                            "ten_thuoc" => $drug_info->name,
                            "so_lo" => Utils::coalesce($value, 'number', ''),
                            'han_dung' => str_replace('-', '', $value['expiry_date']),
                            'so_dklh' => $drug_info->registry_number,
                            'so_luong' => $value['quantity'],
                            "don_gia" => $value['cost'],
                            "don_vi_tinh" => $unit_info->name
                        );
                    } elseif ($type == "IV3") {
                        $data_gpp['chi_tiet'][] = array(
                            "ma_thuoc" => $drug_info->drug_code,
                            "ten_thuoc" => $drug_info->name,
                            "so_lo" => Utils::coalesce($value, 'number', ''),
                            'han_dung' => str_replace('-', '', $value['expiry_date']),
                            'so_dklh' => $drug_info->registry_number,
                            'so_luong' => $value['quantity'],
                            "don_gia" => $value['cost'],
                            "don_vi_tinh" => $unit_info->name
                        );
                    } elseif ($type == "IV8") {
                        $data_gpp['chi_tiet'][] = array(
                            "ma_thuoc" => $drug_info->drug_code,
                            "ten_thuoc" => $drug_info->name,
                            "so_lo" => Utils::coalesce($value, 'number', ''),
                            'han_dung' => str_replace('-', '', $value['expiry_date']),
                            'so_dklh' => $drug_info->registry_number,
                            'so_luong' => $value['quantity'],
                            "don_gia" => $value['cost'],
                            "don_vi_tinh" => $unit_info->name
                        );
                    }
                }

                $this->invoice_detail->create($item_invoice);
                if (($is_basic->is_basic ?? null) == 'yes') {
                    $isUpdateQuantitySuccess = $this->warehouse->updateAmount($value['drug_id'], $value['quantity'], $type_warehouse, $value['number'], true);
                } else {
                    if ($is_basic->exchange ?? null) {
                        $quantity = $value['quantity'] * $is_basic->exchange;
                        $isUpdateQuantitySuccess = $this->warehouse->updateAmount($value['drug_id'], $quantity, $type_warehouse, $value['number'], true);
                    }
                }
                if (!empty($isUpdateQuantitySuccess)) {
                    $flag = true;
                    break;
                }
            }

            if ($flag) {
                DB::rollback();
                return -1;
            }

            LogEx::methodName('After Flag', '');
            if (!empty($drug_store_info->token)) {
                LogEx::methodName('Token exist', '');
                if (!empty($data_gpp['hoa_don_chi_tiet']) || !empty($data_gpp['chi_tiet'])) {
                    LogEx::methodName('hoa_don_chi_tiet', '-  postInvoice');

                    $result_gpp = $this->postInvoice($type, $data_gpp, $drug_store_info->token);
                }
            }
            DB::commit();
            return $last_id_invoice;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    // Bán hàng - IV1 [replaced by createInvoiceIV1]
    private function saveInvoiceIV1($data, $user, $drug_store_info)
    {
        LogEx::methodName($this->className, 'saveInvoiceIV1');

        $type = "IV1";
        $type_warehouse = null;

        if (!empty($data['invoice_id'])) {
            $last_id_invoice = $data['invoice_id'];
        } else {
            if ($data['customer_id']) {
                $cutomer = $this->customer->findOneById($data['customer_id']);
            }

            $date = Carbon::now()->format('Y-m-d');
            $code = !empty($data['invoice_code']) ? $data['invoice_code'] : 'HD' . Utils::getSequenceDB('HD');
            $code_pcn = 'PT-' . $code;
            $action = CommonConstant::ACTIION_TYPE['AC1'];
            $type_vouchers = 1;
            $data_gpp = array(
                'ma_hoa_don' => $code,
                'ma_co_so' => $drug_store_info->base_code,
                'ngay_ban' => str_replace('-', '', $date),
                'ho_ten_nguoi_ban' => $user->name,
                'ho_ten_khach_hang' => $cutomer->name ?? '',
            );
            $data['receipt_date'] = $date;

            $invoice_insert = array(
                'drug_store_id' => $user->drug_store_id,
                'supplier_invoice_code' => Utils::coalesce($data, 'supplier_invoice_code', null),
                'method' => Utils::coalesce($data, 'method', null),
                'payment_method' => Utils::coalesce($data, 'payment_method', null),
                'amount' => $data['amount'],
                'pay_amount' => $data['pay_amount'],
                //'date' => Utils::coalesce($data, 'date', null),
                //'debt' => Utils::coalesce($data, 'debt', 0),
                'created_by' => $user->id,
                'invoice_type' => $type,
                'warehouse_action_id' => $action,
                'customer_id' => Utils::coalesce($data, 'customer_id', 0),
                'discount' => Utils::coalesce($data, 'discount', 0),
                'invoice_code' => $code,
                'description' => Utils::coalesce($data, 'description', ''),
                'status' => Utils::coalesce($data, 'status', 'processing'),
                'payment_status' => Utils::coalesce($data, 'payment_status', null),
                'refer_id' => Utils::coalesce($data, 'refer_id', null),
                'receipt_date' => Utils::coalesce($data, 'receipt_date', null),
                'vat_amount' => Utils::coalesce($data, 'vat_amount', 0),
                'image' => Utils::coalesce($data, 'image', null),
                'source' => Utils::coalesce($data, 'source', null),
                //'discount_rate' => Utils::coalesce($data, 'discount_rate', 0),
                'discount_promotion' => Utils::coalesce($data, 'discount_promotion', 0),
                'sale_id' => Utils::coalesce($data, 'sale_id', 0),
                //'sale_name' =>  Utils::coalesce($data, 'sale_name', 0),
                'shipping_status' => Utils::coalesce($data, 'shipping_status', 'processing'),
                //'total_money' => Utils::coalesce($data, 'total_money', 0),
                'customer_excel' => Utils::coalesce($data, 'customer_excel', 0),
                'is_import' => Utils::coalesce($data, 'is_import', 0)
            );

            $detail_invoice = $data['invoice_detail'];
        }

        DB::beginTransaction();
        try {
            if (!empty($data['invoice_id'])) {
                $invoice = Invoice::find($last_id_invoice);
                $invoice->status = 'done';
                $invoice->source = Utils::coalesce($data, 'source', null);
                $invoice->save();
            } else {
                //$insert = $this->invoice->create($invoice_insert);
                //$last_id_invoice = $insert->id;
                $insert = DB::table('invoice')->insertGetId($invoice_insert);
                $last_id_invoice = $insert;
                if (!empty($data['clinic']) && $type == "IV1") {
                    $linic = $data['clinic'];
                    $linic['invoice_id'] = $last_id_invoice;
                    $linic['code_invoice'] = $code;
                    $this->prescription->create($linic);
                }
                $ware_house_log = array(
                    'drug_store_id' => $user->drug_store_id,
                    'user_id' => $user->id,
                    'action_type' => $action,
                    'invoice_id' => $last_id_invoice,
                    'description' => ''
                );
                /*phiếu thu*/
                $vouchers = array(
                    'user_id' => $user->id,
                    'invoice_id' => $last_id_invoice,
                    'type' => $type_vouchers,
                    'amount' => $data['pay_amount'],
                    'drug_store_id' => $user->drug_store_id,
                    'customer_id' => Utils::coalesce($data, 'customer_id', ''),
                    'code' => $code_pcn,
                    'invoice_type' => $type
                );

                /*C�ng n?*/
                $this->warehouse_log->create($ware_house_log);
                $flag = false; // Dùng check bán quá
                foreach ($detail_invoice as $value) {
                    // Check số lượng == 0 hoặc lẻ
                    if (!is_int($value['quantity']) || $value['quantity'] <= 0) {
                        DB::rollback();
                        return -2;
                    }
                    $is_basic = (!empty($value['is_import'])) ?
                        null :
                        $this->warehouse
                            ->findOneByCredentials([
                                'unit_id' => $value['unit_id'],
                                'drug_id' => $value['drug_id'],
                                'number' => $value['number']
                            ]);

                    $drug_id = null;

                    if (!empty($value['drug_code'])) {
                        $drug_id = Drug::where('drug_store_id', $drug_store_info->id)
                            ->where('drug_code', $value['drug_code'])
                            ->first()->id;
                    }

                    $item_invoice = array(
                        'invoice_id' => $last_id_invoice,
                        'drug_id' => $value['drug_id'] ?? $drug_id,
                        'unit_id' => $value['unit_id'],
                        'quantity' => $value['quantity'],
                        'number' => Utils::coalesce($value, 'number', ''),
                        'expiry_date' => Utils::coalesce($value, 'expiry_date', ''),
                        'cost' => $value['cost'],
                        'usage' => Utils::coalesce($value, 'usage', ''),
                        'vat' => Utils::coalesce($value, 'vat', 0),
                        'exchange' => !empty($value['exchange']) ? $value['exchange'] : 1
                    );

                    if (!empty($value['is_import'])) $master_check = $this->master_data->findOneBy('drug_code', $value['drug_code']);

                    if (!empty($value['drug_id'])) {
                        $drug_info = $this->drug->findOneById($value['drug_id']);
                        $master_check = $this->master_data->findOneBy('drug_code', $drug_info->drug_code);
                    }

                    if (!empty($value['unit_id'])) $unit_info = $this->unit->findOneById($value['unit_id']);

                    if (!empty($master_check)) {
                        $data_gpp['hoa_don_chi_tiet'][] = array(
                            "ma_thuoc" => !empty($value['is_import']) ? $value['drug_code']: $drug_info->drug_code,
                            "ten_thuoc" => !empty($value['is_import']) ? $value['drug_name']: $drug_info->name,
                            "so_lo" => Utils::coalesce($value, 'number', ''),
                            'han_dung' => str_replace('-', '', $value['expiry_date']),
                            'so_dklh' => !empty($value['is_import']) ? $value['registry_number'] : $drug_info->registry_number,
                            'so_luong' => $value['quantity'],
                            "don_gia" => $value['cost'],
                            "don_vi_tinh" => !empty($value['unit_name']) ? $value['unit_name'] : $unit_info->name,
                            'ham_luong' => !empty($value['is_import']) ? $value['substances'] : $drug_info->substances,
                            'lieu_dung' => !empty($value['usage']) ? $value['usage'] : "",
                            'so_dang_ky' => !empty($value['is_import']) ? $value['registry_number'] : $drug_info->registry_number,
                            'so_luong' => $value['quantity'],
                            'don_gia' => $value['cost'],
                            'thanh_tien' => $value['cost'],
                            'duong_dung' => Utils::coalesce($value, 'usage', ''),
                            'ty_le_quy_doi' => !empty($value['is_import']) ? $value['registry_number'] : $is_basic->exchange
                        );
                    }

                    $this->invoice_detail->create($item_invoice);
                    if (!empty($is_basic)) {
                        if ($is_basic->is_basic == 'yes') {
                            $isUpdateQuantitySuccess = $this->warehouse
                                ->updateAmount(
                                    $value['drug_id'],
                                    $value['quantity'],
                                    $type_warehouse,
                                    $value['number'],
                                    true
                                );
                        } else {
                            $quantity = $value['quantity'] * $is_basic->exchange;
                            $isUpdateQuantitySuccess = $this->warehouse
                                ->updateAmount(
                                    $value['drug_id'],
                                    $quantity,
                                    $type_warehouse,
                                    $value['number'],
                                    true
                                );
                        }
                        if (!$isUpdateQuantitySuccess) {
                            $flag = true;
                            break;
                        }
                    }
                }


                if ($flag) {
                    DB::rollback();
                    return -1;
                }

                LogEx::methodName('After Flag', '');
                if (!empty($drug_store_info->token)) {
                    LogEx::methodName('Token exist', '');
                    if (!empty($data_gpp['hoa_don_chi_tiet']) || !empty($data_gpp['chi_tiet'])) {
                        LogEx::methodName('hoa_don_chi_tiet', '-  postInvoice');

                        $result_gpp = $this->postInvoice($type, $data_gpp, $drug_store_info->token);
                    }
                }
            }
            DB::commit();

            return $last_id_invoice;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);

            return false;
        }
    }

    public function updateWarehouse($invoice_id, $detail_invoice)
    {
        LogEx::methodName($this->className, 'updateWarehouse');

        /*lấy số lượng đã bán*/
        $total_pay = 0;
        $tmp_total = 0;
        $drug_pay = DB::table('invoice_detail')
            ->select(
                'invoice_detail.*'
            )
            ->join('invoice', 'invoice.id', 'invoice_detail.invoice_id')
            ->where('invoice.invoice_type', "IV1")
            ->where('invoice_detail.drug_id', $detail_invoice['drug_id'])
            ->where('invoice_detail.number', $detail_invoice['number'])
            ->get();


        $tmp_detail = $this->invoice_detail->findManyByCredentials(['number' => $detail_invoice['number'], 'drug_id' => $detail_invoice['drug_id']])->toArray();
        if (!empty($tmp_detail)) {
            foreach ($tmp_detail as $item) {
                if ($item['invoice_id'] != $invoice_id) {
                    $tmp_exchange = $this->warehouse->findOneByCredentials(['drug_id' => $item['drug_id'], 'unit_id' => $item['unit_id'], 'is_check' => 1]);
                    $tmp_total = $tmp_total + $item['quantity'] * $tmp_exchange->exchange;
                }
            }
        }
        if (!empty($drug_pay)) {
            foreach ($drug_pay as $item) {
                $exchang = $this->warehouse->findOneByCredentials(['drug_id' => $item->drug_id, 'unit_id' => $item->unit_id, 'is_check' => 1]);
                $total_pay = $total_pay + $item->quantity * $exchang->exchange;
            }
        }
        $exchange_update = $this->warehouse->findOneByCredentials(['drug_id' => $detail_invoice['drug_id'], 'is_check' => 1, 'unit_id' => $detail_invoice['unit_id']]);

        $quantity_update = $detail_invoice['quantity'] * $exchange_update->exchange;
        if ($total_pay > $quantity_update) {
            // Trường hợp bán quá số lượng nhập
            return false;
        } else {
            $ware_house = $this->warehouse->findManyByCredentials(['drug_id' => $detail_invoice['drug_id'], 'number' => $detail_invoice['number']])->toArray();

            if (!empty($ware_house)) {
                // Fix bug sai giá (do đẩy lên giá của đơn vị ko phải cơ bản mà ko chia cho exchange của đơn vị đó)
                $warehouseBasicInfo = \App\Models\Warehouse::where('drug_id', $detail_invoice['drug_id'])
                    ->where('unit_id', $detail_invoice['unit_id'])
                    ->whereNull('number')
                    ->first();

                if (empty($warehouseBasicInfo)) {
                    return false;
                }

                $quantity_save = $quantity_update;
                foreach ($ware_house as $value) {
                    if ($value['is_basic'] == 'yes') {
                        $quantity = $value['quantity'] + $quantity_save;
                        // $current_cost = $detail_invoice['current_cost'];
                        $current_cost = $detail_invoice['current_cost'] / $warehouseBasicInfo->exchange; // lấy giá truyền lên chi cho exchange để lấy giá đơn vị cơ bản
                        $this->warehouse->updateOneById($value['id'], ['current_cost' => $current_cost, 'quantity' => $quantity, 'expiry_date' => $detail_invoice['expiry_date']]);
                    } else {
                        $quantity = $value['quantity'] + $quantity_save / $value['exchange'];
                        // $current_cost = $detail_invoice['current_cost'] * $value['exchange'];
                        $current_cost = ($detail_invoice['current_cost'] / $warehouseBasicInfo->exchange) * $value['exchange'];
                        $this->warehouse->updateOneById($value['id'], ['current_cost' => $current_cost, 'quantity' => $quantity, 'expiry_date' => $detail_invoice['expiry_date']]);
                    }
                    // Ghi log
                    $log = new \App\Models\NewWarehouseLog;
                    $ref = \Request::getRequestUri();
                    $action = 'updateWarehouse';
                    $oldVal = $value['quantity'];
                    $newVal = $quantity;
                    $desc = "Thay đổi quantity từ {$oldVal} sang {$newVal} (TH update, đã có trong kho)";
                    $log->pushQuantityLog($value['id'], $value['drug_store_id'], $ref, $action, $oldVal, $newVal, $desc);
                }
            } else {
                $quantity_save = $quantity_update - $total_pay;
                $house_base = $this->warehouse->findManyByCredentials(['drug_id' => $detail_invoice['drug_id'], 'is_check' => 1])->toArray();
                foreach ($house_base as $value) {
                    $quantity = $quantity_save;
                    if ($value['is_basic'] == 'yes') {
                        $save_house = array(
                            'drug_store_id' => $detail_invoice['drug_store_id'],
                            'drug_id' => $value['drug_id'],
                            'unit_id' => $value['unit_id'],
                            'is_basic' => $value['is_basic'],
                            'exchange' => $value['exchange'],
                            'number' => $detail_invoice['number'],
                            'quantity' => $quantity,
                            'main_cost' => $detail_invoice['main_cost'],
                            'current_cost' => $detail_invoice['current_cost'],
                            'expiry_date' => $detail_invoice['expiry_date'],
                            'is_check' => 0
                        );
                    } else {
                        $save_house = array(
                            'drug_store_id' => $detail_invoice['drug_store_id'],
                            'drug_id' => $value['drug_id'],
                            'unit_id' => $value['unit_id'],
                            'is_basic' => $value['is_basic'],
                            'number' => $detail_invoice['number'],
                            'exchange' => $value['exchange'],
                            'quantity' => $quantity / $value['exchange'],
                            'main_cost' => $detail_invoice['main_cost'] * $value['exchange'],
                            'current_cost' => $detail_invoice['current_cost'] * $value['exchange'],
                            'expiry_date' => $detail_invoice['expiry_date'],
                            'is_check' => 0
                        );
                    }
                    $this->warehouse->create($save_house);

                    // Ghi log
                    $log = new \App\Models\NewWarehouseLog;
                    $ref = \Request::getRequestUri();
                    $action = 'updateWarehouse';
                    $oldVal = 0;
                    $newVal = ($value['is_basic'] == 'yes') ? $quantity : $quantity . "/" . $value['exchange'];
                    $desc = "Thay đổi quantity từ {$oldVal} sang {$newVal} (TH tạo mới, drug_id: {$value['drug_id']}, unit_id: {$value['unit_id']}, number: {$detail_invoice['number']})";
                    $log->pushQuantityLog(0, $value['drug_store_id'], $ref, $action, $oldVal, $newVal, $desc);
                }
            }

            return true;
        }
    }


    public function updateInvoice($id, $data_detail, $data_change)
    {
        LogEx::methodName($this->className, 'updateInvoice');

        DB::beginTransaction();
        try {
            $this->invoice->updateOneById($id, $data_change);
            $this->invoice_detail->deleteManyBy('invoice_id', $id);
            foreach ($data_detail as $value) {
                $item_invoice = array(
                    'invoice_id' => $id,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'usage' => Utils::coalesce($value['usage'], ''),
                    'number' => Utils::coalesce($value['number'], ''),
                    'expiry_date' => Utils::coalesce($value['expiry_date'], ''),
                    'cost' => $value['cost'],
                    'vat' => Utils::coalesce($value['vat'], 0)
                );
                $this->invoice_detail->create($item_invoice);
            }
            DB::commit();
            return $id;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    public function sell(Request $request)
    {
        LogEx::methodName($this->className, 'sell');

        $userInfo = $request->userInfo;
        $inputData = $request->input();
        $data = 0;
        DB::beginTransaction();
        try {
            if (count($inputData['prescriptions']) > 0) {
                $img_base64 = $inputData['prescriptions'][0]['image'] ?? '';
                if (!empty($img_base64)) {
                    $img = Utils::generateImageFromBase64($img_base64);
                    $inputData['image'] = url("upload/images/" . $img);
                    $inputData['prescriptions'][0]['image'] = $inputData['image'];
                }
            }
            Utils::createTempTableFromRequestInput($inputData);
            $data = DB::select('select f_create_invoice_iv1_sell(?, ?) as result', [$userInfo->drug_store_id, $userInfo->id]);
            $data = $data[0]->result;
            if ($data < 0) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
            $this->syncDQGInvoice($data, $userInfo);
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }

    /**
     * api v3
     * from sell
    */
    public function sellV3(Request $request)
    {
        LogEx::methodName($this->className, 'sell');

        $userInfo = $request->userInfo;
        $inputData = $request->input();
        $data = 0;
        DB::beginTransaction();
        try {
            if (count($inputData['prescriptions']) > 0) {
                $img_base64 = $inputData['prescriptions'][0]['image'] ?? '';
                if (!empty($img_base64)) {
                    $img = Utils::generateImageFromBase64($img_base64);
                    $inputData['image'] = url("upload/images/" . $img);
                    $inputData['prescriptions'][0]['image'] = $inputData['image'];
                }
            }

            $data = $this->createInvoiceIv1Sell($inputData, $userInfo, $userInfo->drug_store_id);
//            $data = $data[0]->result;

            if ($data < 0) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
            $this->syncDQGInvoice($data, $userInfo);
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }

    /**
     * api v3
     * from f_create_invoice_iv1_sell on v1
    */
    // Bán hàng - IV1
    private function createInvoiceIv1Sell($data, $user, $drug_store_info)
    {
        LogEx::methodName($this->className, 'saveInvoiceIV1');

        $type = "IV1";
        $code = 'HD' . Utils::getSequenceDB('HD');
        $now = Carbon::now()->toDateTimeString();
        $type_warehouse = null;
        $action = CommonConstant::ACTIION_TYPE['AC1'];
        $type_vouchers = 1;
        $code_pcn = 'PT-' . $code;

        $invoice_insert = array(
            "drug_store_id" => $drug_store_info,
            "invoice_code" => $code,
            "invoice_type" => $type,
            "warehouse_action_id" => "Bán hàng cho khách",
            "customer_id" => Utils::coalesce($data, 'client', null),
            "amount" => Utils::coalesce($data, 'total_money', null),
            "vat_amount" => Utils::coalesce($data, 'total_vat', null),
            "pay_amount" => Utils::coalesce($data, 'client_pay', null),
            "discount" => Utils::coalesce($data, 'total_discount', null),
            "created_by" => $user->id,
            "description" => Utils::coalesce($data, 'note', null),
            "status" => Utils::coalesce($data, 'status', 'done'),
            "payment_status" => Utils::coalesce($data, 'payment_status', 'paid'),
            "image" => Utils::coalesce($data, 'image', null),
            "receipt_date" => Utils::coalesce($data, 'date', null),
            "method" => Utils::coalesce($data, 'sales_method', null),
            "payment_method" => Utils::coalesce($data, 'payment_method', null),
            "created_at" => $now,
            "updated_at" => $now
        );
        $detail_invoice = $data['invoice_detail'];

        DB::beginTransaction();
        try {
            $insert = $this->invoice->create($invoice_insert);
            $last_id_invoice = $insert->id;

            if (!empty($data['clinic']) && $type == "IV1") {
                $linic = $data['clinic'];
                $linic['invoice_id'] = $last_id_invoice;
                $linic['code_invoice'] = $code;
                $this->prescription->create($linic);
            }

            $ware_house_log = array(
                'drug_store_id' => $user->drug_store_id,
                'user_id' => $user->id,
                'action_type' => $action,
                'invoice_id' => $last_id_invoice,
                'description' => ''
            );

            /*C�ng n?*/
            $this->warehouse_log->create($ware_house_log);
            $flag = false; // Dùng check bán quá
            foreach ($detail_invoice as $value) {
                // Check số lượng == 0 hoặc lẻ
                if (!is_int($value['quantity']) || $value['quantity'] <= 0) {
                    DB::rollback();
                    return -2;
                }
                $is_basic = $this->warehouse->findOneByCredentials(['unit_id' => $value['unit_id'], 'drug_id' => $value['drug_id'], 'number' => $value['number']]);
                $item_invoice = array(
                    'invoice_id' => $last_id_invoice,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'number' => Utils::coalesce($value, 'number', ''),
                    'expiry_date' => Utils::coalesce($value, 'expiry_date', ''),
                    'cost' => $value['cost'],
                    'usage' => Utils::coalesce($value, 'usage', ''),
                    'vat' => Utils::coalesce($value, 'vat', 0),
                    'exchange' => !empty($value['exchange']) ? $value['exchange'] : 1
                );
                $drug_info = $this->drug->findOneById($value['drug_id']);
                $unit_info = $this->unit->findOneById($value['unit_id']);
                $master_check = $this->master_data->findOneBy('drug_code', $drug_info->drug_code);
                if (!empty($master_check)) {
                    $data_gpp['hoa_don_chi_tiet'][] = array(
                        "ma_thuoc" => $drug_info->drug_code,
                        "ten_thuoc" => $drug_info->name,
                        "so_lo" => Utils::coalesce($value, 'number', ''),
                        'han_dung' => str_replace('-', '', $value['expiry_date']),
                        'so_dklh' => $drug_info->registry_number,
                        'so_luong' => $value['quantity'],
                        "don_gia" => $value['cost'],
                        "don_vi_tinh" => $unit_info->name,
                        'ham_luong' => $drug_info->substances,
                        'lieu_dung' => !empty($value['usage']) ? $value['usage'] : "",
                        'so_dang_ky' => $drug_info->registry_number,
                        'so_luong' => $value['quantity'],
                        'don_gia' => $value['cost'],
                        'thanh_tien' => $value['cost'],
                        'duong_dung' => Utils::coalesce($value, 'usage', ''),
                        'ty_le_quy_doi' => $is_basic->exchange
                    );
                }

                $this->invoice_detail->create($item_invoice);
                if ($is_basic->is_basic == 'yes') {
                    $isUpdateQuantitySuccess = $this->warehouse->updateAmount($value['drug_id'], $value['quantity'], $type_warehouse, $value['number'], true);
                } else {
                    $quantity = $value['quantity'] * $is_basic->exchange;
                    $isUpdateQuantitySuccess = $this->warehouse->updateAmount($value['drug_id'], $quantity, $type_warehouse, $value['number'], true);
                }
                if (!$isUpdateQuantitySuccess) {
                    $flag = true;
                    break;
                }
            }


            if ($flag) {
                DB::rollback();
                return -1;
            }

            DB::commit();
            return $last_id_invoice;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    public function warehousingInvoice(Request $request)
    {
        LogEx::methodName($this->className, 'warehousingInvoice');

        $userInfo = $request->userInfo;
        $inputData = $request->input();
        if(!isset($inputData['status'])){
            $inputData['status'] = "done";
        }
        $data = 0;
        DB::beginTransaction();
        try {
            Utils::createTempTableFromRequestInput($inputData);
            $data = DB::select('select f_create_invoice_iv2_iv7_warehousing(?, ?) as result', [$userInfo->drug_store_id, $userInfo->id]);
            $data = $data[0]->result;
            if ($data < 0) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
            $this->syncDQGInvoice($data, $userInfo);
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }

    /**
     * api v3
     * from warehousing
     */
    public function warehousingInvoiceV3(Request $request)
    {
        LogEx::methodName($this->className, 'warehousingInvoiceV3');

        $userInfo = $request->userInfo;
        $inputData = $request->input();
        if(!isset($inputData['status'])){
            $inputData['status'] = "done";
        }
        $data = 0;
        DB::beginTransaction();
        try {
            $data = $this->createInvoiceIV2IV7($inputData, $userInfo);
            if ($data < 0) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
            $this->syncDQGInvoice($data, $userInfo);
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }

    public function warehousingInvoiceTemp(Request $request)
    {
        LogEx::methodName($this->className, 'warehousingInvoiceTemp');

        $userInfo = $request->userInfo;
        $inputData = $request->input();
        $data = 0;
        DB::beginTransaction();
        try {
            Utils::createTempTableFromRequestInput($inputData);
            $data = DB::select('select f_create_invoice_warehousing_temp(?, ?) as result', [$userInfo->drug_store_id, $userInfo->id]);
            $data = $data[0]->result;
            if ($data < 0) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }

    /**
     * api v3
     * from warehousingInvoiceTemp
    */
    public function warehousingInvoiceTempV3($inputData, $user, $drugStoreInfo)
    {
        LogEx::methodName($this->className, 'warehousingInvoiceTempV3');
        $data = 0;
        DB::beginTransaction();
        try {
            $data = $this->warehousingV3($inputData, $user, $drugStoreInfo);
            if (!$data) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }

        return $data;
    }

    public function getList(Request $request)
    {
        LogEx::methodName($this->className, 'getList');
        $requestInput = $request->input();
        Utils::createTempTableFromRequestInput($requestInput);
        return Utils::executeRawQuery('select * from f_invoice_list(?)', [$request->userInfo->drug_store_id], $request->url(), $requestInput);

    }

    public function warehousingStatistic(Request $request)
    {
        LogEx::methodName($this->className, 'warehousingStatistic');
        $requestInput = $request->input();
        $drugName = $requestInput['drug_name'] ?? null;
        $fromDate = $requestInput['from_date'] ?? null;
        $toDate = $requestInput['to_date'] ?? null;
        $sortBy = $requestInput['sort_by'] ?? null;
        $data = Utils::executeRawQuery("select * from f_invoice_supplier_statistic(?, ?, ?, ?, ?)", [$request->userInfo->drug_store_id, $drugName, $fromDate, $toDate, $sortBy], $request->url(), $requestInput);
        return Utils::getSumData($data, $requestInput, 'select sum(t.amount) as amount, sum(t.vat_amount) as vat_amount, sum(t.discount) as discount, sum(t.total_amount) as total_amount, sum(t.pay_amount) as pay_amount, sum(t.debt_amount) as debt_amount, sum(t.return_amount) as return_amount from tmp_output t');
    }

    /**
     * api v3
     * from warehousingStatistic
     */
    public function warehousingStatisticV3(Request $request, $export = 0, $limit = 10)
    {
        LogEx::methodName($this->className, 'warehousingStatisticV3');

        $requestInput = $request->input();

        $coalesce = Utils::coalesceMapping(
            $requestInput,
            [
                'drug_name' => ['-', null],
                'from_date' => ['-', null],
                'to_date' => ['-', null],
                'sort_by' => ['-', null],
            ]
        );

        $params = [
            'p_store_id' => $request->userInfo->drug_store_id,
            'p_supplier_name' => $coalesce['drug_name'],
            'p_from_date' => $coalesce['from_date'],
            'p_to_date' => $coalesce['to_date'],
            'p_sort_by' => $coalesce['sort_by'],
            'query' => Utils::coalesce($request->input(), 'query', null)
        ];

        $query = $this->invoiceSupplierStatisticV3($params);
        $query_sum = $this->invoiceSupplierStatisticV3($params)
            ->get()
            ->toArray();

        $data = Utils::executeRawQueryV3(
            $query,
            $request->url(),
            $requestInput,
            $export,
            $limit
        );

        $sum_data = [
            'amount' => array_sum(array_column($query_sum, 'amount')),
            'vat_amount' => array_sum(array_column($query_sum, 'vat_amount')),
            'discount' => array_sum(array_column($query_sum, 'discount')),
            'total_amount' => array_sum(array_column($query_sum, 'total_amount')),
            'pay_amount' => array_sum(array_column($query_sum, 'pay_amount')),
            'debt_amount' => array_sum(array_column($query_sum, 'debt_amount')),
            'return_amount' => array_sum(array_column($query_sum, 'return_amount'))
        ];

        return Utils::getSumDataV3($data, $requestInput, $sum_data);
    }

    /**
     * api v3
     * exportWarehousingStatisticV3
     */
    public function exportWarehousingStatisticV3(Request $request)
    {
        LogEx::methodName($this->className, 'exportGoodsInOut');
        $userInfo = $request->userInfo;
        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->warehousingStatisticV3($request, 1,35000);
                    break;
                case "current_page":
                    $data = $this->warehousingStatisticV3($request, 1);
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = $this->warehousingStatisticV3($request, 1, 35000);
                    break;
            }
        } else {
            $data = $this->warehousingStatisticV3($request, 1);
        }
        return $data;
    }

    /**
     * api v3
     * from f_invoice_supplier_statistic in v1
    */
    public function invoiceSupplierStatisticV3(array $params, $export = 0, $limit = 10)
    {
        LogEx::methodName($this->className, 'invoiceSupplierStatisticV3');

        $data = DB::table(DB::raw('invoice i'))
            ->select(
                'i.created_at as created_at',
                'i.receipt_date as receipt_date',
                'i.invoice_code',
                'i.id',
                'i.customer_id',
                's.name as supplier_name',
                's.number_phone',
                's.tax_number',
                'i.amount as amount',
                'i.vat_amount as vat_amount',
                'i.discount as discount',
                DB::raw('i.amount + i.vat_amount - i.discount as total_amount'),
                'i.pay_amount as pay_amount',
                DB::raw('i.amount + i.vat_amount - i.discount - i.pay_amount as debt_amount'),
                DB::raw('COALESCE(sum(r.pay_amount), 0) as return_amount')
            )
            ->join(DB::raw('supplier s'),'s.id','=','i.customer_id')
            ->leftJoin(DB::raw('invoice r'),'r.refer_id','=','i.id')
            ->where('i.drug_store_id', '=', $params['p_store_id'])
            ->where('i.status','=','done')
            ->where('i.invoice_type','=','IV2')
            ->where(function ($query) use ($params) {
                if ($params['p_from_date']) $query->where('i.created_at', '>=', $params['p_from_date']);
                if ($params['p_to_date']) $query->where('i.created_at', '<=', $params['p_to_date']);
                if ($params['query']) {
                    $name = trim($params['query']);
                    $queryFultextDB = '1 = 1 ';
                    $queryFultextDB = $queryFultextDB . " AND (i.invoice_code = '" . trim($name) . "'";
                    $queryFultextDB = $queryFultextDB . " OR (s.name ~* '" . $name
                        . "' or s.number_phone  ~* '" . $name
                        . "' or s.tax_number  ~* '" . $name
                        ."'))";
                    $query->whereRaw($queryFultextDB);
                }
            })
            ->groupBy([
                'i.created_at',
                'i.receipt_date',
                'i.invoice_code',
                'i.id',
                'i.customer_id',
                's.name',
                's.number_phone',
                's.tax_number',
                'i.amount',
                'i.vat_amount',
                'i.discount',
                'i.pay_amount'
            ])
            ->when(!empty($params['p_sort_by']), function ($query) use ($params) {
                $p_sort_by = explode('_', trim($params['p_sort_by']));
                $oder_by = $p_sort_by[count($p_sort_by)-1];
                array_pop($p_sort_by);
                $order_list = [
                    'created_at' =>  'created_at',
                    'receipt_date' => 'receipt_date',
                    'invoice_code' => 'i.invoice_code',
                    'supplier_name' => 'supplier_name',
                    'amount' => 'i.amount',
                    'vat' => 'vat_amount',
                    'discount' => 'discount',
                    'total_amount' => 'total_amount',
                    'return_amount' => 'return_amount',
                    'pay_amount' => 'pay_amount',
                    'debt_amount' => 'debt_amount'
                ];

                ( !empty($p_sort_by[0]) && !empty($order_list[implode('_', $p_sort_by)]) ) ?
                $query->orderBy($order_list[implode('_', $p_sort_by)], strtoupper($oder_by)) :
                $query->orderBy('i.id', 'DESC');
            });

        return $data;
    }

    // New
    public function export(Request $invoiceFilterRequest)
    {
        LogEx::methodName($this->className, 'export');
        $userInfo = $invoiceFilterRequest->userInfo;
        $requestInput = $invoiceFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->invoice->filter($invoiceFilterRequest, $userInfo->drug_store_id, 35000);
                    break;
                case "current_page":
                    $data = $this->invoice->filter($invoiceFilterRequest, $userInfo->drug_store_id);
                    break;
                case "current_search":
                    $invoiceFilterRequest->request->remove("page");
                    $data = $this->invoice->filter($invoiceFilterRequest, $userInfo->drug_store_id, 35000);
                    break;
            }
        } else {
            $data = $this->invoice->filter($invoiceFilterRequest, $userInfo->drug_store_id);
        }
        return $data;
    }
    //new save
    public function createOrUpdate($requestInput)
    {
        DB::beginTransaction();
        try {
                  foreach ($requestInput["line_items"] as $lineItems){
                      if (isset($lineItems["combo_name"])  ){
                          foreach ($lineItems["items"] as $itemsCombo){
                              $unitsListRequestCombo = [];
                              $time = strtotime($itemsCombo["expiry_date"]);
                              $newformat = date('Y-m-d',$time);
                              $request = array(
                                  'drug_id' => $itemsCombo["drug_id"],
                                  'expiry_date' => $newformat,
                                  'number' => $itemsCombo["number"],
                                  'cost' => $itemsCombo["price"],
                                  'quantity' => $itemsCombo["quantity"],
//                                  'total_amount' => $itemsCombo["total_amount"],
                                  'unit_id' => $itemsCombo["unit_id"],
                                  'vat' => $itemsCombo["vat"],
                              );
                                  array_push($unitsListRequestCombo, $request);
                                  LogEx::info($unitsListRequestCombo);
                                  $this->invoice_detail->insertBatchWithChunk($unitsListRequestCombo, sizeof($unitsListRequestCombo));
                              DB::commit();
                              }
                          }
                      elseif (isset($lineItems["name_patient"])){
                          foreach ($lineItems["items"] as $itemsPatien){
                              $unitsListRequestCombo = [];
                              $time = strtotime($itemsPatien["expiry_date"]);
                              $newformat = date('Y-m-d',$time);
                              $request = array(
                                  'drug_id' => $itemsPatien["drug_id"],
                                  'expiry_date' => $newformat,
                                  'number' => $itemsPatien["number"],
                                  'cost' => $itemsPatien["price"],
                                  'quantity' => $itemsPatien["quantity"],
//                                  'total_amount' => $itemsCombo["total_amount"],
                                  'unit_id' => $itemsPatien["unit_id"],
                                  'vat' => $itemsPatien["vat"],
                              );
                              array_push($unitsListRequestCombo, $request);
                              LogEx::info($unitsListRequestCombo);
                              $this->invoice_detail->insertBatchWithChunk($unitsListRequestCombo, sizeof($unitsListRequestCombo));

                          }
                          if ($lineItems["age_select"] == "year" ){
                              $year_old = $lineItems["year_old"];
                              $month_old = null;

                          }else
                          {
                              $year_old = null;
                              $month_old = $lineItems["month_old"];
                          }
                          $unitsListRequestPatien = [];
                          if (isset($lineItems["created_at"])){
                              $time = strtotime($lineItems["created_at"]);
                              $formatDateTime = date('Y-m-d H:i:s',$time);
                              LogEx::info($newformat);
                          }
                          if (isset($lineItems["address"])){
                              $address = $lineItems["address"];
                          }
                          if (isset($lineItems["bhyt"])){
                              $bhyt = $lineItems["bhyt"];
                          }
                          if (isset($lineItems["caregiver"])) {
                              $caregiver = $lineItems["caregiver"];
                          }
                          if (isset($lineItems["clinic"])){
                              $clinic = $lineItems["clinic"];
                          }

                          if (isset($lineItems["code_invoice"])){
                              $code_invoice = $lineItems["code_invoice"];
                          }
                          if (isset($lineItems["doctor"])){
                              $doctor = $lineItems["doctor"];
                          }
                          if (isset($lineItems["height"])) {
                              $height = $lineItems["height"];
                          }
                          if (isset($lineItems["id_card"])){
                              $id_card = $lineItems["id_card"];
                          }

                          if (isset($lineItems["patient_address"])){
                              $patient_address = $lineItems["patient_address"];
                          }
                          if (isset($lineItems["patient_code"])) {
                              $patient_code = $lineItems["patient_code"];
                          }
                          if (isset($lineItems["weight"])){
                              $weight = $lineItems["weight"];
                          }

                              $requestPatien = array(
                              'address' => $address,
                              'bhyt'    => $bhyt,
                              'caregiver' => $caregiver,
                              'clinic' => $clinic,
                              'code_invoice' => $code_invoice,
                              'doctor' => $doctor,
                              'height' => $height,
                              'id_card' => $id_card,
                              'name_patient' => $lineItems["name_patient"],
                              'patient_address' => $patient_address,
                              'patient_code' => $patient_code,
                              'weight' => $weight,
                              'year_old' => $year_old,
                              'month_old' => $month_old,
                              'created_at' =>$formatDateTime,
                          );
                          array_push($unitsListRequestPatien, $requestPatien);
                          LogEx::info($requestPatien);
                          $this->prescription->insertBatchWithChunk($unitsListRequestPatien, sizeof($unitsListRequestPatien));
                          DB::commit();
                          }
                      else {
                          $unitsListRequest = [];
                          $time = strtotime($lineItems["expiry_date"]);
                          $newformat = date('Y-m-d',$time);
                          $request = array(
                              'drug_id' => $lineItems["drug_id"],
                              'expiry_date' => $newformat,
                              'number' => $lineItems["number"],
                              'cost' => $lineItems["price"],
                              'quantity' => $lineItems["quantity"],
//                                  'total_amount' => $itemsCombo["total_amount"],
                              'unit_id' => $lineItems["unit_id"],
                              'vat' => $lineItems["vat"],
                          );
                          array_push($unitsListRequest, $request);
                          LogEx::info("san pham le");
                          LogEx::info($unitsListRequest);
                          $this->invoice_detail->insertBatchWithChunk($unitsListRequest, sizeof($unitsListRequest));
//                          DB::commit();
                      }
                      }




            return null;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return null;
        }
    }

    /**
     * api v3
     * create invoiceListV3 from f_invoice_detail on v3
    */
    public function invoiceListV3(array $params)
    {
        LogEx::methodName($this->className, 'invoiceListV3');
        $data = 0;

        return $data;
    }

    /**
     * api v3
     * create invoiceListV3 from f_invoice_detail on v3
     */
    public function invoiceDetailV3(array $params)
    {
        LogEx::methodName($this->className, 'invoiceDetailV3');

        $v_return_data = [];
        $p_drug_store_id = $params['request']->userInfo->drug_store_id;
        $p_invoice_id = !empty($params['invoice_id']) ? $params['invoice_id'] : null;
        $p_invoice_code = !empty($params['invoice_code']) ? $params['invoice_code'] : null;

        $v_invoice_data = DB::table(DB::raw('invoice i'))
            ->select(
                'i.*',
                'u.name as user_fullname',
                'u.username as user_username',
                'c.name as customer_name',
                'c.address','c.email',
                'c.number_phone',
                DB::raw('CASE WHEN i.is_order = true THEN ds.name ELSE s.name END as supplier_name'),
                DB::raw('CASE WHEN i.is_order = true THEN ds.phone ELSE s.number_phone END as supplier_phone'),
                DB::raw('CASE WHEN i.is_order = true THEN ds.address ELSE s.address END as supplier_address'),
                's.email as supplier_email',
                's.website as supplier_website',
                'r.invoice_code as ref_invoice_code',
                DB::raw('(select users.name from users where users.id = i.sale_id) as sale_name')
            )
            ->leftJoin(DB::raw('users u'),'u.id','=','i.created_by')
            ->leftJoin(
                DB::raw('customer c'),
                function($join) {
                    $join->on('c.id','=','i.customer_id')
                        ->whereIn('i.invoice_type',['IV1', 'IV3']);
                }
            )
            ->leftJoin(
                DB::raw('supplier s'),
                function($join) {
                    $join->on('s.id','=','i.customer_id')
                        ->whereIn('i.invoice_type',['IV2', 'IV4', 'IV7']);
                }
            )
            ->leftJoin(DB::raw('drugstores ds'),'ds.id','=','i.customer_id')
            ->leftJoin(DB::raw('invoice r'),'r.id','=','i.refer_id')
            ->when(
                $p_invoice_id,
                function ($query, $p_invoice_id) {
                    $query->where('i.id', '=', $p_invoice_id);
                }
             )
            ->when(
                $p_invoice_code,
                function ($query, $p_invoice_code) {
                    $query->where('i.invoice_code', '=', $p_invoice_code);
                }
            )
            ->first();

        if ($v_invoice_data) {
            $v_invoice_id = $v_invoice_data->id;
            $v_invoice_type = $v_invoice_data->invoice_type;
            $v_return_data['invoice'] = $v_invoice_data;
            $tmp_invoice_detail = DB::table(DB::raw('invoice_detail id'))
                ->select(
                    'id.*',
                    DB::raw(
                        'to_jsonb(id.*) || jsonb_build_object(
                           \'current_cost\', id.cost,
                           \'drug_code\', d.drug_code,
                           \'drug_name\', d.name,
                           \'image\', d.image,
                           \'unit_name\', u.name,
                           \'drug\', case when d.id > 0 then to_jsonb(d.*) else null end
                        ) as invoice_detail'
                    )
                )
                ->leftJoin(DB::raw('drug d'),'d.id','=','id.drug_id')
                ->leftJoin(DB::raw('unit u'),'u.id','=','id.unit_id')
                ->where('id.invoice_id', '=', $v_invoice_id)
                ->get()->toArray();

            if (count($tmp_invoice_detail)) {
                $list_drug_ids = array_column($tmp_invoice_detail, 'drug_id');

                $tmp_drug_units = DB::table(DB::raw('warehouse w'))
                    ->select(
                        'w.drug_id',
                        DB::raw(
                            'jsonb_agg(jsonb_build_object(
                                \'unit_id\',u.id,
                                \'unit_name\',u.name,
                                \'exchange\',w.exchange,
                                \'is_basic\',w.is_basic,
                                \'pre_cost\',w.pre_cost,
                                \'main_cost\',w.main_cost,
                                \'current_cost\',w.current_cost)
                            ) as units'
                        )
                    )
                    ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
                    ->whereRaw('w.is_check = true')
                    ->whereIn('w.drug_id', $list_drug_ids)
                    ->groupBy('w.drug_id')
                    ->get()->toArray();

                $tmp_drug_numbers = DB::table(DB::raw('warehouse w'))
                    ->select(
                        'w.drug_id',
                        DB::raw(
                            'jsonb_agg(jsonb_build_object(
                                \'number\',w.number,
                                \'expiry_date\',w.expiry_date,
                                \'quantity\',w.quantity)
                            ) as numbers'
                        )
                    )
                    ->where('w.is_basic','=','yes')
                    ->where('w.is_check', '=', false)
                    ->whereIn('w.drug_id', $list_drug_ids)
                    ->groupBy('w.drug_id')
                    ->get()->toArray();

                foreach ($tmp_invoice_detail as $key => $tmp_invoice_detail_item) {
                    $conver = json_decode($tmp_invoice_detail_item->invoice_detail);
                    foreach ($tmp_drug_units as $tmp_drug_unit) {
                        if ($tmp_drug_unit->drug_id == $tmp_invoice_detail_item->drug_id) {
                            $conver->drug->units = json_decode($tmp_drug_unit->units);
                        }
                    }
                    foreach ($tmp_drug_numbers as $tmp_drug_number) {
                        if ($tmp_drug_number->drug_id == $tmp_invoice_detail_item->drug_id) {
                            $conver->drug->numbers = json_decode($tmp_drug_number->numbers);
                        }
                    }
                    $tmp_invoice_detail[$key]->invoice_detail = $conver;
                }

                $v_return_data['invoice_detail'] = $tmp_invoice_detail;

                if ($v_invoice_type === 'IV1') {
                    $v_clinic_data = DB::table(DB::raw('prescription t'))
                        ->select(DB::raw('*'))
                        ->where('t.invoice_id', '=', $v_invoice_id)
                        ->get()->toArray();
                    $v_return_data['clinic'] = $v_clinic_data;
                }

                if ($v_invoice_type === 'IV1' || $v_invoice_type === 'IV2') {
                    $v_return_invoices = DB::table(DB::raw('invoice r'))
                        ->select(DB::raw('*'))
                        ->where('r.refer_id', '=', $v_invoice_id)
                        ->get()->toArray();
                    //$v_return_data['retund_invoice'] = $v_return_invoices;
                    $v_return_data['retund_invoice'] = array_column($v_return_invoices, 'id');
                }

                if ($v_invoice_type === 'IV3' || $v_invoice_type === 'IV4') {
                    $v_org_data = DB::table(DB::raw('invoice o'))
                        ->select(DB::raw('*'))
                        ->where('o.id', '=', $v_invoice_data->refer_id)
                        ->get()->toArray();
                    $v_return_data['original_invoice'] = $v_org_data;
                }
            }
        }

        return $v_return_data;
    }

    public function invoiceDetailV3New(array $params)
    {
        LogEx::methodName($this->className, 'invoiceDetailV3');

        $v_return_data = [];
        $p_drug_store_id = $params['request']->userInfo->drug_store_id ?? null;
        $p_invoice_id = !empty($params['invoice_id']) ? $params['invoice_id'] : null;
        $p_invoice_code = !empty($params['invoice_code']) ? $params['invoice_code'] : null;

        $v_invoice_data = DB::table(DB::raw('invoice i'))
            ->select(
                'i.*',
                'u.name as user_fullname',
                'u.username as user_username',
                'c.name as customer_name',
                'c.address','c.email',
                'c.number_phone',
                DB::raw('CASE WHEN i.is_order = true THEN ds.name ELSE s.name END as supplier_name'),
                DB::raw('CASE WHEN i.is_order = true THEN ds.phone ELSE s.number_phone END as supplier_phone'),
                DB::raw('CASE WHEN i.is_order = true THEN ds.address ELSE s.address END as supplier_address'),
                's.email as supplier_email',
                's.website as supplier_website',
                'r.invoice_code as ref_invoice_code',
                DB::raw('(select users.name from users where users.id = i.sale_id) as sale_name')
            )
            ->leftJoin(DB::raw('users u'),'u.id','=','i.created_by')
            ->leftJoin(
                DB::raw('customer c'),
                function($join) {
                    $join->on('c.id','=','i.customer_id')
                        ->whereIn('i.invoice_type',['IV1', 'IV3']);
                }
            )
            ->leftJoin(
                DB::raw('supplier s'),
                function($join) {
                    $join->on('s.id','=','i.customer_id')
                        ->whereIn('i.invoice_type',['IV2', 'IV4', 'IV7']);
                }
            )
            ->leftJoin(DB::raw('drugstores ds'),'ds.id','=','i.customer_id')
            ->leftJoin(DB::raw('invoice r'),'r.id','=','i.refer_id')
            ->when(
                $p_invoice_id,
                function ($query, $p_invoice_id) {
                    $query->where('i.id', '=', $p_invoice_id);
                }
            )
            ->when(
                $p_invoice_code,
                function ($query, $p_invoice_code) {
                    $query->where('i.invoice_code', '=', $p_invoice_code);
                }
            )
            ->first();

        if ($v_invoice_data) {
            $v_invoice_id = $v_invoice_data->id;
            $v_invoice_type = $v_invoice_data->invoice_type;
            $v_return_data['invoice'] = $v_invoice_data;

            $tmp_invoice_detail = DB::table(DB::raw('invoice_detail id'))
                ->select('id.drug_id',DB::raw('to_jsonb(id.*) || jsonb_build_object(
                                    \'current_cost\', id.cost,
                                    \'drug_code\', d.drug_code,
                                    \'drug_name\', d.name,
                                    \'image\', d.image,
                                    \'unit_name\', u.name,
                                    \'drug\', case when d.id > 0 then to_jsonb(d.*) else null end
                                )       as invoice_detail'))
                ->leftJoin(DB::raw('drug d'),'d.id','=','id.drug_id')
                ->leftJoin(DB::raw('unit u'),'u.id','=','id.unit_id')
                ->where('id.invoice_id', '=', $v_invoice_id)
                ->where('id.drug_id', '>', 0);

                $tmp_drug_units = DB::table(DB::raw('warehouse w'))
                    ->select(
                        'w.drug_id',
                        DB::raw(
                            'jsonb_agg(jsonb_build_object(
                                \'unit_id\',u.id,
                                \'unit_name\',u.name,
                                \'exchange\',w.exchange,
                                \'is_basic\',w.is_basic,
                                \'pre_cost\',w.pre_cost,
                                \'main_cost\',w.main_cost,
                                \'current_cost\',w.current_cost)
                            ) as units'
                        )
                    )
                    ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
                    ->whereRaw('w.is_check = true')
                    ->joinSub(
                        $tmp_invoice_detail,
                        't',
                        function ($join) {
                            $join->on('t.drug_id', '=', 'w.drug_id');
                        }
                    )
                    ->groupBy('w.drug_id');

                $tmp_drug_numbers = DB::table(DB::raw('warehouse w'))
                    ->select(
                        'w.drug_id',
                        DB::raw(
                            'jsonb_agg(jsonb_build_object(
                                \'number\',w.number,
                                \'expiry_date\',w.expiry_date,
                                \'quantity\',w.quantity)
                            ) as numbers'
                        )
                    )
                    ->where('w.is_basic','=','yes')
                    ->where('w.is_check', '=', false)
                    ->joinSub(
                        $tmp_invoice_detail,
                        't',
                        function ($join) {
                            $join->on('t.drug_id', '=', 'w.drug_id');
                        }
                    )
                    ->groupBy('w.drug_id');

                $query_invoice_detail = str_replace_array('?', $tmp_invoice_detail->getBindings(), $tmp_invoice_detail->toSql());

                $joinSub = DB::table(DB::raw("({$query_invoice_detail}) id"))
                    ->select(DB::raw('id.invoice_detail ||
                        jsonb_build_object(\'drug\', id.invoice_detail->\'drug\' ||
                        jsonb_build_object(\'units\', tdu.units, \'numbers\', tdn.numbers)) as invoice_detail'))
                    ->leftJoinSub(
                        $tmp_drug_numbers,
                        'tdn',
                        function ($join) {
                            $join->on('tdn.drug_id', '=', 'id.drug_id');
                        })
                    ->leftJoinSub(
                        $tmp_drug_units,
                        'tdu',
                        function ($join) {
                            $join->on('tdu.drug_id', '=', 'id.drug_id');
                        })->get();

                $v_return_data['invoice_detail'] = [];

                foreach ($joinSub as $key => $item) {
                    $joinSub[$key]->invoice_detail = json_decode($item->invoice_detail);
                    $v_return_data['invoice_detail'][] = $joinSub[$key]->invoice_detail;
                }

                if ($v_invoice_type === 'IV1') {
                    $v_clinic_data = DB::table(DB::raw('prescription t'))
                        ->select(DB::raw('*'))
                        ->where('t.invoice_id', '=', $v_invoice_id)
                        ->get()->toArray();
                    $v_return_data['v_clinic_data'] = $v_clinic_data;
                }

                if ($v_invoice_type === 'IV1' || $v_invoice_type === 'IV2') {
                    $v_return_invoices = DB::table(DB::raw('invoice r'))
                        ->select(DB::raw('*'))
                        ->where('r.refer_id', '=', $v_invoice_id)
                        ->get()->toArray();
                    $v_return_data['v_return_invoices'] = array_column($v_return_invoices, 'id');
                }

                if ($v_invoice_type === 'IV3' || $v_invoice_type === 'IV4') {
                    $v_org_data = DB::table(DB::raw('invoice o'))
                        ->select(DB::raw('*'))
                        ->where('o.id', '=', $v_invoice_data->refer_id)
                        ->get()->toArray();
                    $v_return_data['v_org_data'] = $v_org_data;
                }
        }

        return $v_return_data;
    }

    /**
     * api v3
     * create invoiceDetailShortV3 from f_invoice_detail_short on v3
     */
    public function invoiceDetailShortV3(array $params)
    {
        LogEx::methodName($this->className, 'invoiceDetailShortV3');

        $v_return_data = [];
        $p_invoice_id = !empty($params['invoice_id']) ? $params['invoice_id'] : null;
        $p_invoice_code = !empty($params['invoice_code']) ? $params['invoice_code'] :null;

        $v_invoice_data = DB::table(DB::raw('invoice i'))
            ->select(
                'i.*',
                'u.name as user_fullname',
                'u.username as user_username',
                'c.name as customer_name',
                'c.address','c.email',
                'c.number_phone',
                's.name as supplier_name',
                's.number_phone as supplier_phone',
                's.email as supplier_email',
                's.website as supplier_website',
                'r.invoice_code as ref_invoice_code'
            )
            ->leftJoin(DB::raw('users u'),'u.id','=','i.created_by')
            ->leftJoin(
                DB::raw('customer c'),
                function($join) {
                    $join->on('c.id','=','i.customer_id')
                        ->whereIn('i.invoice_type',['IV1', 'IV3']);
                }
            )
            ->leftJoin(
                DB::raw('supplier s'),
                function($join) {
                    $join->on('s.id','=','i.customer_id')
                        ->whereIn('i.invoice_type',['IV2', 'IV4', 'IV7']);
                }
            )
            ->leftJoin(DB::raw('invoice r'),'r.id','=','i.refer_id')
            ->when(
                $p_invoice_id,
                function ($query, $p_invoice_id) {
                    $query->where('i.id', '=', $p_invoice_id);
                }
            )
            ->when(
                $p_invoice_code,
                function ($query, $p_invoice_code) {
                    $query->where('i.invoice_code', '=', $p_invoice_code);
                }
            )
            ->first();

        if ($v_invoice_data) {
            $v_invoice_id = $v_invoice_data->id;
            $v_invoice_type = $v_invoice_data->invoice_type;

            $tmp_invoice_detail = DB::table(DB::raw('invoice_detail id'))
                ->select(
                    'id.drug_id',
                    DB::raw(
                        'to_jsonb(id.*) || jsonb_build_object(
                           \'current_cost\', id.cost,
                           \'drug_code\', d.drug_code,
                           \'drug_name\', d.name,
                           \'image\', d.image,
                           \'unit_name\', u.name
                        ) as invoice_detail'
                    )
                )
                ->leftJoin(DB::raw('drug d'),'d.id','=','id.drug_id')
                ->leftJoin(DB::raw('unit u'),'u.id','=','id.unit_id')
                ->where('id.invoice_id', '=', $v_invoice_id)
                ->where('id.unit_id', '>', 0)
                ->get()->toArray();

            if (count($tmp_invoice_detail)) {
                $list_drug_ids = array_column($tmp_invoice_detail, 'drug_id');

                $tmp_drug_units = DB::table(DB::raw('warehouse w'))
                    ->select(
                        'w.drug_id',
                        DB::raw(
                            'jsonb_agg(jsonb_build_object(
                                \'unit_id\',u.id,
                                \'unit_name\',u.name,
                                \'exchange\',w.exchange,
                                \'is_basic\',w.is_basic,
                                \'pre_cost\',w.pre_cost,
                                \'main_cost\',w.main_cost,
                                \'current_cost\',w.current_cost)
                            ) as units'
                        )
                    )
                    ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
                    ->whereRaw('w.is_check = true')
                    ->whereIn('w.drug_id', $list_drug_ids)
                    ->groupBy('w.drug_id')
                    ->get()->toArray();

                $tmp_drug_numbers = DB::table(DB::raw('warehouse w'))
                    ->select(
                        'w.drug_id',
                        DB::raw(
                            'jsonb_agg(jsonb_build_object(
                                \'number\',w.number,
                                \'expiry_date\',w.expiry_date,
                                \'quantity\',w.quantity)
                            ) as numbers'
                        )
                    )
                    ->where('w.is_basic','=','yes')
                    ->where('w.is_check', '=', false)
                    ->whereIn('w.drug_id', $list_drug_ids)
                    ->groupBy('w.drug_id')
                    ->get()->toArray();

                foreach ($tmp_invoice_detail as $key => $value) {
                    $conver = json_decode($value->invoice_detail);
                    $tmp_invoice_detail[$key]->invoice_detail = $conver;
                }

                $v_from_order = DB::table('t_order')
                    ->select('out_invoice_id')
                    ->where('out_invoice_id', '=', $v_invoice_id)
                    ->orWhere('in_invoice_id', '=', $v_invoice_id)
                    ->get()->toArray();

                $v_return_data = [
                    'from_order' => count($v_from_order) ? true : false,
                    'invoice' => $v_invoice_data,
                    'invoice_detail' =>$tmp_invoice_detail
                ];

                if ($v_invoice_type === 'IV1') {
                    $v_clinic_data = DB::table(DB::raw('prescription t'))
                        ->select(DB::raw('(jsonb_agg(t)) ::jsonb'))
                        ->where('t.invoice_id', '=', $v_invoice_id)
                        ->get()->toArray();
                    $v_return_data['v_clinic_data'] = $v_clinic_data;
                }

                if ($v_invoice_type === 'IV1' || $v_invoice_type === 'IV2') {
                    $v_return_invoices = DB::table(DB::raw('invoice r'))
                        ->select(DB::raw('(jsonb_agg(r.id)) ::jsonb'))
                        ->where('r.refer_id', '=', $v_invoice_id)
                        ->get();
                    $v_return_data['v_return_invoices'] = $v_return_invoices;
                }

                if ($v_invoice_type === 'IV3' || $v_invoice_type === 'IV4') {
                    $v_org_data = DB::table(DB::raw('invoice o'))
                        ->select(DB::raw('(jsonb_agg(o)) ::jsonb'))
                        ->where('o.id', '=', $v_invoice_data->refer_id)
                        ->get()->toArray();
                    $v_return_data['v_org_data'] = $v_org_data;
                }
            }
        }

        return $v_return_data;
    }

    /**
     * api v3
     * create invoiceUpdateStatusV3 from f_invoice_update_status on v3
     */
    public function invoiceUpdateStatusV3(array $params)
    {
        LogEx::methodName($this->className, 'invoiceUpdateStatusV3');

        $data = [];
        $p_store_id = Utils::coalesce($params, 'drug_store_id', null);
        $p_id = Utils::coalesce($params, 'id', null);
        $p_status = Utils::coalesce($params, 'status', null);
        $v_current_time = Carbon::now()->format('Y-m-d');

        DB::beginTransaction();
        try {
            $v_invoice_data = DB::table(DB::raw('invoice i'))
                ->select(DB::raw('*'))
                ->where('i.id', '=', $p_id)
                ->first();

            $v_price_status = $this->invoiceUpdateInPriceV3($p_id, $p_store_id);

            if (!$p_status) return 'Trạng thái không hợp lệ';

            if (!$v_invoice_data) return 'Hoá đơn không tồn tại';

            if ($v_invoice_data->status == 'cancel') return 'Hoá đơn đã huỷ';

            if ($v_invoice_data->status == $p_status) return 'Trạng thái hoá đơn không đổi';

            DB::table('invoice')
                ->where('id', '=', $p_id)
                ->update([
                    'status' => $p_status,
                    'updated_at' => $v_current_time
                ]);

            if ($p_status == 'done' || $p_status == 'processing') {
                $v_count = $this->invoiceUpdateQuantityV1($p_id);

                if ($v_count < 0) return 'Số lượng tồn kho không hợp lệ';
            }

            if ($p_status == 'cancel') {
                if ($p_status == 'done' || $p_status == 'processing') {
                    $v_count = $this->invoiceUpdateQuantityV1($p_id, 1, 0);

                    if ($v_count < 0) return 'Số lượng tồn kho không hợp lệ';
                }
            }

            $data['id'] = $p_id;
            DB::commit();
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }

        return $data;
    }

    /**
     * api v3
     * from f_invoice_update_quantity on v1
    */
    public function invoiceUpdateQuantityV1($p_invoice_id , $p_reverse_flag = null, $p_force_update = null)
    {
        LogEx::methodName($this->className, 'invoiceUpdateQuantityV1');

        $v_current_time = Carbon::now()->format('Y-m-d');
        $invoice = DB::table(DB::raw('invoice i'))
            ->select('i.invoice_type','i.drug_store_id')
            ->where('i.id', '=', $p_invoice_id)
            ->first();
        if (!$invoice) return -1;

        $v_invoice_type = $invoice->invoice_type;
        $v_drug_store_id = $invoice->drug_store_id;
        $v_pos_neg = 1;

        if (in_array($v_invoice_type, ['IV1', 'IV4', 'IV8'])) $v_pos_neg = -1;

        if ($p_reverse_flag > 0) $v_pos_neg = $v_pos_neg * -1;
        //invoice_detail
        $tmp_changed_quantity = DB::table(DB::raw('invoice_detail id'))
            ->select(
                'id.drug_id',
                'id.number',
                DB::raw('max(id.expiry_date) as expiry_date'),
                DB::raw('max(id.mfg_date) as mfg_date'),
                DB::raw('coalesce(max(w.quantity), 0) + ' . $v_pos_neg . ' * sum(id.quantity * id.exchange)   as quantity')
            )
            ->leftJoin(DB::raw('warehouse w'), function($join) use($v_drug_store_id) {
                $join->on('w.drug_id', '=', 'id.drug_id')
                    ->on('w.number', '=', 'id.number')
                    ->where('w.drug_store_id', '=', $v_drug_store_id)
                    ->where('w.is_check', '=', false)
                    ->where('w.is_basic', '=', 'yes');
            })
            ->where('id.invoice_id', '=', $p_invoice_id)
            ->groupBy(['id.drug_id','id.number'])
            ->get()
            ->toArray();
        //update ware house
        foreach ($tmp_changed_quantity as $item) {
            //check
            if ($item->quantity < 0 && $p_force_update <= 0) return -2;
            //update
            DB::table('warehouse')
                ->where('drug_store_id', '=', $v_drug_store_id)
                ->where('drug_id', '=', $item->drug_id)
                ->where('number', '=', $item->number)
                ->whereRaw('is_check = false')
                ->update([
                    'quantity' => DB::raw('floor(' . $item->quantity . ' * 10 / exchange)/10'),
                    'expiry_date'=> $item->expiry_date,
                    'updated_at' => $v_current_time
                ]);
            //insert
        }
        //v3 calculate invoice quantity
        $tmp_stock_changed = DB::table(DB::raw('invoice_detail id'))
            ->select(
                'id.drug_id',
                DB::raw('max(u.unit_id) as unit_id'),
                'id.number',
                DB::raw('max(id.expiry_date) as expiry_date'),
                DB::raw('coalesce(max(s.quantity), 0) + ' . $v_pos_neg . ' * sum(id.quantity * id.exchange)   as quantity')
            )
            ->join(DB::raw('t_drug_unit u'), function($join) use ($v_drug_store_id) {
                $join->on('u.drug_id', '=', 'id.drug_id')
                    ->where('u.drug_store_id', '=', $v_drug_store_id)
                    ->whereRaw('u.is_basis = true');
            })
            ->leftJoin(DB::raw('t_stock s'), function($join) use ($v_drug_store_id) {
                $join->on('s.drug_id', '=', 'id.drug_id')
                    ->on('s.number', '=', 'id.number')
                    ->where('s.drug_store_id', '=', $v_drug_store_id);

            })
            ->where('id.invoice_id', '=', $p_invoice_id)
            ->groupBy(['id.drug_id','id.number'])
            ->get()
            ->toArray();
        //v3 update exists stock quantity
        foreach ($tmp_stock_changed as $item) {
            //check
            if ($item->quantity < 0 && $p_force_update <= 0) return -2;
            //update
            DB::table('t_stock')
                ->where('drug_store_id', '=', $v_drug_store_id)
                ->where('drug_id', '=', $item->drug_id)
                ->where('number', '=', $item->number)
                ->update([
                    'quantity' => $item->quantity,
                    'expiry_date' => $item->expiry_date,
                    'updated_at' => $v_current_time
                ]);
            //insert
        }

        return $p_invoice_id;
    }

    /**
     * api v3
     * from f_invoice_update_in_price on v1
     */
    public function invoiceUpdateInPriceV3($p_invoice_id , $p_drug_store_id)
    {
        LogEx::methodName($this->className, 'invoiceUpdateInPriceV3');

        $invoice = DB::table(DB::raw('invoice i'))
            ->select('i.invoice_type','i.updated_at')
            ->where('i.id', '=', $p_invoice_id)
            ->first();

        if (in_array($invoice->invoice_type, ['IV2', 'IV7'])) return 0;

        $invoice_details = DB::table('invoice_detail')
            ->where('invoice_id', '=', $p_invoice_id)
            ->get()->toArray();

        foreach ($invoice_details as $invoice_detail) {
            $invocie_updated = DB::table(DB::raw('invoice_detail il'))
                ->select('*')
                ->leftJoin(DB::raw('invoice i'),'il.invoice_id','=','i.id')
                ->where('il.drug_id', '=',$invoice_detail->drug_id)
                ->where('i.status','=','done')
                ->where('il.updated_at', '>', $invoice_detail->updated_at)
                ->where('i.id', '<>', $p_invoice_id)
                ->count();
            if ($invocie_updated > 0) return 1;
        }

        return 1;
    }
}
