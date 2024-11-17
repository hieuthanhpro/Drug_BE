<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 1/2/2019
 * Time: 11:22 AM
 */

namespace App\Repositories\Permissions;
use Illuminate\Support\Facades\DB;
use App\Models\Permissions;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class PermissionsEloquentRepository extends AbstractBaseRepository implements PermissionsRepositoryInterface
{
    protected $className = "PermissionsEloquentRepository";

    public function __construct(Permissions $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


    public function getListPermission(){
        LogEx::methodName($this->className, 'getListPermission');

        $data = DB::table('permissions')
            ->select(
                'permissions.*',
                'permission_group.title as group_name'
            )
            ->join('permission_group', 'permission_group.id', 'permissions.group_id')
            ->get();
        return $data;
    }

}
