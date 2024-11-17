<?php

namespace App\Repositories\PriceRule;

use App\Models\PriceRule;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class PriceRuleEloquentRepository extends AbstractBaseRepository implements PriceRuleRepositoryInterface
{
    protected $className = "PriceRuleEloquentRepository";

    public function __construct(PriceRule $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }
}
