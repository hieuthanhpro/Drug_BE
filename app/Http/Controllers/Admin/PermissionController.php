<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Permissions\PermissionsRepositoryInterface;
use App\LibExtension\LogEx;

class PermissionController extends Controller
{
    protected $className = "Admin\PermissionController";
    private $permission;

    public function __construct(
        PermissionsRepositoryInterface $permission
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->permission = $permission;
    }

    public function index()
    {
        LogEx::methodName($this->className, 'index');

        $data = $this->permission->findAll();
        return view('admin.permissions.index', compact('data'));
    }

    public function create()
    {
        LogEx::methodName($this->className, 'create');

        return view('admin.permissions.create');
    }
}
