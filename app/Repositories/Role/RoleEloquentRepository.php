<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 11:03 AM
 */

namespace App\Repositories\Role;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class RoleEloquentRepository extends AbstractBaseRepository implements RoleRepositoryInterface
{
    protected $className = "RoleEloquentRepository";

    public function __construct(Role $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getDetail($id){
        LogEx::methodName($this->className, 'getDetail');

        $data = DB::table('role')
            ->select(
                'permissions.*'
            )
            ->join('permission_role', 'role.id', 'permission_role.role_id')
            ->join('permissions', 'permissions.id', 'permission_role.permission_id')
            ->where('role.id',$id)
            ->get();
        return $data;

    }
}
