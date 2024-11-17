<?php

namespace App\Repositories\OrderDetail;
use Illuminate\Support\Facades\DB;
use App\Models\OrderDetail;
use App\Repositories\AbstractBaseRepository;
use App\LibExtension\LogEx;

class OrderDetailEloquentRepository extends AbstractBaseRepository implements OrderDetailRepositoryInterface
{
    protected $className = "OrderDetailEloquentRepository";

    public function __construct(OrderDetail $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }
}
