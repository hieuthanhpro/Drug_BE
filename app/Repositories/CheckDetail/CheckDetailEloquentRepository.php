<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 10:43 AM
 */

namespace App\Repositories\CheckDetail;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\CheckDetail;
use App\Repositories\AbstractBaseRepository;
use App\LibExtension\LogEx;

class CheckDetailEloquentRepository extends AbstractBaseRepository implements CheckDetailRepositoryInterface
{
    protected $className = "CheckDetailEloquentRepository";

    public function __construct(CheckDetail $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


}
