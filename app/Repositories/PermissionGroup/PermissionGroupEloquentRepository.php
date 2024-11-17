<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 1/2/2019
 * Time: 11:22 AM
 */

namespace App\Repositories\PermissionGroup;

use App\Models\PermissionGroup;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class PermissionGroupEloquentRepository extends AbstractBaseRepository implements PermissionGroupRepositoryInterface
{
    protected $className = "PermissionGroupEloquentRepository";

    public function __construct(PermissionGroup $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

}
