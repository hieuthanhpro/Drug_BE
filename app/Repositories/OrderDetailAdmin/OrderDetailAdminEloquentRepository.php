<?php

namespace App\Repositories\OrderDetailAdmin;
use Illuminate\Support\Facades\DB;
use App\Models\OrderDetailAdmin;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class OrderDetailAdminEloquentRepository extends AbstractBaseRepository implements OrderDetailAdminRepositoryInterface
{
    protected $className = "OrderDetailAdminEloquentRepository";
    public function __construct(OrderDetailAdmin $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }
}
