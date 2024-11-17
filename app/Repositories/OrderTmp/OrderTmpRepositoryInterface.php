<?php

namespace App\Repositories\OrderTmp;

use App\Repositories\RepositoryInterface;

interface OrderTmpRepositoryInterface extends RepositoryInterface
{
    public function getDetailById($id);
    public function getAllByDrugStore($drug_store_id);
}