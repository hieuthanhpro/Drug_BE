<?php

namespace App\Repositories\LinkAds;

use App\Repositories\AbstractBaseRepository;
use App\Models\LinkAds;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\LibExtension\LogEx;

class LinkAdsEloquentRepository  extends AbstractBaseRepository implements LinkAdsRepositoryInterface
{
    protected $className = "LinkAdsEloquentRepository";
    public function __construct(LinkAds $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getAdsNewest($number = 3)
    {
        LogEx::methodName($this->className, 'getAdsNewest');

        return $this->model->orderBy('updated_at', 'DESC')->take($number)->get();
    }
}
