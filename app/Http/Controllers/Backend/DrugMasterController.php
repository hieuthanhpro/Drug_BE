<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Repositories\DrugMaster\DrugMasterRepositoryInterface;
use Illuminate\Http\Request;

class DrugMasterController extends Controller
{
    protected $className = "Backend\DrugMasterController";

    protected $drug_master;

    public function __construct(
        DrugMasterRepositoryInterface $drug_master
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->drug_master = $drug_master;
    }

    public function getDrugMasterByName(Request $request)
    {
        LogEx::methodName($this->className, 'getDrugMasterByName');

        $data = Utils::executeRawQuery("select * from v3.f_drug_master_list(?)", [Utils::getParams($request->input())], $request->url(), $request->input());
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getDrugMasterByName
    */
    public function getDrugMasterByNameV3(Request $request)
    {
        LogEx::methodName($this->className, 'getDrugMasterByName');

        $name = $request->input()['query'] ?? null;
        $data = Utils::executeRawQueryV3(
            $this->drug_master->drugMasterListV3($name),
            $request->url(),
            $request->input()
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
