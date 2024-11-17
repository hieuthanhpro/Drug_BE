<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Repositories\Permissions\PermissionsRepositoryInterface;
use App\Http\Controllers\Controller;
use App\LibExtension\LogEx;

class PermissionsController extends Controller
{
    protected $className = "Backend\PermissionsController";

    protected $permission;

    public function __construct(PermissionsRepositoryInterface $permission)
    {
        LogEx::constructName($this->className, '__construct');

        $this->permission = $permission;
    }

    public function index()
    {
        LogEx::methodName($this->className, 'index');

        $data = $this->permission->getListPermission();
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
