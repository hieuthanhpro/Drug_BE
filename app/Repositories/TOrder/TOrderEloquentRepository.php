<?php

namespace App\Repositories\TOrder;

use App\Models\TOrder;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class TOrderEloquentRepository extends AbstractBaseRepository implements TOrderRepositoryInterface
{
    protected $className = "OrderEloquentRepository";

    public function __construct(TOrder $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


}
