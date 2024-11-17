<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\VouchersCheck;
use App\Repositories\CheckDetail\CheckDetailRepositoryInterface;
use App\Repositories\Users\UserRepositoryInterface;
use App\Repositories\VouchersCheck\VouchersCheckRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VouchersCheckController extends Controller
{
    protected $className = "Backend\VouchersCheckController";

    protected $vouchersCheck;
    protected $checkDetail;
    protected $warehouse;
    protected $user;

    public function __construct(
        VouchersCheckRepositoryInterface $vouchersCheck,
        CheckDetailRepositoryInterface   $checkDetail,
        WarehouseRepositoryInterface     $warehouse,
        UserRepositoryInterface          $user

    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->vouchersCheck = $vouchersCheck;
        $this->checkDetail = $checkDetail;
        $this->warehouse = $warehouse;
        $this->user = $user;
    }

    //public function index(Request $request)
    //{
    //    LogEx::methodName($this->className, 'index');
    //    $user = $request->userInfo;
    //    $input = $request->input();
    //    //$input['search'] = $input['query'] ?? null;
    //    Utils::createTempTableFromRequestInput($input);
    //    $data = Utils::executeRawQuery('select * from f_vouchers_check_list(?)', [$user->drug_store_id], $request->url(), $input);
    //    return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    //}

    /**
     * api v3
     * from index
    */
    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $drugStoreId = $request->userInfo->drug_store_id;
        $input = $request->input();
        $searchStr = $input['query'] ?? '';
        $fromDate = $input['from_date'] ?? '';
        $toDate = $input['to_date'] ?? '';
        $status = $input['status'] ?? '';

        $data = Utils::executeRawQueryV3(
            $this->vouchersCheck->getList($drugStoreId, $searchStr, $fromDate, $toDate, $status),
            $request->url(),
            $input
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * export voucher
    */
    public function export(Request $request)
    {
        LogEx::methodName($this->className, 'export');

        $user = $request->userInfo;
        $input = $request->input();
        $searchStr = $input['query'] ?? null;
        $fromDate = $input['from_date'] ?? null;
        $toDate = $input['to_date'] ?? null;
        $status = $input['status'] ?? null;
        $typeExport = $input["type_export"] ?? null;
        Utils::createTempTableFromRequestInput($input);
        $data = [];

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQueryV3(
                        $this->vouchersCheck->getList($user->drug_store_id, $searchStr, $fromDate, $toDate, $status),
                        $request->url(),
                        $input,
                        1,
                        3500
                    );
                    break;
                case "current_page":
                    $data = Utils::executeRawQueryV3(
                        $this->vouchersCheck->getList($user->drug_store_id, $searchStr, $fromDate, $toDate, $status),
                        $request->url(),
                        $input,
                        1
                    );
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = Utils::executeRawQueryV3(
                        $this->vouchersCheck->getList($user->drug_store_id, $searchStr, $fromDate, $toDate, $status),
                        $request->url(),
                        $input,
                        1,
                        3500
                    );
                    break;
            }
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

//    public function store(Request $request)
//    {
//        LogEx::methodName($this->className, 'store');
//        $requestInput = $request->input();
//        $user = $request->userInfo;
//        Utils::createTempTableFromRequestInput($requestInput);
//        $data = Utils::executeRawQuery('select f_vouchers_check_create(?, ?) as result', [$user->drug_store_id, $user->id], $request->url(), $requestInput);
//
//        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
//    }

    /**
     * api v3
     * from store
    */
    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $input = $request->input();
        $vouchersCheck = array(
            'note' => $input['note'] ?? '',
            'code' => 'KP'.Utils::getSequenceDB('KP'),
            'status' => true,
            'created_by' => $request->userInfo->id,
            'check_status' => 'pending',
            'drug_store_id' => $request->userInfo->drug_store_id
        );
        $checkDetail = $input['check_detail'];

        /* insert database */
        DB::beginTransaction();
        try {
            $vouchersCheck = $this->vouchersCheck->create($vouchersCheck);
            //$this->checkDetail->deleteManyBy('vouchers_check_id', $id);
            foreach ($checkDetail as $value) {
                if (is_null($value['current_amount']) || $value['current_amount'] == 0) {
                    return \App\Helper::errorResponse(
                        CommonConstant::INTERNAL_SERVER_ERROR,
                        'Số lượng kiểm kho phải lớn hơn 0'
                    );
                }

                $tmp = array(
                    'vouchers_check_id' => $vouchersCheck->id,
                    'drug_id' => Utils::coalesce($value, 'drug_id', null),
                    'number' => Utils::coalesce($value, 'number', null),
                    'diff_amount' => Utils::coalesce($value, 'diff_amount', null),
                    'current_amount' => Utils::coalesce($value, 'current_amount', 0),
                    'unit_id' => Utils::coalesce($value, 'unit_id', null),
                    'diff_value' => Utils::coalesce($value, 'diff_value', null),
                    'main_cost' => Utils::coalesce($value, 'main_cost', null),
                    'amount' => Utils::coalesce($value, 'amount', null),
                    'expiry_date' => Utils::coalesce($value, 'expiry_date', null),
                    'drug_code' => Utils::coalesce($value, 'drug_code', null),
                    'note' => Utils::coalesce($value, 'note', null),
                );
                $this->checkDetail->create($tmp);
//                $warehouse = array(
//                    'drug_id' => $value['drug_id'],
//                    'unit_id' => $value['unit_id'],
//                    'number' => $value['number'],
//                    'amount' => $value['current_amount'],
//                );
//                $this->warehouse->updateAmountByUnit($warehouse);
            }
            DB::commit();
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $vouchersCheck->id);
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $input = $request->input();
        $vouchersCheck = array(
            'note' => $input['note'] ?? '',
        );
        $checkDetail = $input['check_detail'];

        /* insert database */
        DB::beginTransaction();
        try {
            $this->vouchersCheck->updateOneById($id, $vouchersCheck);
            $this->checkDetail->deleteManyBy('vouchers_check_id', $id);
            foreach ($checkDetail as $value) {
                $tmp = array(
                    'vouchers_check_id' => $id,
                    'drug_id' => $value['drug_id'],
                    'number' => $value['number'],
                    'diff_amount' => $value['diff_amount'],
                    'current_amount' => $value['current_amount'],
                    'unit_id' => $value['unit_id'],
                    'diff_value' => $value['diff_value'] ?? null,
                    'main_cost' => $value['main_cost'] ?? null,
                    'amount' => $value['amount'] ?? null,
                    'expiry_date' => $value['expiry_date'] ?? '',
                    'drug_code' => $value['drug_code'] ?? '',
                    'note' => $value['note'] ?? ''
                );
                $this->checkDetail->create($tmp);
                $warehouse = array(
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'number' => $value['number'],
                    'amount' => $value['current_amount'],
                );
                $this->warehouse->updateAmountByUnit($warehouse);
            }
            DB::commit();
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function getDetail($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetail');

        $user = $request->userInfo;
        $dataResult = $this->vouchersCheck->findOneById($id);
        if ($dataResult->drug_store_id != $user->drug_store_id) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }

        if (isset($dataResult->created_by)) {
            $userInfo = $this->user->findOneById($dataResult->created_by);
            $dataResult['creator'] = $userInfo->full_name ?? ($userInfo->name ?? null);
        }

        $dataResult['check_detail'] = DB::table("check_detail")
            ->select(
                'check_detail.*',
                'unit.name as unit_name',
                'drug.name as drug_name',
                'drug.image as drug_image'
            )
            ->join('unit', 'unit.id', 'check_detail.unit_id')
            ->join('drug', 'drug.id', 'check_detail.drug_id')
            ->where('check_detail.vouchers_check_id', $id)
            ->get();
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataResult);
    }

    public function cancel($id)
    {
        LogEx::methodName($this->className, 'cancel');

        DB::beginTransaction();
        try {
            $this->vouchersCheck->updateOneById($id, ['status' => 2]);
            $checkDetail = $this->checkDetail->findManyBy('vouchers_check_id', $id);
            $check = 0;
            foreach ($checkDetail as $value) {
                $warehouse = $this->warehouse->findOneByCredentials(['drug_id' => $value->drug_id, 'number' => $value->number, 'unit_id' => $value->unit_id]);
                if ($warehouse->quantity < $value->diff_amount) {
                    $check = 1;
                    break;
                } else {
                    $this->warehouse->updateAmount($value->drug_id, $value->diff_amount, null, $value->number);
                }
            }
            if ($check == 0) {
                DB::commit();
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
            } else {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ERROR_QUANTILY);
            }
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function getDetailList(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $input = $request->input();
        $fromDate = $input['from_date'] ?? null;
        $toDate = $input['to_date'] ?? null;
        $data = $this->vouchersCheck->getDetailList($user->drug_store_id, $fromDate, $toDate);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function confirmVouchersCheck(Request $request)
    {
        LogEx::methodName($this->className, 'confirmVouchersCheck');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $vouchersCheckId = $requestInput['vouchers_check_id'] ?? null;
        $checkStatus = $requestInput['check_status'] ?? null;
        $data = Utils::executeRawQuery('select f_vouchers_check_check(?, ?, ?, ?) as result', [$user->drug_store_id, $user->id, $vouchersCheckId, $checkStatus], $request->url(), $requestInput);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
    }

    /**
     * api v3
     * from confirmVouchersCheck
    */
    public function confirmVouchersCheckV3(Request $request)
    {
        LogEx::methodName($this->className, 'confirmVouchersCheckV3');

        $query = $this->vouchersCheckCheckV3($request);;
        $data = $query;

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from f_vouchers_check_check on v1
    */
    public function vouchersCheckCheckV3(Request $request)
    {
        LogEx::methodName($this->className, 'vouchersCheckCheckV3');

        $p_store_id = $request->userInfo->drug_store_id;
        $p_user_id = $request->userInfo->id;
        $requestInput = $request->input();
        $p_vouchers_check_id = $requestInput['vouchers_check_id'] ?? null;
        $p_check_value = $requestInput['check_status'] ?? null;
        $v_current_time = Carbon::now()->format('Y-m-d');

        if (!VouchersCheck::find($p_vouchers_check_id)->whereRaw('check_status = pending')->whereRaw("drug_store_id = {$p_store_id}")) return -1;

        DB::beginTransaction();
        try {
            VouchersCheck::find($p_vouchers_check_id)->update([
                'check_status' => $p_check_value,
                'checked_by' => $p_user_id,
                'updated_at' => $v_current_time
            ]);

            if ($p_check_value == "checked") {
                $tmp_update_data = DB::table(DB::raw('check_detail d'))
                    ->select('d.drug_id','d.number', DB::raw('d.current_amount * b.exchange   as quantity'))
                    ->join(DB::raw('warehouse b'), function($join) {
                        $join->on('b.drug_id','=','d.drug_id')
                            ->on('b.unit_id','=','d.unit_id')
                            ->whereRaw('b.is_check = true');
                    })
                    ->where('d.vouchers_check_id', '=', $p_vouchers_check_id);

                $isUpdate = 0;
                $dataCheck = $tmp_update_data;
                foreach ($dataCheck->get() as $item) {
                    if (!empty($item->quantity)) $isUpdate = 1;
                }

                if ($isUpdate) {
                    $sql_data_bindings = str_replace_array('?', $tmp_update_data->getBindings(), $tmp_update_data->toSql());

                    $warehouseUpdate = DB::table(DB::raw('warehouse w'))
                        ->join(DB::raw("({$sql_data_bindings}) as q"), function ($join) {
                            $join->on('w.drug_id', '=', 'q.drug_id')
                                ->on('w.number', '=', 'q.number');
                        })
                        ->whereRaw('w.is_check = false')
                        ->update([
                            'quantity' => DB::raw('coalesce(q.quantity, 0)/w.exchange')
                        ]);

                    $stock = DB::table(DB::raw('t_stock s'))
                        ->join(DB::raw("({$sql_data_bindings}) q"), function ($join) {
                            $join->on('s.drug_id', '=', 'q.drug_id')
                                ->on('s.number', '=', 'q.number');
                        })
                        ->update([
                            'quantity' => DB::raw('coalesce(q.quantity, 0)')
                        ]);
                }
            }
            DB::commit();

            return $p_vouchers_check_id;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }
}
