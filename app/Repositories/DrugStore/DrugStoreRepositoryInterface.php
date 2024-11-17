<?php

namespace App\Repositories\DrugStore;

use App\Repositories\RepositoryInterface;

interface DrugStoreRepositoryInterface extends RepositoryInterface
{
    public function filterDrugStore($requestInput);
    public function getDrugStoresByStatusAndType($status, $type);
    public function getDrugStoresByStatusAndIds($status, $ids);

    public function deleteDrugStoreOrData($id);
}
