<?php

namespace App\Repositories\DrugCategory;

use App\Repositories\RepositoryInterface;

interface DrugCategoryRepositoryInterface extends RepositoryInterface
{
    public function countDrugById($id);
    public function getList($drug_store_id, $isDrug, $searchText = null);
    public function copyDrugCategoryByStoreId($storeId, $destStoreId);
    public function filter($drugCategoryFilterInput, $drugStoreId, $limit);
}