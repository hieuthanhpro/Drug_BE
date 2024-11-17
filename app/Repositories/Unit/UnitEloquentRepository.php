<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 10:43 AM
 */

namespace App\Repositories\Unit;

use App\Models\Unit;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class UnitEloquentRepository extends AbstractBaseRepository implements UnitRepositoryInterface
{
    protected $className = "UnitEloquentRepository";

    public function __construct(Unit $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


}
