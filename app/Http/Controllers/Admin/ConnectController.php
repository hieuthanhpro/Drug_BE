<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use App\Services\ApiServiceGPP;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;
use Illuminate\View\View;

class ConnectController extends Controller
{
    protected $className = "Admin\ConnectController";

    protected $apiService;

    public function __construct(ApiServiceGPP $apiService)
    {
        LogEx::constructName($this->className, '__construct');
        $this->apiService = $apiService;
    }

    /**
     * Show form kiểm tra kết nối
     * @return Factory|Application|View
     */
    public function showForm()
    {
        LogEx::methodName($this->className, 'showForm');
        return view('admin.connect.form');
    }

    /**
     * Show form kiểm tra kết nối
     * @param Request $request
     */
    public function checkConnectData(Request $request)
    {
        LogEx::methodName($this->className, 'checkConnectData');

        $data = $request->input();
        $dataLoginApi = array(
            'usr' => $data['username'],
            'pwd' => $data['password'],
        );
        $url = CommonConstant::URL_API_CUCDUOC . '/api/tai_khoan/dang_nhap';
        $dataGpp = $this->apiService->loginApi("POST", $url, json_encode($dataLoginApi));

        if ($dataGpp) {
            $dataGpp = json_decode($dataGpp);
            $url_check = CommonConstant::URL_API_CUCDUOC . '/api/lien_thong/hoa_don/' . $data['bar_code'] . '/' . $data['invoice_code'];
            $invoice = $this->apiService->callAPI('GET', $url_check, null, $dataGpp->data->token);
            dd($invoice);
        } else {
            dd("Liên thông Cục Dược bị lỗi");
        }
    }
}
