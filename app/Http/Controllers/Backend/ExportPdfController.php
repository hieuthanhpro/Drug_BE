<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;

class ExportPdfController extends Controller
{
    protected $className = "Backend\ExportPdfController";
    public function __construct()
    {
        LogEx::constructName($this->className, '__construct');

    }

    public function export(Request $request)
    {
        LogEx::methodName($this->className, 'export');

        $input = $request->input();

        $title = $input['title'] ?? "";
        $header = $input['header'] ?? "";
        $table = $input['table'] ?? [];
        $footer = $input['footer'] ?? [];

        $tableHeader = $table['header'];
        $tableData = $table['data'];

        $footerText = $footer['text'];
        $footerSign = [];

        foreach ($footer['sign'] as $item)
        {
            $footerSign[0][] = $item['key'];
            $footerSign[1][] = "";
            $footerSign[2][] = $item['value'];
        }

        try {
            $pdf = PDFFast::loadView('report', compact('title', 'header', 'tableHeader', 'tableData', 'footerText', 'footerSign'))->setPaper('a4', 'portrait');
            return $pdf->download('report.pdf');
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e);
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, "Data xuất pdf chưa đúng định dạng!");
        }
    }
}
