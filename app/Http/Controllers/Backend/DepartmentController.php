<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function departmentSave(Request $request)
    {
        LogEx::methodName($this->className, 'saveDepartment');
        $requestInput = $request->input();

        try {
            $data = Utils::executeRawQuery('select * from v3.f_department_save(?) as result', [Utils::getParams($requestInput)]);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function departmentList(Request $request)
    {
        LogEx::methodName($this->className, 'departmentList');

        $requestInput = $request->input();

        try {
            $data = Utils::executeRawQuery('select * from v3.f_department_list(?)', [Utils::getParams($requestInput)], $request->url(), $requestInput);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function departmentDetail(Request $request)
    {
        LogEx::methodName($this->className, 'departmentDetail');
        $requestInput = $request->input();

        try {
            $data = Utils::executeRawQuery('select v3.f_department_detail(?) as result', [Utils::getParams($requestInput)], $request->url(), $requestInput);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }
}
