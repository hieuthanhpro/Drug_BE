<?php

namespace App\Http\Controllers\Backend;

use App\Services\SupplierService;
use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\Supplier\SupplierRepositoryInterface;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected $className = "Backend\SupplierController";

    protected $supplier;
    protected $invoice;
    protected $supplierService;

    public function __construct(SupplierRepositoryInterface $supplier, InvoiceRepositoryInterface $invoice, SupplierService $supplierService)
    {
        LogEx::constructName($this->className, '__construct');

        $this->supplier = $supplier;
        $this->invoice = $invoice;
        $this->supplierService = $supplierService;
    }

    public function show(Request $request)
    {
        LogEx::methodName($this->className, 'show');

        $user = $request->userInfo;
        $name = $request->input('name');
        $phone = $request->input('phone');
        $address = $request->input('address');

        $data = $this->supplier->getListSupplier($user->drug_store_id, $name, $phone, $address);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $data = Utils::executeRawQuery('select * from supplier where drug_store_id = ? or refer_id is not null', [$user->drug_store_id]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }


    public function getListSupplier(Request $request)
    {
        LogEx::methodName($this->className, 'getListSupplier');

        $user = $request->userInfo;
        $name = $request->input('name');
        $phone = $request->input('phone');
        $address = $request->input('address');

        $data = $this->supplier->getListSupplier($user->drug_store_id, $name, $phone, $address);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $input = $request->input();

        $name = $input['name'] ?? '';

        if (!empty($name)) {
            $check = $this->supplier->findOneByCredentials(['name' => $name, 'drug_store_id' => $user->drug_store_id]);

            if (empty($check)) {
                $dataInsert = array(
                    'name' => $name,
                    'number_phone' => $input['number_phone'] ?? null,
                    'email' => $input['email'] ?? null,
                    'tax_number' => $input['tax_number'] ?? null,
                    'website' => $input['website'] ?? null,
                    'drug_store_id' => $user->drug_store_id,
                    'address' => $input['address'] ?? null,
                );
                $insert = $this->supplier->create($dataInsert);
                $dataInsert['id'] = $insert->id;
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataInsert);
            } else {
                return \App\Helper::errorResponse(CommonConstant::CONFLICT, CommonConstant::MSG_ALREADY_EXISTS);
            }
        }
        return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
    }

    public function getDetailSupplier($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailSupplier');

        $data = $this->supplier->getDetail($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $check = $this->supplier->findOneById($id);
        $input = $request->input();
        if (!empty($check)) {
            $dataInsert = array(
                'name' => $input['name'] ?? null,
                'number_phone' => $input['number_phone'] ?? null,
                'email' => $input['email'] ?? null,
                'tax_number' => $input['tax_number'] ?? null,
                'website' => $input['website'] ?? null,
                'address' => $input['address'] ?? null,
                'status' => $input['status'] ?? 1,
            );
            $this->supplier->updateOneById($id, $dataInsert);
            $dataInsert['id'] = $id;
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataInsert);
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    public function destroy($id)
    {
        LogEx::methodName($this->className, 'destroy');

        $check = $this->supplier->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->supplier->deleteOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function getListInvoice(Request $request)
    {
        LogEx::methodName($this->className, 'getListInvoice');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery('select * from v3.f_supplier_invoice_list(?)', [Utils::getParams($request->input())], $request->url(), $requestInput);
        $data = Utils::getSumData($data, $requestInput, 'select sum(t.amount) as amount, sum(t.vat_amount) as vat_amount, sum(t.discount) as discount, sum(t.pay_amount) as pay_amount, sum(t.amount) + sum(t.vat_amount) - sum(t.discount) - sum(t.pay_amount) as debt from tmp_output t');
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getList(Request $request)
    {
        LogEx::methodName($this->className, 'getList');

        $requestInput = $request->input();
        $name = $requestInput['name'] ?? null;
        $phone = $requestInput['number_phone'] ?? null;
        $address = $requestInput['address'] ?? null;
        $data = Utils::executeRawQuery('select * from f_supplier_list(?, ?, ?, ?)', [$request->userInfo->drug_store_id, $name, $phone, $address], $request->url(), $requestInput);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getList
    */
    public function getListV3(Request $request)
    {
        LogEx::methodName($this->className, 'getList');

        $requestInput = $request->input();
        //$name = $requestInput['name'] ?? null;
        //$phone = $requestInput['number_phone'] ?? null;
        //$address = $requestInput['address'] ?? null;
        $search = $requestInput['query'] ?? null;
        $supplierInvoiceList = $this->supplierService->supplierInvoiceList([
            'drug_store_id' => $request->userInfo->drug_store_id,
            'search' => $search,
        ]);
        $data = Utils::executeRawQueryV3(
            $supplierInvoiceList,
            $request->url(),
            $requestInput
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * exportListV3
     */
    public function exportListV3(Request $request)
    {
        LogEx::methodName($this->className, 'exportListV3');

        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $name = $requestInput['name'] ?? null;
        $phone = $requestInput['number_phone'] ?? null;
        $address = $requestInput['address'] ?? null;
        $supplierInvoiceList = $this->supplierService->supplierInvoiceList([
            'drug_store_id' => $request->userInfo->drug_store_id,
            'name' => $name,
            'phone' => $phone,
            'address' => $address
        ]);
        $data = Utils::executeRawQueryV3(
            $supplierInvoiceList,
            $request->url(),
            $requestInput
        );

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQueryV3(
                        $supplierInvoiceList,
                        $request->url(),
                        $requestInput,
                         1,
                        3500
                    );
                    break;
                case "current_page":
                    $data = Utils::executeRawQueryV3(
                        $supplierInvoiceList,
                        $request->url(),
                        $requestInput,
                        1
                    );
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = Utils::executeRawQueryV3(
                        $supplierInvoiceList,
                        $request->url(),
                        $requestInput,
                        1,
                        3500
                    );
                    break;
            }
        }

        return $data;
    }

    public function getHistory(Request $request)
    {
        LogEx::methodName($this->className, 'getHistory');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery('select * from v3.f_supplier_history(?)', [Utils::getParams($request->input())], $request->url(), $requestInput);
        $data = Utils::getSumData($data, $requestInput, 'select sum(t.quantity) as quantity, sum(t.cost) as cost, sum(t.vat) as vat, sum(t.cost*t.quantity) as amount from tmp_output t');
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getHistory
    */
    public function getHistoryV3(Request $request)
    {
        LogEx::methodName($this->className, 'getHistory');

        $supplierHistory = $this->supplierService->supplierHistory($request);
        $queries = $supplierHistory;
        $query_sum = $supplierHistory
            ->get()
            ->toArray();
        $requestInput = $request->input();
        $data = Utils::executeRawQueryV3(
            $queries,
            $request->url(),
            $requestInput
        );
        $sum_data = [
            'quantity' => array_sum(array_column($query_sum, 'quantity')),
            'cost' => array_sum(array_column($query_sum, 'cost')),
            'vat' => array_sum(array_column($query_sum, 'vat')),
//            'amount' => array_sum(
//                array_column($query_sum, 'cost')*array_column($query_sum, 'quantity')
//            ),
        ];
        $data = Utils::getSumDataV3(
            $data,
            $requestInput,
            $sum_data
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
