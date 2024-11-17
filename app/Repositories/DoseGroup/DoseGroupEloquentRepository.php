<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:37 AM
 */
namespace App\Repositories\DoseGroup;

use App\Models\DoseGroup;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;
class DoseGroupEloquentRepository extends AbstractBaseRepository implements DoseGroupRepositoryInterface
{
    protected $className = "DoseGroupEloquentRepository";
    public function __construct(DoseGroup $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

}
