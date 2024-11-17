<?php

namespace App\Services;

use App\Http\Requests\Drug\DrugFilterRequest;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\Drug;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\DrugMaster\DrugMasterRepositoryInterface;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


/**
 * Class DrugService
 * @package App\Services
 */
class DrugService
{
    protected $className = "DrugService";

    protected $drug;
    protected $drugStore;
    protected $drugMasterData;
    protected $warehouse;

    public function __construct(DrugRepositoryInterface       $drug, DrugStoreRepositoryInterface $drugStore,
                                DrugMasterRepositoryInterface $drugMasterData, WarehouseRepositoryInterface $warehouse)
    {
        LogEx::constructName($this->className, '__construct');
        $this->drug = $drug;
        $this->drugStore = $drugStore;
        $this->drugMasterData = $drugMasterData;
        $this->warehouse = $warehouse;
    }

    public function getDetail($id, $drugStoreId, $dataMobile = [])
    {
        $drug = $this->drug->detail($id, $drugStoreId);
        if ($drug->isNotEmpty()) {
            $units = $this->warehouse->getUnits($id, $drugStoreId);
            $numbers = $this->warehouse->getNumbers($id, $drugStoreId);
            $quantity = array_reduce(json_decode(json_encode($numbers)), function ($number, $item) {
                return $number + $item->quantity;
            }, 0);
            $result = (object)$drug[0];
            if (sizeof($units) > 0) {
                $unitBasic = $units->filter(function ($item) {
                    return $item->is_basic === "yes";
                })->first();
                $result->current_cost = (float)$unitBasic->current_cost;
                $result->main_cost = (float)$unitBasic->main_cost;
                $result->unit_id = $unitBasic->unit_id;
                $result->unit_name = $unitBasic->unit_name;
            }
            $result->units = $units;
            $result->numbers = $numbers;
            $result->quantity = $quantity ?? 0;
            $result->isremaininstock = $quantity && $quantity > 0;
            if ($dataMobile) $result->data_mobile = $dataMobile;
            return $result;
        }
        return null;
    }

    public function createOrUpdate($requestInput, $image, $drugStoreId)
    {
        DB::beginTransaction();
        try {
            $drugStore = $this->drugStore->findOneById($drugStoreId);
            $unitBasic = array(
                'unit_id' => $requestInput["unit_id"],
                'current_cost' => $requestInput["current_cost"]
            );

            if ($image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('upload/images'), $imageName);
                $requestInput["image"] = url("upload/images/" . $imageName);
            }
            //($requestInput["userInfo"]);
            //unset($requestInput["user"]);
            //unset($requestInput["drug_code"]);
            $dataRequests = [
                "is_master_data" => Utils::coalesce($requestInput, 'is_master_data', false),
                "is_monopoly" => Utils::coalesce($requestInput, 'is_monopoly', false),
                "name" => Utils::coalesce($requestInput, 'name', null),
                "short_name" => Utils::coalesce($requestInput, 'short_name', null),
                "drug_category_id" => Utils::coalesce($requestInput, 'drug_category_id', null),
                "drug_group_id" => Utils::coalesce($requestInput, 'drug_group_id', null),
                "unit_id" => Utils::coalesce($requestInput, 'unit_id', null),
                //"current_cost" => $requestInput["current_cost"],
                //"main_cost" => $requestInput["main_cost"],
                //"base_ratio" => $requestInput["base_ratio"],
                //"drug_code" => $requestInput["drug_code"],
                "barcode" => Utils::coalesce($requestInput, 'barcode', null),
                "registry_number" => Utils::coalesce($requestInput, 'registry_number', null),
                "country" => Utils::coalesce($requestInput, 'country', null),
                "company" => Utils::coalesce($requestInput, 'company', null),
                "package_form" => Utils::coalesce($requestInput, 'package_form', null),
                "concentration" => Utils::coalesce($requestInput, 'concentration', null),
                "substances" => Utils::coalesce($requestInput, 'substances', null),
                "image" => Utils::coalesce($requestInput, 'image', null),
                //"quantity" => $requestInput["quantity"],
                "warning_unit" => Utils::coalesce($requestInput, 'warning_unit', null),
                "warning_quantity_min" => Utils::coalesce($requestInput, 'warning_quantity_min', null),
                "warning_quantity_max" => Utils::coalesce($requestInput, 'warning_quantity_max', null),
                "warning_days" => Utils::coalesce($requestInput, 'warning_days', null),
                "created_at" => Carbon::now()->toDateTimeString(),
                "updated_at" => Carbon::now()->toDateTimeString()
            ];

            if (isset($requestInput["id"])) {
                $drug = $this->getDetail($requestInput["id"], $drugStoreId);
                if ($drug) {
                    $datas = array_merge(
                        !Str::contains($drug->drug_code, "DQG") ?
                        $dataRequests :
                        array(
                            "is_monopoly" => Utils::coalesce($requestInput, 'is_monopoly', null),
                            "short_name" => Utils::coalesce($requestInput, 'short_name', null),
                            "drug_category_id" => Utils::coalesce($requestInput, 'drug_category_id', null),
                            "drug_group_id" => Utils::coalesce($requestInput, 'drug_group_id', null),
                            "barcode" => Utils::coalesce($requestInput, 'barcode', null),
                            "image" => Utils::coalesce($requestInput, 'image', null),
                            "warning_unit" => Utils::coalesce($requestInput, 'warning_unit', null),
                            "warning_quantity_min" => Utils::coalesce($requestInput, 'warning_quantity_min', null),
                            "warning_quantity_max" => Utils::coalesce($requestInput, 'warning_quantity_max', null),
                            "warning_days" => Utils::coalesce($requestInput, 'warning_days', null),
                        ),
                        ["drug_store_id" => $drugStoreId, "source" => $drugStore->type]
                    );
                    //$this->drug->updateOneById($requestInput["id"], $datas);
                    $updateDrug = DB::table('drug')
                        ->where('id', $requestInput["id"])
                        ->update($datas);
                    $unitBasic["unit_id"] = $drug->unit_id;
                    $updateUnitStatus = $this->updateUnit($requestInput["id"], $drugStoreId, $unitBasic, $requestInput["units"] ?? null);

                    if ($updateUnitStatus === false) {
                        DB::rollBack();
                        return null;
                    }
                    DB::commit();

                    return $this->getDetail($requestInput["id"], $drugStoreId, $datas);
                }
            } else {
                if (empty($requestInput['is_master_data']) || empty($requestInput["master_data_id"]) ||  $dataRequests['is_master_data'] === 'false') {
                    $drugCode = ($requestInput['is_drug'] == 'true') ?
                        'DRUG' . Utils::getSequenceDB('DRUG') :
                        'SP' . Utils::getSequenceDB('DRUG');
                } else {
                    $drugMasterData = $this->drugMasterData->findOneById($requestInput["master_data_id"]);
                    if ($drugMasterData) {
                        $drugCode = $drugMasterData->drug_code;
                        $dataRequests["name"] = $drugMasterData->name;
                        $dataRequests["unit_id"] = $drugMasterData->unit_id;
                        $dataRequests["registry_number"] = $drugMasterData->registry_number;
                        $dataRequests["country"] = $drugMasterData->country;
                        $dataRequests["company"] = $drugMasterData->company;
                        $dataRequests["package_form"] = $drugMasterData->package_form;
                        $dataRequests["concentration"] = $drugMasterData->concentration;
                        $dataRequests["substances"] = $drugMasterData->substances;
                        $unitBasic["unit_id"] = $drugMasterData->unit_id;
                    } else {
                        DB::rollBack();
                        return null;
                    }
                }
                //$drug = $this->drug->create(array_merge($dataRequests, ["drug_store_id" => $drugStoreId, "source" =>
                // $drugStore->type, "drug_code" => $drugCode, "active" => "yes"]));
                $drugId = DB::table('drug')
                    ->insertGetId(array_merge(
                        $dataRequests,
                        [
                            "drug_store_id" => $drugStoreId,
                            "source" => $drugStore->type,
                            "drug_code" => $drugCode,
                            "active" => "yes"])
                    );

                if ($drugId) {
                    $createUnitStatus = $this->createUnit($drugId, $drugStoreId, $unitBasic, $requestInput["units"] ?? null);
                    if ($createUnitStatus === false) {
                        DB::rollBack();
                        return null;
                    }
                    DB::commit();

                    return $this->getDetail(
                        $drugId,
                        $drugStoreId,
                        array_merge(
                            $dataRequests,
                            [
                                "drug_store_id" => $drugStoreId,
                                "source" => $drugStore->type,
                                "drug_code" => $drugCode,
                                "active" => "yes"
                            ])
                    );
                }
            }
            DB::rollBack();

            return null;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);

            return null;
        }
    }

    public function createUnit($drugId, $drugStoreId, $unitBasic, $unitsExchange)
    {
        try {
            // Create unit no basic
            $unitsListRequest = [];
            $unitBasic = array(
                'drug_store_id' => $drugStoreId,
                'drug_id' => $drugId,
                'unit_id' => $unitBasic["unit_id"],
                'is_basic' => 'yes',
                'exchange' => 1,
                'quantity' => 0,
                'main_cost' => 0,
                'pre_cost' => 0,
                'is_check' => true,
                'current_cost' => $unitBasic["current_cost"],
            );
            array_push($unitsListRequest, $unitBasic);
            if ($unitsExchange) {
                foreach ($unitsExchange as $unitExchange) {
                    $request = array(
                        'drug_store_id' => $drugStoreId,
                        'drug_id' => $drugId,
                        'unit_id' => $unitExchange["unit_id"],
                        'is_basic' => 'no',
                        'exchange' => $unitExchange["exchange"],
                        'quantity' => 0,
                        'main_cost' => 0,
                        'pre_cost' => 0,
                        'is_check' => true,
                        'current_cost' => $unitExchange["current_cost"],
                    );
                    array_push($unitsListRequest, $request);
                }
            }
            $this->warehouse->insertBatchWithChunk($unitsListRequest, sizeof($unitsListRequest));
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    public function updateUnit($drugId, $drugStoreId, $unitBasic, $unitsExchange)
    {
        try {
            //Delete all unit no basic
            $this->warehouse->deleteAllByCredentials(["drug_id" => $drugId, "is_basic" => "no"]);
            $unitsBasicWarehouse = $this->warehouse->findManyByCredentials(["drug_id" => $drugId, "is_basic" => "yes"]);

            //Update unit basic
            $this->warehouse->updateAllByCredentials(["drug_id" => $drugId, "is_basic" => "yes"], ["unit_id" => $unitBasic["unit_id"], "current_cost" => $unitBasic["current_cost"]]);

            // Create unit no basic
            $unitsListRequest = [];
            if ($unitsExchange) {
                foreach ($unitsExchange as $unitExchange) {
                    foreach ($unitsBasicWarehouse as $unitBasicWarehouse) {
                        $request = array(
                            'drug_store_id' => $drugStoreId,
                            'drug_id' => $drugId,
                            'unit_id' => $unitExchange["unit_id"],
                            'is_basic' => 'no',
                            'exchange' => $unitExchange["exchange"],
                            'quantity' => $unitBasicWarehouse->quantity / $unitExchange["exchange"],
                            'main_cost' => $unitBasicWarehouse->main_cost * $unitExchange["exchange"],
                            'pre_cost' => $unitBasicWarehouse->pre_cost * $unitExchange["exchange"],
                            'is_check' => $unitBasicWarehouse->is_check,
                            'number' => $unitBasicWarehouse->number,
                            'expiry_date' => $unitBasicWarehouse->expiry_date,
                            'current_cost' => $unitExchange["current_cost"],
                            'mfg_date' => $unitBasicWarehouse->mfg_date,
                        );
                        array_push($unitsListRequest, $request);
                    }
                }
                $this->warehouse->insertBatchWithChunk($unitsListRequest, sizeof($unitsListRequest));
            }
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    public function checkExistDrugMaster($idDrugMaster, $drugStoreId)
    {
        $drugMaster = $this->drugMasterData->findOneById($idDrugMaster);
        if (!$drugMaster) {
            return "no_drug_master";
        }
        $drug = $this->drug->findOneByCredentials(["drug_code" => $drugMaster->drug_code, "drug_store_id" => $drugStoreId]);
        if ($drug) {
            return "exist";
        }
        return null;
    }

    public function checkDeleteDrug($id)
    {
        if (DB::table("t_order_detail")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("order_detail_admin")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("order_detail")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("invoice_detail_tmp")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("control_quality_book_details")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("t_order_drug_match")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("check_detail")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("t_stockym")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("t_stock")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("invoice_detail")->where("drug_id", $id)->exists()) {
            return false;
        }
        if (DB::table("warehouse")->where("drug_id", $id)->where("is_check", "=", false)->exists()) {
            return false;
        }
        return true;
    }

    public function deleteDrug($id)
    {
        DB::beginTransaction();
        try {
            DB::table("warehouse")->where("drug_id", $id)->delete();
            DB::table("t_drug_unit")->where("drug_id", $id)->delete();
            DB::table("drug_autosearch")->where("drug_id", $id)->delete();
            DB::table("drug")->where("id", $id)->delete();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    public function export(DrugFilterRequest $drugFilterRequest)
    {
        LogEx::methodName($this->className, 'export');
        $userInfo = $drugFilterRequest->userInfo;
        $requestInput = $drugFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->drug->filter(["is_drug" => $requestInput["is_drug"] ?? null, "group" => $requestInput["group"] ?? null,
                        "category" => $requestInput["category"] ?? null], $userInfo->drug_store_id, 35000);
                    break;
                case "current_select":
                    if (isset($requestInput["ids"]) && sizeof($requestInput["ids"]) > 0) {
                        $data = $this->drug->filter($drugFilterRequest, $userInfo->drug_store_id);
                    }
                    break;
                case "current_page":
                    $data = $this->drug->filter($drugFilterRequest, $userInfo->drug_store_id);
                    break;
                case "current_search":
                    $drugFilterRequest->request->remove("page");
                    $data = $this->drug->filter($drugFilterRequest, $userInfo->drug_store_id, 35000);
                    break;
            }
        } else {
            $data = $this->drug->filter($drugFilterRequest, $userInfo->drug_store_id);
        }

        return $data;
    }

    /**
     * api v3
     * from f_drug_autosearch_import on v3
    */
    public function drugAutosearchImportV3($inputText, $request)
    {
        LogEx::methodName($this->className, 'export');

        $drug_store_id = $request->userInfo->drug_store_id;
        $p_search_text = $inputText;

        return 1;
    }
}