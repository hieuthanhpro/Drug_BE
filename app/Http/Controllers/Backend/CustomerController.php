<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerFilterRequest;
use App\Http\Requests\Customer\CustomerRequest;
use App\Http\Requests\Drug\DrugRequest;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Repositories\Customer\CustomerRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\Vouchers\VouchersRepositoryInterface;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $className = "Backend\CustomerController";

    protected $customer;
    protected $customerService;
    protected $vouchers;
    protected $invoice;

    public function __construct(
        CustomerRepositoryInterface $customer,
        CustomerService             $customerService,
        VouchersRepositoryInterface $vouchers,
        InvoiceRepositoryInterface  $invoice
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->customer = $customer;
        $this->customerService = $customerService;
        $this->vouchers = $vouchers;
        $this->invoice = $invoice;
    }

    //New
    public function filter(CustomerFilterRequest $customerFilterRequest)
    {
        LogEx::methodName($this->className, 'filter');
        $data = $this->customer->filterV3($customerFilterRequest->input(), $customerFilterRequest->userInfo->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function export(CustomerFilterRequest $customerFilterRequest)
    {
        LogEx::methodName($this->className, 'export');
        $data = $this->customerService->export($customerFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function save(CustomerRequest $request)
    {
        LogEx::methodName($this->className, 'save');
        $userInfo = $request->userInfo;
        $requestInput = $request->input();
        if (isset($requestInput["id"])) {
            $data = $this->customer->findOneByCredentials(['id' => $requestInput["id"], 'drug_store_id' => $userInfo->drug_store_id]);
            if (!$data) {
                return \App\Helper::successResponse(CommonConstant::NOT_FOUND, CommonConstant::MSG_NOTFOUND);
            }
        }

        $data = $this->customerService->createOrUpdate($requestInput, $userInfo->drug_store_id);
        if ($data) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::successResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    //=================================================//

    /**
     * Lấy ra danh sách khách hàng theo nhà thuốc
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $data = $this->customer->getCustomersByStoreId($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function destroy($id, Request $request)
    {
        LogEx::methodName($this->className, 'destroy');
        $check = $this->customer->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $data = $this->customer->deleteOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $data = $request->input();
        $check = $this->customer->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->customer->updateOneById($id, $data);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $data = $request->input();
        $data['drug_store_id'] = $user->drug_store_id;
        $phone = $data['number_phone'] ?? '';
        if (!empty($phone)) {
            $check = $this->customer->findOneByCredentials(['number_phone' => $phone, 'drug_store_id' => $user->drug_store_id]);
            if (!empty($check)) {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_DATA_EXISTS);
            } else {
                $result = $this->customer->create($data);
                $data['id'] = $result->id;
            }
        } else {
            $result = $this->customer->create($data);
            $data['id'] = $result->id;
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * Lọc danh sách khách hàng
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListCustomer(Request $request)
    {
        LogEx::methodName($this->className, 'getListCustomer');

        $requestInput = $request->input();
        $user = $request->userInfo;
        $data = $this->customer->filter($requestInput, $user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getListInvoice(Request $request)
    {
        LogEx::methodName($this->className, 'getListInvoice');
        $requestInput = $request->input();
        $data = Utils::executeRawQuery('select * from v3.f_customer_invoice_list(?)', [Utils::getParams($request->input())], $request->url(), $requestInput);
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
        $data = Utils::executeRawQuery('select * from f_customer_list(?, ?, ?, ?)', [$request->userInfo->drug_store_id, $name, $phone, $address], $request->url(), $requestInput);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getList
     * Lọc danh sách khách hàng
     */
    public function getListV3(Request $request)
    {
        LogEx::methodName($this->className, 'getListCustomer');

        $query = $this->customer->customerListV3($request);
        $data = Utils::executeRawQueryV3(
            $query,
            $request->url(),
            $request->input()
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
        $query = $this->customer->customerListV3($request);
        $data = Utils::executeRawQueryV3(
            $query,
            $request->url(),
            $request->input(),
            1
        );

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQueryV3(
                        $query,
                        $request->url(),
                        $request->input(),
                        1,
                        3500
                    );
                    break;
                case "current_page":
                    $data = Utils::executeRawQueryV3(
                        $query,
                        $request->url(),
                        $request->input(),
                        1
                    );
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = Utils::executeRawQueryV3(
                        $query,
                        $request->url(),
                        $request->input(),
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
        $data = Utils::executeRawQuery('select * from v3.f_customer_history(?)', [Utils::getParams($request->input())], $request->url(), $requestInput);
        $data = Utils::getSumData($data, $requestInput, 'select sum(t.quantity) as quantity, sum(t.cost) as cost, sum(t.vat) as vat, sum(t.cost * t.quantity) as amount from tmp_output t');
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getHistory
     */
    public function getHistoryV3(Request $request)
    {
        LogEx::methodName($this->className, 'getHistory');

        //$requestInput = $request->input();
        //$data = Utils::executeRawQuery('select * from v3.f_customer_history(?)', [Utils::getParams($request->input())], $request->url(), $requestInput);
        //$data = Utils::getSumData($data, $requestInput, 'select sum(t.quantity) as quantity, sum(t.cost) as cost, sum(t.vat) as vat, sum(t.cost * t.quantity) as amount from tmp_output t');
        $query = $this->customer->customerHistoryV3($request);
        $data = Utils::executeRawQueryV3(
            $query,
            $request->url(),
            $request->input()
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function show($id, Request $request)
    {
        LogEx::methodName($this->className, 'show');
        $user = $request->userInfo;
        $data = $this->customer->getDetail($id,$user->drug_store_id);
        if ($data) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::successResponse(CommonConstant::NOT_FOUND, CommonConstant::MSG_NOTFOUND);
    }
    public function edit($id, Request $request)
    {
        LogEx::methodName($this->className, 'show');
        $user = $request->userInfo;
        $data = $this->customer->getDetail($id,$user->drug_store_id);
        if ($data) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::successResponse(CommonConstant::NOT_FOUND, CommonConstant::MSG_NOTFOUND);
    }

}
