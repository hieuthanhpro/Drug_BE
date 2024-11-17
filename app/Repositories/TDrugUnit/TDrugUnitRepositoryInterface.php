<?php

namespace App\Repositories\TDrugUnit;

use App\Repositories\RepositoryInterface;

interface TDrugUnitRepositoryInterface extends RepositoryInterface
{
    public function copyTDrugUnitByStoreId($destStoreId);
}