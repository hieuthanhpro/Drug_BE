<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 1/2/2019
 * Time: 11:22 AM
 */

namespace App\Repositories\Prescription;

use App\Models\Prescription;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class PrescriptionEloquentRepository extends AbstractBaseRepository implements PrescriptionRepositoryInterface
{
    protected $className = "PrescriptionEloquentRepository";

    public function __construct(Prescription $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


}
