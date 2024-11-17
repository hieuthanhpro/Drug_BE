<?php

namespace App\Repositories\Warehouse;

use App\Repositories\RepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

interface WarehouseRepositoryInterface extends RepositoryInterface
{
    public function updateAmount($drug_id, $quantity, $type = null, $nubumer);
    public function creareByNumber($data, $drug_store_id);
    public function updateAmountByUnit($data);
    public function countQuantityByDrug($drug_store_id, $drug_id, $unit_id);
    public function getListPackages(string $drug_store_id, string $drug_id);
    public function updateInvoiceAmount($drug_id, $number, $quantityBasicUnit, $reverse_flag = false);
    public function updateCosts($drug_id, $pre_cost_basic, $main_cost_basic, $current_cost_basic);
    public function getWarehouseInOut(Request $request);
    public function copyWarehouseByStoreId($storeId, $destStoreId);

    public function countWarehouseByStoreId($storeId);

    // New
    public function getUnits($drugId, $drugStoreId);
    public function getNumbers($drugId, $drugStoreId);
    public function filter($warehouseFilterInput, $drugStoreId, $limit);
}
