<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Models\InvoiceDetail;
use App\Models\InvoiceTmp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Repositories\InvoiceTmp\InvoiceTmpRepositoryInterface;
use App\Repositories\InvoiceDetailTmp\InvoiceDetailTmpRepositoryInterface;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;

class InvoiceTmpController extends Controller
{
    protected $className = "Backend\InvoiceTmpController";

    protected $invoiceTmp;
    protected $invoiceDetailTmp;

    public function __construct(
        InvoiceTmpRepositoryInterface       $invoiceTmp,
        InvoiceDetailTmpRepositoryInterface $invoiceDetailTmp
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->invoiceTmp = $invoiceTmp;
        $this->invoiceDetailTmp = $invoiceDetailTmp;
    }

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $data = $this->invoice_tmp->getAllByDrugStore($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $data = $request->input();
        unset($data['userInfo']);
        $detailInvoice = $data['invoice_detail'];
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
        DB::beginTransaction();
        try {
            $this->invoiceTmp->updateOneById($id, $invoiceInsert);
            $this->invoiceDetailTmp->deleteAllByCredentials(['invoice_id' => $id]);
            foreach ($detailInvoice as $value) {
                $itemInvoice = array(
                    'invoice_id' => $id,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'usage' => $value['usage'] ?? '',
                    'number' => $value['number'] ?? '',
                    'expiry_date' => $value['expiry_date'] ?? '',
                    'cost' => $value['cost'] ?? 0,
                    'vat' => $value['vat'] ?? 0,
                    'main_cost' => $value['main_cost'] ?? null,
                    'current_cost' => $value['current_cost'] ?? null
                );
                $this->invoiceDetailTmp->create($itemInvoice);
            }
            DB::commit();
            $result = $this->invoiceTmp->getDetailById($id);
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
        $invoiceInsert = array(
            'drug_store_id' => $user->drug_store_id,
            'invoice_code' => 'PN'.Utils::getSequenceDB('PN'),
            'supplier_invoice_code' => Utils::coalesce($data, 'supplier_invoice_code', null),
            'invoice_type' => Utils::coalesce($data, 'invoice_type', null),
            'warehouse_action_id' => 'Nhập hàng từ NCC',
            'customer_id' => Utils::coalesce($data, 'customer_id', null),
            'amount' => Utils::coalesce($data, 'amount', null),
            'vat_amount' => Utils::coalesce($data, 'vat_amount', 0),
            'pay_amount' => Utils::coalesce($data, 'pay_amount', 0),
            'discount' => Utils::coalesce($data, 'discount', 0),
            'created_by' => Utils::coalesce($data, 'created_by', 0),
            'description' => Utils::coalesce($data, 'description', null),
            'status' => 'done',
            'payment_status' => 'unpaid',
            'receipt_date' => Utils::coalesce($data, 'receipt_date', null),
            'method' => 'direct',
            'payment_method' => 'cash',
            'refer_id' => Utils::coalesce($data, 'refer_id', null),
        );
        $detailInvoice = $data['invoice_detail'];
        DB::beginTransaction();
        try {
            if (!empty($invoiceInsert['invoice_id'])) {
                $this->invoiceTmp->updateOneById($invoiceInsert['invoice_id'], $invoiceInsert);
                $insert = $this->invoiceTmp->findOneById($invoiceInsert['invoice_id']);
                $this->invoicedetailTmp->deleteManyBy('invoice_id', $invoiceInsert['invoice_id']);
            }
            if (empty($insert)) {
                //$insert = $this->invoiceTmp->create($invoiceInsert);
                $insert = InvoiceTmp::create($invoiceInsert);
            }
            $lastIdInvoice = $insert->id;
            //$lastIdInvoice = $insert;dd($lastIdInvoice);
            foreach ($detailInvoice as $value) {
                $itemInvoice = array(
                    'invoice_id' => $lastIdInvoice,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'usage' => $value['usage'] ?? '',
                    'number' => $value['number'] ?? '',
                    'expiry_date' => $value['expiry_date'] ?? '',
                    'cost' => $value['cost'] ?? 0,
                    'vat' => $value['vat'] ?? 0,
                    'main_cost' => $value['main_cost'] ?? null,
                    'current_cost' => $value['current_cost'] ?? null
                );
                //$this->invoiceDetailTmp->create($itemInvoice);
                $listID[] = DB::table('invoice_detail_tmp')->insertGetId($itemInvoice);
            }
            DB::commit();
            $result = $this->invoiceDetailTmp->invoiceTmpDetailV3($lastIdInvoice, $request);

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

        if ($this->invoiceTmp->deleteTmpInvoice($id)) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
        } else {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
    }

    public function getDetailById($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailById');
        $data = Utils::executeRawQuery('select v3.f_invoice_tmp_detail(?) as result', [Utils::getParams($request->input(), array('id' => $id))]);
        if (count($data) > 0) {
            $rs = json_decode($data[0]->result);
            if (isset($rs->invoice)) {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $rs);
            }
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    /**
     * api v3
     * from getDetailById
    */
    public function getDetailByIdV3($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailByIdV3');

        $data = $this->invoiceDetailTmp->invoiceTmpDetailV3($id, $request);

        if (!empty($data['invoice_detail'])) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }

        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    /**
     * api v3
     * from getDetailById
     */
    public function getDetailByIdV3New($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailByIdV3');

        $data = $this->invoiceDetailTmp->invoiceTmpDetailV3New($id, $request);

        if (!empty($data['invoice_detail'])) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }

        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    public function getList(Request $request)
    {
        LogEx::methodName($this->className, 'getList');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery('select * from v3.f_invoice_tmp_list(?)', [Utils::getParams($requestInput)], $request->url(), $requestInput);
        $data = Utils::getSumData($data, $requestInput, 'select sum(t.amount) as amount, sum(t.vat_amount) as vat_amount, sum(t.discount) as discount, sum(t.pay_amount) as pay_amount, sum(t.amount) + sum(t.vat_amount) - sum(t.discount) - sum(t.pay_amount) as debt from tmp_output t');
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     *api v3
     * export tmf
    */
    public function exportTMP(Request $request)
    {
        LogEx::methodName($this->className, 'exportTMP');

        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $query = $this->invoiceDetailTmp->invoiceTmpListV3($request);
        $queries = $query;
        $data = null;

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQueryV3(
                        $queries,
                        $request->url(),
                        $request->input(),
                        1,
                        3500
                    );
                    break;
                case "current_page":
                    $data = Utils::executeRawQueryV3(
                        $queries,
                        $request->url(),
                        $request->input(),
                        1
                    );
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = Utils::executeRawQueryV3(
                        $queries,
                        $request->url(),
                        $request->input(),
                        1,
                        3500
                    );
                    break;
            }
        } else {
            $data = Utils::executeRawQueryV3(
                $queries,
                $request->url(),
                $request->input(),
                1
            );
        }

        return $data;
    }

    /**
     * api v3
     * from getList
    */
    public function getListV3(Request $request)
    {
        LogEx::methodName($this->className, 'getList');

        $query = $this->invoiceDetailTmp->invoiceTmpListV3($request);
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
            'amount' => array_sum(array_column($query_sum, 'amount')),
            'vat_amount' => array_sum(array_column($query_sum, 'vat_amount')),
            'discount' => array_sum(array_column($query_sum, 'discount')),
            'vat_amount' => array_sum(array_column($query_sum, 'vat_amount')),
            'pay_amount' => array_sum(array_column($query_sum, 'pay_amount')),
            'debt' => ( array_sum(array_column($query_sum, 'amount')) +
                array_sum(array_column($query_sum, 'vat_amount')) -
                array_sum(array_column($query_sum, 'discount')) -
                array_sum(array_column($query_sum, 'pay_amount')) )
        ];
        $datas = Utils::getSumDataV3($data, $request->input(), $sum_data);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $datas);
    }
}
