<?php

namespace App\Repositories\OrderDetailTmp;
use App\Models\OrderDetailTmp;
use Illuminate\Support\Facades\DB;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class OrderDetailTmpEloquentRepository extends AbstractBaseRepository implements OrderDetailTmpRepositoryInterface
{
    protected $className = "OrderDetailTmpEloquentRepository";

    public function __construct(OrderDetailTmp $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

}
