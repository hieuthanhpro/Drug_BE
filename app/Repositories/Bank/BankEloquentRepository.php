<?php

namespace App\Repositories\Bank;

use App\Models\Bank;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class BankEloquentRepository extends AbstractBaseRepository implements BankRepositoryInterface
{
    protected $className = "BankEloquentRepository";
    protected $sortMethod = "ASC";

    public function __construct(Bank $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }
}
