<?php
namespace App\Repositories\InvoiceWarehouse;

use App\Repositories\RepositoryInterface;

interface InvoiceWarehouseRepositoryInterface extends RepositoryInterface
{
    public function filter($drugFilterInput, $drugStoreId, $limit);
    public function getByIdAndDrugStoreId($id, $drugStoreId);
}
