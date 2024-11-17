<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\Repositories\DoseGroup\DoseGroupRepositoryInterface;
use Illuminate\Http\Request;

class DoseGroupController extends Controller
{
    protected $className = "Backend\DoseGroupController";

    protected $dose_group;

    public function __construct(DoseGroupRepositoryInterface $dose_group)
    {
        LogEx::constructName($this->className, '__construct');

        $this->dose_group = $dose_group;
    }

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $data = $this->dose_group->findManyBy('drug_store_id', $user->drug_store_id)->toArray();
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function destroy($id, Request $request)
    {
        LogEx::methodName($this->className, 'destroy');

        $check = $this->dose_group->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $data = $this->dose_group->deleteOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $data = $request->input();
        $check = $this->dose_group->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->dose_group->updateOneById($id, $data);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $data = $request->input();
        $data['drug_store_id'] = $user->drug_store_id;
        $chek_name = $this->dose_group->findOneByCredentials(['name' => $data['name'], 'drug_store_id' => $user->drug_store_id]);
        if (!empty($chek_name)) {
            return \App\Helper::errorResponse(CommonConstant::CONFLICT, CommonConstant::MSG_DATA_EXISTS);
        }
        $insert = $this->dose_group->create($data);
        $data['id'] = $insert->id;
        unset($data['userInfo']);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getDetailGroup($id)
    {
        LogEx::methodName($this->className, 'getDetailGroup');

        $data = $this->dose_group->findOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
