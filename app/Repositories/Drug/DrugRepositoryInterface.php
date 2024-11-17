<?php

namespace App\Repositories\Drug;

use App\Http\Requests\Drug\DrugMasterFilterRequest;
use App\Repositories\RepositoryInterface;

interface DrugRepositoryInterface extends RepositoryInterface
{
    public function filter($drugFilterInput, $drugStoreId, $limit);
    public function filterDrugMaster(DrugMasterFilterRequest $drugMasterFilterRequest);
    public function detail($id, $drugStoreId);
    public function filterForSale($drugFilterInput, $drugStoreId);

    /**
     * @param $drug_store_id
     * @return mixed
     */
    public function getUnitByDrug($drug_store_id, $drug_id);

    public function countDrugByGroup($drug_store_id);

    public function getListDrugWaring($drug_store_id);
    public function getListDrugWaringCombineUnits($drug_store_id);
    public function getDetailDrug($id, $drug_store_id);
    public function getDrugInventory($drug_store_id);
    public function getDrugExpired($drug_store_id);
    public function getAllDrugByStore($drug_store_id);
    public function getListMasterByName($name, $per_page = null);
    public function getListDrugWithUnit($drug_store_id);
    public function getListDrugStore($drug_store_id);
    public function getDrugForDose($drug_id, $unit_id);
    public function getDrugBase($drug_store_id, $name, $perpage);
    public function getDrugByNumber($drug_id, $number);
    public function getListNumber($id);
    public function getTopDrug($drug_store_id, $time, $type);
    public function getDrugBaseById($drug_id);
    public function getAutoListWithPacks(string $drug_store_id, int $modeSearch, string $inputText);


    public function getDrugListES($param);
    public function copyDrugByStoreId($storeId, $destStoreId);

    public function countDrugByStoreId($storeId);
}
