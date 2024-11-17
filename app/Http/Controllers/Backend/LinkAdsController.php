<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Repositories\LinkAds\LinkAdsRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;

class LinkAdsController extends Controller
{
    protected $className = "Backend\LinkAdsController";

    protected $linkAds;

    public function __construct(LinkAdsRepositoryInterface $linkAds)
    {
        LogEx::constructName($this->className, '__construct');

        $this->linkAds = $linkAds;
    }

    public function index(Request $request){
        LogEx::methodName($this->className, 'index');

        $input = $request->input();
        $data = $this->linkAds->getAdsNewest($input['number']);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
