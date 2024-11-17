<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drug\GroupCategoryFilterRequest;
use App\Http\Requests\Drug\GroupCategorySaveRequest;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\Repositories\DrugGroup\DrugGroupRepositoryInterface;
use App\Services\DrugGroupService;
use Illuminate\Http\Request;

class DrugGroupController extends Controller
{
    protected $className = "Backend\DrugGroupController";

    protected $drugGroup;
    protected $drugGroupService;

    public function __construct(DrugGroupRepositoryInterface $drugGroup, DrugGroupService $drugGroupService)
    {
        $this->drugGroup = $drugGroup;
        $this->drugGroupService = $drugGroupService;
    }

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $result_data = null;
        $user = $request->userInfo;
        $data = $this->drugGroup->findManyBy('drug_store_id', $user->drug_store_id)->toArray();
        if (!empty($data)) {
            foreach ($data as $value) {
                $total = $this->drugGroup->countDrugById($value['id']);
                $value['total_drug'] = $total;
                $result_data[] = $value;
            }
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result_data);
    }

    public function destroy($id, Request $request)
    {
        LogEx::methodName($this->className, 'destroy');

        $check = $this->drugGroup->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $data = $this->drugGroup->deleteOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $data = $request->input();
        $check = $this->drugGroup->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->drugGroup->updateOneById($id, $data);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $data = $request->input();
        $data['drug_store_id'] = $user->drug_store_id;
        $isDrug = empty($data['is_drug']) ? null : $data['is_drug'];
        $checkName = $this->drugGroup->findOneByCredentials(['name' => $data['name'], 'is_drug' => $isDrug, 'drug_store_id' => $user->drug_store_id]);
        if (!empty($checkName)) {
            return \App\Helper::errorResponse(CommonConstant::CONFLICT, CommonConstant::MSG_DATA_EXISTS);
        }
        $insert = $this->drugGroup->create($data);
        $data['id'] = $insert->id;
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getDetailGroup($id)
    {
        LogEx::methodName($this->className, 'getDetailGroup');

        $data = $this->drugGroup->findOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getList(Request $request) {
        LogEx::methodName($this->className, 'getList');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $isDrug = empty($requestInput['is_drug']) ? null : $requestInput['is_drug'];
        $drugStoreID = !empty($requestInput['drug_store_id']) ? $requestInput['drug_store_id'] : $user->drug_store_id;
        $data = $this->drugGroup->getList($drugStoreID, $isDrug);
        //$resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        //return response()->json($resp);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getListFilter(Request $request) {
        LogEx::methodName($this->className, 'getListFilter');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $isDrug = empty($requestInput['is_drug']) ? null : $requestInput['is_drug'];
        $drugStoreID = !empty($requestInput['drug_store_id']) ? $requestInput['drug_store_id'] : $user->drug_store_id;
        $data = $this->drugGroup->getList($drugStoreID, $isDrug);
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
        $data = $this->drugGroup->filter($requestInput, $userInfo->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function save(GroupCategorySaveRequest $request) {
        LogEx::methodName($this->className, 'save');
        $data = $this->drugGroupService->createOrUpdate($request);
        if($data){
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::successResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function delete($id){
        LogEx::methodName($this->className, 'delete');
        $this->drugGroup->deleteOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function export(GroupCategoryFilterRequest $groupCategoryFilterRequest){
        LogEx::methodName($this->className, 'export');
        $data = $this->drugGroupService->export($groupCategoryFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
