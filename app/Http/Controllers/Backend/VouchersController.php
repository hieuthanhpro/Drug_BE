<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Models\Vouchers;
use App\Repositories\Vouchers\VouchersRepositoryInterface;
use App\Services\CashBook;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;

/**
 * @deprecated
 */
class VouchersController extends Controller
{
    protected $className = "Backend\VouchersController";

    protected $vouchers;
    protected $cask_book;

    public function __construct(VouchersRepositoryInterface $vouchers, CashBook $cask_book)
    {
        LogEx::constructName($this->className, '__construct');

        $this->vouchers = $vouchers;
        $this->cask_book = $cask_book;
    }

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $input = $request->input();

        $fromDate = $input['from_date'] ?? null;
        $toDate = $input['to_date'] ?? null;
        $date = date_create($toDate);
        $toDate = date_add($date, date_interval_create_from_date_string("1 days"))->format('Y-m-d');
        $type = $input['type'] ?? null;
        $invoiceType = $input['invoice_type'] ?? null;
        $amount = $input['amount'] ?? null;
        $data = $this->vouchers->getListVouchersByCondition($user->drug_store_id, $fromDate, $toDate, $type, $invoiceType, $amount);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $input = $request->input();
        $invoiceType = $input['invoice_type'] ?? null;
        $customerId = $input['customer_id'];
        if ($invoiceType == 'IV1') {
            $code = 'PT' . Utils::getSequenceDB('PT');
            $typeVouchers = 1;
        } elseif ($invoiceType == 'IV3') {
            $code = 'PC' . Utils::getSequenceDB('PC');
            $typeVouchers = 0;
        } elseif ($invoiceType == 'IV4') {
            $code = 'PC' . Utils::getSequenceDB('PC');
            $typeVouchers = 1;
        } elseif ($invoiceType == 'IV2') {
            $code = 'PC' . Utils::getSequenceDB('PC');
            $typeVouchers = 0;
        } elseif ($invoiceType == 'IV5') {
            $code = 'PN' . Utils::getSequenceDB('PN');
            $typeVouchers = 1;
        } elseif ($invoiceType == 'IV6') {
            $code = 'PC' . Utils::getSequenceDB('PC');
            $typeVouchers = 0;
        } else {
            $typeVouchers = 2;
        }

        if ($invoiceType == "IV2" || $invoiceType == "IV4") {
            $data = array(
                'user_id' => $user->id,
                'type' => $typeVouchers,
                'amount' => $input['amount'],
                'drug_store_id' => $user->drug_store_id,
                'invoice_type' => $invoiceType,
                'code' => $code,
                'supplier_id' => $customerId,
                'note' => $input['note'] ?? '',
                'recipient_id' => !empty($input['recipient_id']) ? $input['recipient_id'] : null
            );
        } elseif ($invoiceType == "IV1" || $invoiceType == "IV3") {
            $data = array(
                'user_id' => $user->id,
                'type' => $typeVouchers,
                'amount' => $input['amount'],
                'drug_store_id' => $user->drug_store_id,
                'invoice_type' => $invoiceType,
                'customer_id' => $customerId,
                'code' => $code,
                'note' => $input['note'] ?? '',
                'recipient_id' => !empty($input['recipient_id']) ? $input['recipient_id'] : null
            );
        } else {
            $data = array(
                'user_id' => $user->id,
                'type' => $typeVouchers,
                'amount' => $input['amount'],
                'drug_store_id' => $user->drug_store_id,
                'invoice_type' => $invoiceType,
                'code' => $code,
                'note' => $input['note'] ?? '',
                'recipient_id' => !empty($input['recipient_id']) ? $input['recipient_id'] : null
            );
        }
        $data_result = $this->cask_book->createVouchers($data, $invoiceType, $customerId);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data_result);
    }

    public function getListVouchers($id, Request $request)
    {
        LogEx::methodName($this->className, 'getListVouchers');

        $input = $request->input();
        $type = $input['type'] ?? null;
        $formDate = $input['form_date'] ?? null;
        $toDate = $input['to_date'] ?? null;
        $data = $this->vouchers->getListSupplier($id, $formDate, $toDate, $type);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function cancelVouchers($id, Request $request)
    {
        LogEx::methodName($this->className, 'cancelVouchers');

        $data = $this->vouchers->updateOneById($id, ['status' => 0]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function staticsVouchers(Request $request)
    {
        LogEx::methodName($this->className, 'staticsVouchers');

        $dataResult = array();
        $input = $request->input();
        $user = $request->userInfo;
        $startMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $currentDate = Carbon::now()->format('Y-m-d');
        $formDate = $input['from_date'] ?? $startMonth;
        $toDate = $input['to_date'] ?? $currentDate;
        $date = date_create($toDate);
        $toDate = date_add($date, date_interval_create_from_date_string("1 days"))->format('Y-m-d');

        /*tính quỹ đầu kỳ*/

        $allThu = Vouchers::select('vouchers.type', \DB::raw('SUM(vouchers.amount) as amount'))
            ->where('vouchers.type', 1)
            ->where('vouchers.drug_store_id', $user->drug_store_id)
            ->where('vouchers.created_at', '<=', $formDate)
            ->where('vouchers.status', 1)
            ->groupBy('vouchers.type')
            ->first();

        $allChi = Vouchers::select('vouchers.type', \DB::raw('SUM(vouchers.amount) as amount'))
            ->where('vouchers.type', 0)
            ->where('vouchers.drug_store_id', $user->drug_store_id)
            ->where('vouchers.created_at', '<=', $formDate)
            ->where('vouchers.status', 1)
            ->groupBy('vouchers.type')
            ->first();
        $dauKy = Vouchers::select('vouchers.amount')
            ->where('vouchers.type', 2)
            ->where('vouchers.drug_store_id', $user->drug_store_id)
            ->where('vouchers.created_at', '<=', $formDate)
            ->where('vouchers.status', 1)
            ->first();

        if (empty($allChi)) {
            $allChi['amount'] = 0;
        }
        if (empty($allThu)) {
            $allThu['amount'] = 0;
        }
        if (empty($dauKy)) {
            $dauKy['amount'] = 0;
        }
        /*kết thúc tính quỹ*/

        $totalChiTemp = Vouchers::select('vouchers.type', \DB::raw('SUM(vouchers.amount) as amount'))
            ->where('vouchers.type', 0)
            ->where('vouchers.drug_store_id', $user->drug_store_id)
            ->wheredate('vouchers.updated_at', '>=', $formDate)
            ->wheredate('vouchers.updated_at', '<=', $toDate)
            ->where('vouchers.status', 1)
            ->groupBy('vouchers.type')
            ->first();


        $totalThuTemp = Vouchers::select('vouchers.type', \DB::raw('SUM(vouchers.amount) as amount'))
            ->where('vouchers.type', 1)
            ->where('vouchers.drug_store_id', $user->drug_store_id)
            ->wheredate('vouchers.updated_at', '>=', $formDate)
            ->wheredate('vouchers.updated_at', '<=', $toDate)
            ->where('vouchers.status', 1)
            ->groupBy('vouchers.type')
            ->first();

        /*lấy thu chi trong kỳ*/
        $totalThu = $totalThuTemp['amount'] ?? 0;
        $totalChi = $totalChiTemp['amount'] ?? 0;
        $totalQuyDauKy = $allThu['amount'] + $dauKy['amount'] - $allChi['amount'];
        $totalTonQuy = $totalQuyDauKy + $totalThu - $totalChi;

        $dataResult['total_thu'] = $totalThu;
        $dataResult['total_chi'] = $totalChi;
        $dataResult['quy_dau_ky'] = $totalQuyDauKy;
        $dataResult['ton_quy'] = $totalTonQuy;
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataResult);
    }

    public function getDetailVouchers($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailVouchers');

        $input = $request->input();
        $type = $input['type'] ?? null;
        $data = $this->vouchers->getDetail($id, $type);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
