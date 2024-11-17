<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 1/2/2019
 * Time: 11:22 AM
 */

namespace App\Repositories\PermissionRole;

use App\Models\PermissionRole;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class PermissionRoleEloquentRepository extends AbstractBaseRepository implements PermissionRoleRepositoryInterface
{
    protected $className = "PermissionRoleEloquentRepository";

    public function __construct(PermissionRole $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


}
