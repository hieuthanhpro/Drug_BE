<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:43 AM
 */
namespace App\Repositories\DoseCategory;

use App\Models\DoseCategory;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;

class DoseCategoryEloquentRepository extends AbstractBaseRepository implements DoseCategoryRepositoryInterface
{
    protected $className = "DoseCategoryEloquentRepository";

    public function __construct(DoseCategory $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }



}
