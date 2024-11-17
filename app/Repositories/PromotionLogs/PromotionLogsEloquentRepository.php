<?php

namespace App\Repositories\PromotionLogs;

use App\Models\PromotionLogs;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class PromotionLogsEloquentRepository extends AbstractBaseRepository implements PromotionLogsRepositoryInterface
{
    protected $className = "PromotionLogsEloquentRepository";

    public function __construct(PromotionLogs $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }
}
