<?php

namespace App\Repositories\DrugGroup;

use App\Repositories\RepositoryInterface;

interface DrugGroupRepositoryInterface extends RepositoryInterface
{
    public function countDrugById($id);
    public function copyDrugGroupByStoreId($storeId, $destStoreId);
    public function filter($drugGroupFilterInput, $drugStoreId, $limit);
}