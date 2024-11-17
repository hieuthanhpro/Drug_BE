<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\LinkAds\LinkAdsRepositoryInterface;
use App\LibExtension\LogEx;

class LinkAdsController extends Controller
{
    protected $className = "Admin\LinkAdsController";
    private $linkAds;

    public function __construct(LinkAdsRepositoryInterface $linkAds)
    {
        LogEx::constructName($this->className, '__construct');

        $this->linkAds = $linkAds;
    }

    public function index()
    {
        LogEx::methodName($this->className, 'index');

        $data = $this->linkAds->findAll();
        return view('admin.linkads.index', compact('data'));
    }

    public function viewCreate()
    {
        LogEx::methodName($this->className, 'viewCreate');

        return view('admin.linkads.create');
    }

    public function viewUpdate($id)
    {
        LogEx::methodName($this->className, 'viewUpdate');

        $data = $this->linkAds->findOneById($id);
        return view('admin.linkads.edit', compact('data'));
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $input = $request->input();

        $create = $this->linkAds->create(['text' => $input['text'], 'link' => $input['link']]);
        if ($create) {
            return redirect()->route('admin.linkads.index')->with('success', 'Tạo nhật quảng cáo thành công');
        }

        return back()->with('errors', 'Tạo quảng cáo không thành công');
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $input = $request->input();

        $update = $this->linkAds->updateOneById($id, ['text' => $input['text'], 'link' => $input['link']]);
        if ($update) {
            return redirect()->route('admin.linkads.index')->with('success', 'Cập nhật quảng cáo thành công');
        }

        return back()->with('errors', 'Cập nhật quảng cáo không thành công');
    }

    public function delete($id)
    {
        LogEx::methodName($this->className, 'delete');

        $data = $this->linkAds->findOneById($id);
        if (!empty($data)){
            $delete = $this->linkads->deleteOneById($id);
            if ($delete){
                return back()->with('success', 'Xóa quảng cáo thành công');
            }else{
                return back()->with('errors', 'Xóa quảng cáo thất bại');
            }
        }else{
            return back()->with('errors', 'Không có thông tin quảng cáo');
        }
    }
}
