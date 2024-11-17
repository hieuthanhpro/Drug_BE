<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Unit\UnitRepositoryInterface;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;

class UnitController extends Controller
{
    protected $className = "Admin\UnitController";

    private $unit;

    public function __construct(UnitRepositoryInterface $unit)
    {
        LogEx::constructName($this->className, '__construct');

        $this->unit = $unit;
    }

    public function index()
    {
        LogEx::methodName($this->className, 'index');

        $data = $this->unit->findAll();
        return view('admin.unit.index', compact('data'));
    }

    public function create()
    {
        LogEx::methodName($this->className, 'create');

        return view('admin.unit.create');
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $data = $request->all();
        $name = $data['name'] ?? '';

        if (empty($name)) {
            return back()->with('errors', 'chưa nhập tên');
        } else {
            $check = $this->unit->findOneBy('name', $name);
            if (!empty($check)) {
                return back()->with('errors', 'Tên đã tồn tại');
            } else {
                $create = $this->unit->create($data);
                if ($create) {
                    return redirect()->route('admin.unit.index')->with('success', 'tạo tài khoản thành công');
                }
            }
        }
        return back()->with('errors', 'tạo thất bại');
    }

    public function delete($id)
    {
        LogEx::methodName($this->className, 'delete');

        $data = $this->unit->findOneById($id);
        if (!empty($data)) {
            $delete = $this->unit->deleteOneById($id);
            if ($delete) {
                return back()->with('success', 'xóa tài khoản thành công');
            } else {
                return back()->with('errors', 'xóa tài khoản thất bại');
            }
        }
        return back()->with('errors', 'không có thông tin tài khoản');
    }
}
