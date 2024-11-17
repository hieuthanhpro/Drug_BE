<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 10:43 AM
 */

namespace App\Repositories\Debt;

use App\Models\Debt;
use App\Repositories\AbstractBaseRepository;
use App\LibExtension\LogEx;

class DebtEloquentRepository extends AbstractBaseRepository implements DebtRepositoryInterface
{
    protected $className = "DebtEloquentRepository";
    public function __construct(Debt $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


}
