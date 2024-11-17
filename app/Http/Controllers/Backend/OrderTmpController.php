<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\OrderDetailTmp\OrderDetailTmpRepositoryInterface;
use App\Repositories\OrderTmp\OrderTmpRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderTmpController extends Controller
{
    protected $className = "Backend\OrderTmpController";

    protected $orderTmp;
    protected $orderDetailTmp;
    protected $drugStore;

    public function __construct(
        OrderTmpRepositoryInterface       $orderTmp,
        OrderDetailTmpRepositoryInterface $orderDetailTmp,
        DrugStoreRepositoryInterface      $drugStore
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->orderTmp = $orderTmp;
        $this->orderDetailTmp = $orderDetailTmp;
        $this->drugStore = $drugStore;
    }

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $data = $this->orderTmp->getAllByDrugStore($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }


    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $data = $request->input();
        $orderInsert = array(
            'vat_amount' => $data['vat_amount'] ?? null,
            'receipt_date' => $data['receipt_date'] ?? null,
            'delivery_date' => $data['delivery_date'] ?? null,
            'description' => $data['description'] ?? '',
            'supplier_id' => $data['supplier_id'] ?? null,
            'amount' => $data['amount'],
            'pay_amount' => $data['pay_amount'],
            'supplier_order_code' => $data['supplier_order_code'] ?? '',
            'created_at' => $data['created_at'] ?? null
        );

        $detailOrder = $data['detail_order'];

        DB::beginTransaction();
        try {
            $this->orderTmp->updateOneById($id, $orderInsert);
            $this->orderDetailTmp->deleteAllByCredentials(['order_id' => $id]);
            foreach ($detailOrder as $value) {
                $itemOrder = array(
                    'order_id' => $id,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'cost' => $value['cost'] ?? 0,
                    'package_form' => $value['package_form'] ?? '',
                    'concentration' => $value['concentration'] ?? '',
                    'manufacturer' => $value['manufacturer'] ?? '',
                );

                $this->orderDetailTmp->create($itemOrder);
            }
            DB::commit();
            $result = $this->orderTmp->getDetailById($id);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $data = $request->input();
        $orderInsert = array(
            'drug_store_id' => $user->drug_store_id,
            'vat_amount' => $data['vat_amount'] ?? null,
            'receipt_date' => $data['receipt_date'] ?? null,
            'delivery_date' => $data['delivery_date'] ?? null,
            'description' => $data['description'] ?? '',
            'supplier_id' => $data['supplier_id'] ?? null,
            'created_by' => $user->id,
            'amount' => $data['amount'],
            'pay_amount' => $data['pay_amount'],
            'supplier_order_code' => $data['supplier_order_code'] ?? '',
            'created_at' => $data['created_at'] ?? null
        );

        $detailOrder = $data['detail_order'];

        DB::beginTransaction();
        try {
            $insert = $this->orderTmp->create($orderInsert);
            $lastIdOrder = $insert->id;
            foreach ($detailOrder as $value) {
                $itemOrder = array(
                    'order_id' => $lastIdOrder,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'cost' => $value['cost'] ?? 0,
                    'package_form' => $value['package_form'] ?? '',
                    'concentration' => $value['concentration'] ?? '',
                    'manufacturer' => $value['manufacturer'] ?? '',
                );

                $this->orderDetailTmp->create($itemOrder);
            }
            DB::commit();
            $result = $this->orderTmp->getDetailById($lastIdOrder);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function destroy($id, Request $request)
    {
        LogEx::methodName($this->className, 'destroy');

        $user = $request->userInfo;
        $check = $this->orderTmp->findOneById($id);
        if (empty($check) || $check->drug_store_id != $user->drug_store_id) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->orderTmp->deleteOneById($id);
        $this->orderDetailTmp->deleteManyBy('order_id', $id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function getDetailOrder($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailOrder');

        $user = $request->userInfo;
        $check = $this->orderTmp->findOneById($id);
        if (!empty($check) && $check->drug_store_id == $user->drug_store_id) {
            $data = $this->orderTmp->getDetailById($id);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } else {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
    }
}
