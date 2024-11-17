<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DrugMaster;
use App\Repositories\DrugMaster\DrugMasterRepositoryInterface;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\LibExtension\LogEx;

class DrugController extends Controller
{
    protected $className = "Admin\DrugController";
    private $drug;

    public function __construct(DrugMasterRepositoryInterface $drug)
    {
        $this->drug = $drug;
    }

    /**
     * Trả về view quản lý thuốc
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function index()
    {
        LogEx::methodName($this->className, 'index');
        return view('admin.drug.index');
    }


    /**
     * Lấy danh sách thuốc
     * @param Datatables $datatables
     * @return \Illuminate\Http\JsonResponse|null
     */
    public function getListDrug(Datatables $datatables)
    {
        LogEx::methodName($this->className, 'getListDrug');

        $builder = DrugMaster::query()
            ->join('unit', 'unit.id', '=', 'drug_master_data.unit_id')
            ->select('drug_master_data.id', 'drug_master_data.name', 'drug_master_data.drug_code', 'drug_master_data.substances', 'drug_master_data.concentration', 'drug_master_data.company', 'drug_master_data.registry_number', 'drug_master_data.active', 'drug_master_data.image as image', 'unit.name as unit_name')
            ->orderByDesc('drug_master_data.id');
        try {
            return $datatables->eloquent($builder)
                ->editColumn('image', function ($data) {
                    return '<img src="' . $data->image . '" style="width: 100%">';
                })
                ->addColumn('action', function ($data) {
                    return '<button class="btn btn-xs btn-primary btn_upload" onclick="uploadImg(' . $data->id . ')" data-toggle="modal" data-target="#myModal"></i>Upload Ảnh</button>';
                })
                ->rawColumns(['image', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e);
            return null;
        }
    }

    public function upload(Request $request)
    {
        LogEx::methodName($this->className, 'upload');

        $input = $request->all();
        $id = $input['drug_id'];
        $file = null;
        if ($request->file('image')) {
            $file = $request->file('image');
        }
        if ($file == null) {
            return back()->with('errors', 'File ảnh phải là dạng:png,PNG,jpg,JPG,jpeg,JPEG,gif,GIF');
        }
        $extension = $request->file('image')->getClientOriginalExtension();
        $fileSize = filesize($request->file('image'));

        if (!in_array($extension, ['png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG', 'gif', 'GIF'])) {
            return back()->with('errors', 'File ảnh không đúng định dạng');
        }

        if ($fileSize > 2097152) {
            return back()->with('errors', 'File không được lớn hơn 2MB');
        }
        if ($this->drug->findOneById($id) != null) {
            $data['image'] = $this->uploadImage($request);
            $upload = $this->drug->updateOneById($id, $data);
            if ($upload) {
                return back()->with('success', 'Upload ảnh thành công !');
            }
        }
        return back()->with('errors', 'Upload ảnh thất bại !');
    }

    private function uploadImage(Request $request)
    {
        LogEx::methodName($this->className, 'uploadImage');

        $params = [];
        if ($request->hasFile('image')) {
            $options = [
                'folder' => 'drug',
                'type' => 'drug',
            ];
            $params = $this->saveImageCDN($request->file('image'), $options);
        }
        return $params;
    }
}
