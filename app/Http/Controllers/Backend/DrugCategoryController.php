<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drug\GroupCategoryFilterRequest;
use App\Http\Requests\Drug\GroupCategorySaveRequest;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\Repositories\DrugCategory\DrugCategoryRepositoryInterface;
use App\Services\DrugCategoryService;
use Illuminate\Http\Request;

class DrugCategoryController extends Controller
{
    protected $className = "Backend\DrugCategoryController";

    protected $drugCategory;
    protected $drugCategoryService;

    public function __construct(DrugCategoryRepositoryInterface $drugCategory, DrugCategoryService $drugCategoryService)
    {
        LogEx::constructName($this->className, '__construct');

        $this->drugCategory = $drugCategory;
        $this->drugCategoryService = $drugCategoryService;
    }

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $result_data = array();
        $user = $request->userInfo;
        $data = $this->drugCategory->findManyBy('drug_store_id', $user->drug_store_id);
        if (!empty($data)) {
            foreach ($data as $value) {
                $total = $this->drugCategory->countDrugById($value['id']);
                $value['total_drug'] = $total;
                $result_data[] = $value;
            }
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result_data);
    }

    public function destroy($id, Request $request)
    {
        LogEx::methodName($this->className, 'destroy');

        $check = $this->drugCategory->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $data = $this->drugCategory->deleteOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $data = $request->input();
        $check = $this->drugCategory->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->drugCategory->updateOneById($id, $data);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $data = $request->input();
        $data['drug_store_id'] = $user->drug_store_id;
        $isDrug = empty($data['is_drug']) ? null : $data['is_drug'];
        $checkName = $this->drugCategory->findOneByCredentials(['name' => $data['name'], 'is_drug' => $isDrug, 'drug_store_id' => $user->drug_store_id]);
        if (!empty($checkName)) {
            return \App\Helper::errorResponse(CommonConstant::CONFLICT, CommonConstant::MSG_DATA_EXISTS);
        }
        $insert = $this->drugCategory->create($data);
        $data['id'] = $insert->id;
        unset($data['userInfo']);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getDetailCategory($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailCategory');

        $data = $this->drugCategory->findOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getList(Request $request)
    {
        LogEx::methodName($this->className, 'getList');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $isDrug = empty($requestInput['is_drug']) ? null : $requestInput['is_drug'];
        $drugStoreID = !empty($requestInput['drug_store_id']) ? $requestInput['drug_store_id'] : $user->drug_store_id;
        $data = $this->drugCategory->getList($drugStoreID, $isDrug);
        //$resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        //return response()->json($resp);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getListFilter(Request $request)
    {
        LogEx::methodName($this->className, 'getListFilter');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $isDrug = empty($requestInput['is_drug']) ? null : $requestInput['is_drug'];;
        $drug_store_id = $requestInput['drug_store_id'] ?? 0;
        $query = $requestInput['search_text'] ?? null;
        $data = $this->drugCategory->getList($drug_store_id, $isDrug, $query);
        //$resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        //return response()->json($resp);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
    // New
    public function filter(GroupCategoryFilterRequest $request)
    {
        LogEx::methodName($this->className, 'filter');

        $userInfo = $request->userInfo;
        $requestInput = $request->input();
        $data = $this->drugCategory->filter($requestInput, $userInfo->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function save(GroupCategorySaveRequest $request) {
        LogEx::methodName($this->className, 'save');
        $data = $this->drugCategoryService->createOrUpdate($request);
        if($data){
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::successResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function delete($id){
        LogEx::methodName($this->className, 'delete');
        $this->drugCategory->deleteOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function export(GroupCategoryFilterRequest $groupCategoryFilterRequest){
        LogEx::methodName($this->className, 'export');
        $data = $this->drugCategoryService->export($groupCategoryFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
