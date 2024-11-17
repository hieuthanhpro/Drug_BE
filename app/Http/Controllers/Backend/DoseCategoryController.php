<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Repositories\DoseCategory\DoseCategoryRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;

class DoseCategoryController extends Controller
{
    protected $className = "Backend\DoseCategoryController";

    protected $doseCategory;

    public function __construct(DoseCategoryRepositoryInterface $doseCategory)
    {
        LogEx::constructName($this->className, '__construct');

        $this->doseCategory = $doseCategory;
    }

    public function index(Request $request){
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $data = $this->doseCategory->findManyBy('drug_store_id',$user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function destroy($id, Request $request){
        LogEx::methodName($this->className, 'destroy');

        $check = $this->doseCategory->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->doseCategory->deleteOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }


    public function update($id, Request $request){
        LogEx::methodName($this->className, 'update');

        $data = $request->input();
        $check = $this->doseCategory->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->doseCategory->updateOneById($id, $data);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function store(Request $request){
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $data = $request->input();
        $data['drug_store_id'] = $user->drug_store_id;
        $chek_name = $this->doseCategory->findOneByCredentials(['name'=>$data['name'],'drug_store_id' => $user->drug_store_id]);
        if (!empty($chek_name)) {
            return \App\Helper::errorResponse(CommonConstant::CONFLICT, CommonConstant::MSG_DATA_EXISTS);
        }
        $insert= $this->doseCategory->create($data);
        $data['id'] = $insert->id;
        unset($data['userInfo']);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function getDetailCategory($id,Request $request){
        LogEx::methodName($this->className, 'getDetailCategory');

        $data = $this->doseCategory->findOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
