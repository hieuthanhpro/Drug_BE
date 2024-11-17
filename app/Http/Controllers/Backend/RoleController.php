<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\PermissionRole\PermissionRoleRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;

class RoleController extends Controller
{
    protected $className = "Backend\RoleController";

    protected $role;
    protected $permissionRole;

    public function __construct(
        RoleRepositoryInterface $role,
        PermissionRoleRepositoryInterface $permissionRole

    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->role = $role;
        $this->permissionRole = $permissionRole;
    }

    public function index(Request $request)
    {
        LogEx::methodName($this->className, 'index');

        $user = $request->userInfo;
        $data = $this->role->findManyBy('drug_store_id', $user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }


    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $input = $request->input();
        $user = $request->userInfo;

        $check = $this->role->findOneByCredentials(['drug_store_id' => $user->drug_store_id, 'name' => $input['name']]);

        if (!empty($check)) {
            $resp = $this->responseApi(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ALREADY_EXISTS, $check);
        } else {
            $data = array(
                'drug_store_id' => $user->drug_store_id,
                'name' => $input['name']
            );
            $insert = $this->role->create($data);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $insert);
        }
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $input = $request->input();
        $user = $request->userInfo;
        $data = array(
            'drug_store_id' => $user->drug_store_id,
            'name' => $input['name']
        );

        $update = $this->role->updateOneById($id,$data);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $update);
    }

    public function destroy($id, Request $request)
    {
        LogEx::methodName($this->className, 'destroy');

        $this->role->deleteOneById($id);

        $check = $this->permissionRole->findOneBy('role_id', $id);
        if (!empty($check)) {
            $this->permissionRole->deleteManyBy('role_id', $id);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function addPermissionRole(Request $request)
    {
        LogEx::methodName($this->className, 'addPermissionRole');

        $input = $request->input();
        $user = $request->userInfo;
        $roleId = $input['role_id'];
        $permissionId = $input['permission_id'];

        if (empty($role_id) || empty($permissionId)) {
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
        } else {
            DB::beginTransaction();
            try {
                foreach ($permissionId as $value) {
                    $data = array(
                        'role_id' => $role_id,
                        'permission_id' => $value,
                        'drug_store_id' => $user->drug_store_id
                    );
                    $this->permissionRole->create($data);
                }
                DB::commit();
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
            } catch (\Exception $e) {
                DB::rollback();
                LogEx::try_catch($this->className, $e);
                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
            }
        }
    }

    public function editPermissionRole(Request $request)
    {
        LogEx::methodName($this->className, 'editPermissionRole');

        $input = $request->input();
        $user = $request->userInfo;
        $roleId = $input['role_id'];
        $permissionId = $input['permission_id'];
        $permission = $this->permissionRole->findOneBy('role_id', $roleId);
        if (!empty($permission)) {
            $this->permissionRole->deleteManyBy('role_id', $roleId);
        }
        DB::beginTransaction();
        try {
            foreach ($permissionId as $value) {
                $data = array(
                    'role_id' => $roleId,
                    'permission_id' => $value,
                    'drug_store_id' => $user->drug_store_id
                );
                $this->permissionRole->create($data);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function getRoleDetail($id){
        LogEx::methodName($this->className, 'getRoleDetail');

        $data = $this->role->getDetail($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
