<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drug\DrugExportRequest;
use App\Http\Requests\Drug\DrugFilterForSaleRequest;
use App\Http\Requests\Drug\DrugFilterRequest;
use App\Http\Requests\Drug\DrugMasterFilterRequest;
use App\Http\Requests\Drug\DrugRequest;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\LibExtension\UniversalDataMapping;
use App\LibExtension\Utils;
use App\Models\Invoice;
use App\Models\Unit;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\DrugGroup\DrugGroupRepositoryInterface;
use App\Repositories\DrugMaster\DrugMasterRepositoryInterface;
use App\Repositories\Unit\UnitRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Services\DrugService;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DrugController extends Controller
{
    protected $className = "Backend\DrugController";

    protected $drugGroup;
    protected $drug;
    protected $warehouse;
    protected $drugMaster;
    protected $unit;
    protected $drugService;

    public function __construct(
        DrugGroupRepositoryInterface  $drugGroup,
        DrugRepositoryInterface       $drug,
        WarehouseRepositoryInterface  $warehouse,
        DrugMasterRepositoryInterface $drugMaster,
        UnitRepositoryInterface       $unit,
        DrugService                   $drugService
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->drugGroup = $drugGroup;
        $this->drug = $drug;
        $this->warehouse = $warehouse;
        $this->drugMaster = $drugMaster;
        $this->unit = $unit;
        $this->drugService = $drugService;
    }

    public function getUnitByDrug($id, Request $request)
    {
        LogEx::methodName($this->className, 'getUnitByDrug');

        $userInfo = $request->userInfo;
        $data = $this->drug->getUnitByDrug($userInfo->drug_store_id, $id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    // New
    public function filter(DrugFilterRequest $drugFilterRequest)
    {
        LogEx::methodName($this->className, 'filter');
        $data = $this->drug->filter($drugFilterRequest->input(), $drugFilterRequest->userInfo->drug_store_id,$drugFilterRequest->limit);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function filterDrugMaster(DrugMasterFilterRequest $drugMasterFilterRequest)
    {
        LogEx::methodName($this->className, 'filterDrugMaster');
        $data = $this->drug->filterDrugMaster($drugMasterFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function detail($id, Request $request)
    {
        LogEx::methodName($this->className, 'detail');
        $userInfo = $request->userInfo;
        $data = $this->drugService->getDetail($id, $userInfo->drug_store_id);
        if ($data) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::successResponse(CommonConstant::NOT_FOUND, CommonConstant::MSG_NOTFOUND);
    }

    public function save(DrugRequest $request)
    {
        LogEx::methodName($this->className, 'save');
        $userInfo = $request->userInfo;
        $requestInput = $request->input();
        if (isset($requestInput["id"])) {
            $data = $this->drugService->getDetail($requestInput["id"], $userInfo->drug_store_id);
            if (!$data) {
                return \App\Helper::successResponse(CommonConstant::NOT_FOUND, CommonConstant::MSG_NOTFOUND);
            }
        }

        if(!empty($requestInput["is_master_data"]) && $requestInput["is_master_data"] === "true" && isset($requestInput["drug_master_data_id"])){
            $existMasterData = $this->drugService->checkExistDrugMaster($requestInput["drug_master_data_id"], $userInfo->drug_store_id);
            if($existMasterData){
                if($existMasterData === 'no_drug_master'){
                    return \App\Helper::successResponse(CommonConstant::NOT_FOUND, "Không tìm thấy thông tin thuốc Dược quốc gia");
                }else{
                    return \App\Helper::successResponse(CommonConstant::CONFLICT, CommonConstant::MSG_DATA_EXISTS);
                }
            }
        }

        if (!empty($requestInput['image'])) {
            if (Str::contains($requestInput['image'], 'http://')) {
                $requestInput['image'] = $requestInput['image'];
            } else {
                $img = $this->generateImageFromBase64($requestInput['image']);
                $requestInput['image'] = url("upload/images/" . $img);
            }
        };

        $data = $this->drugService->createOrUpdate($requestInput, $request->image_file, $userInfo->drug_store_id);
        if ($data) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::successResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function updateStatusV3(Request $request)
    {
        LogEx::methodName($this->className, 'updateStatusV3');
        $requestInput = $request->input();
        $userInfo = $request->userInfo;
        $ids = $requestInput["ids"];
        $type = $requestInput["type"];
        if (sizeof($ids) === 1) {
            $drug = $this->drug->findOneByCredentials(array("id" => $ids[0], "drug_store_id" => $userInfo->drug_store_id));
            if (!$drug) {
                return \App\Helper::successResponse(CommonConstant::NOT_FOUND, CommonConstant::MSG_NOTFOUND);
            }
        }
        $this->drug->updateManyByIds($ids, ["active" => $type === "start" ? "yes" : "no"]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function delete($id, Request $request){
        LogEx::methodName($this->className, 'delete');
        $userInfo = $request->userInfo;
        $drug = $this->drug->findOneByCredentials(["id" => $id, "drug_store_id" => $userInfo->drug_store_id]);
        if(!$drug){
            return \App\Helper::successResponse(CommonConstant::NOT_FOUND, CommonConstant::MSG_NOTFOUND);
        }
        $isEligible = $this->drugService->checkDeleteDrug($id);
        if(!$isEligible){
            return \App\Helper::successResponse(CommonConstant::BAD_REQUEST, "Không thể xóa thuốc hoặc sản phẩm này");
        }
        $isDelete = $this->drugService->deleteDrug($id);
        if($isDelete){
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
        }
        return \App\Helper::successResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function export(DrugFilterRequest $drugFilterRequest){
        LogEx::methodName($this->className, 'export');
        $data = $this->drugService->export($drugFilterRequest);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function filterForSale(DrugFilterForSaleRequest $drugFilterForSaleRequest)
    {
        LogEx::methodName($this->className, 'getDrugForSale');
        $data = $this->drug->filterForSale($drugFilterForSaleRequest->input(), $drugFilterForSaleRequest->userInfo->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    //=============================================//

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $user = $request->userInfo;
        $input = $request->input();
        $drugInfo = Utils::coalesceMapping(
            $input,
            [
                'drug_group_id' => ['-', null],
                'drug_category_id' => ['-', null],
                'expiry_date' => ['-', null],
                'name' => ['-', ''],
                'short_name' => ['-', ''],
                'substances' => ['-', ''],
                'concentration' => ['-', ''],
                'country' => ['-', ''],
                'company' => ['-', ''],
                'package_form' => ['-', ''],
                'registry_number' => ['-', ''],
                'description' => ['-', ''],
                'active' => ['-', '']
            ]
        );
        $drugInfo['drug_store_id'] = $user->drug_store_id;

        if (isset($input['image'])) {
            $image = $input['image'];
            // Check image is link or base64
            if (strpos($image, 'http') === 0) {
                $link_img = $image;
            } else {
                $link_img = url("upload/images/" . $this->generateImageFromBase64($image));
            }

            $drugInfo = array_merge($drugInfo, array(
                'drug_code' => Utils::coalesce($input, 'drug_code', ''),
                'barcode' => Utils::coalesce($input, 'drug_code', ''),
                'image' => $link_img,
                'usage' => Utils::coalesce($input, 'usage', null)
            ));
        }
        // Qui cach dong goi
        $dataWarehouse = $input['warehouse'];

        /* kiểm tra thuốc tồn tại chưa*/
        $isExistDrug = $this->drug->findOneByCredentials(['drug_store_id' => $user->drug_store_id, 'id' => $input['id']]);

        if (empty($isExistDrug)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        } else {
            /* insert database */
            DB::beginTransaction();
            try {
                // Update Drug information
                $this->drug->updateOneById($input['id'], $drugInfo);
                if ($input['isEdit_DrugUnit']) {
                    // Delete all package for Drug
                    $this->warehouse->deleteAllByCredentials(['drug_id' => $input['id'], 'drug_store_id' => $user->drug_store_id]);
                    foreach ($dataWarehouse as $master_warehouse) {
                        $dataInsertWarehouse = Utils::coalesceMapping(
                            $master_warehouse,
                            [
                                'unit_id' => '-',
                                'is_basic' => '-',
                                'exchange' => '-',
                                'warning_quantity' => ['-', null],
                            ]
                        );
                        $dataInsertWarehouse = array_merge($dataInsertWarehouse, array(
                            'drug_store_id' => $user->drug_store_id,
                            'drug_id' => $input['id'],
                            'is_check' => true,
                            'quantity' => null
                        ));
                        // Insert master data to warehouse
                        $this->warehouse->create($dataInsertWarehouse);
                    }
                }

                DB::commit();
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
            } catch (\Exception $e) {
                DB::rollback();
                LogEx::try_catch($this->className, $e);
                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
            }
        }
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $user = $request->userInfo;
        $input = $request->input();
        $imgBase64 = $input['image'] ?? '';
        if (!empty($imgBase64)) {
            $img = $this->generateImageFromBase64($imgBase64);
            $linkImg = url("upload/images/" . $img);
        } else {
            $linkImg = '';
        }
        $id = $input['id'] ?? null;
        if (empty($id)) {
            $isMasterData = null;
        } else {
            $isMasterData = 1;
        }
        if (!empty($input['drug_code'])) {
            $drugCode = $input['drug_code'];
        } else {
            // random code for DRUG (not master drug)
            $drugCode = 'DRUG' . Utils::getSequenceDB('DRUG');
        }
        $barCode = $input['drug_code'] ?? '';
        $name = $input['name'] ?? '';
        $checkCode = $this->drug->findOneByCredentials(['drug_code' => $barCode, 'drug_store_id' => $user->drug_store_id]);
        if (empty($checkCode)) {
            /*data input*/
            $drugInfo = array(
                'drug_store_id' => $user->drug_store_id,
                'drug_group_id' => $input['drug_group_id'] ?? null,
                'drug_category_id' => $input['drug_category_id'] ?? null,
                'drug_code' => $drugCode,
                'expiry_date' => $input['expiry_date'] ?? null,
                'barcode' => $barCode, // Fix barcode null
                'vat' => $input['vat'] ?? null,
                'is_master_data' => $isMasterData,
                'name' => $input['name'],
                'short_name' => $input['short_name'] ?? '',
                'substances' => $input['substances'] ?? '',
                'concentration' => $input['concentration'] ?? '',
                'country' => $input['country'] ?? '',
                'company' => $input['company'] ?? '',
                'package_form' => $input['package_form'] ?? '',
                'registry_number' => $input['registry_number'] ?? '',
                'description' => isset($input['company']) ? $input['description'] : '',
                'image' => $linkImg ?? null,
                'usage' => $input['usage'] ?? null,
            );
            $dataWarehouse = $input['warehouse'];

            /* insert database */
            DB::beginTransaction();
            try {
                // Insert drug information to database
                $this->drug->create($drugInfo);
                $lastDrug = $this->drug->findOneByCredentials(['name' => $name, 'drug_store_id' => $user->drug_store_id]);
                foreach ($dataWarehouse as $value) {
                    // Initialize quantity 0 with every units
                    $data_insert_warehouse = array(
                        'drug_store_id' => $user->drug_store_id,
                        'drug_id' => $lastDrug->id,
                        'unit_id' => $value['unit_id'],
                        'is_basic' => $value['is_basic'],
                        'exchange' => $value['exchange'],
                        'quantity' => 0,
                        'warning_quantity' => $value['warning_quantity'] ?? null,
                        'main_cost' => $value['main_cost'] ?? null,
                        'pre_cost' => $value['pre_cost'] ?? null,
                        'current_cost' => $value['current_cost'] ?? null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    );
                    $this->warehouse->create($data_insert_warehouse);
                }
                DB::commit();

                $dataDrug = $this->drug->getDrugBaseById($lastDrug->id);
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataDrug);
            } catch (\Exception $e) {
                DB::rollback();
                LogEx::try_catch($this->className, $e);
                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
            }
        } else {
            return \App\Helper::errorResponse(CommonConstant::CONFLICT, CommonConstant::MSG_ALREADY_EXISTS);
        }
    }

    /**
     * @throws \Exception
     */
    public function destroy($id, Request $request)
    {
        LogEx::methodName($this->className, 'destroy');

        $user = $request->userInfo;
        $check = $this->drug->findOneById($id);
        if (empty($check)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->drug->deleteOneById($id);
        $this->warehouse->deleteAllByCredentials(['drug_id' => $id, 'drug_store_id' => $user->drug_store_id]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function getDetailDrug($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailDrug');

        $user = $request->userInfo;
        if (empty($id)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $data = $this->drug->getDetailDrug($id, $user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getAllDrug(Request $request)
    {
        LogEx::methodName($this->className, 'getAllDrug');

        $user = $request->userInfo;
        $data = $this->drug->getAllDrugByStore($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function setupCost(Request $request)
    {
        LogEx::methodName($this->className, 'setupCost');

        $input = $request->input();
        $drugId = $input['drug_id'];
        $exchange = $input['exchange'];
        $currentCost = $input['current_cost'] / $exchange;
        $allNumber = $this->warehouse->findManyByCredentials(['drug_id' => $drugId]);
        foreach ($allNumber as $item) {
            if ($item->is_basic == 'yes') {
                $this->warehouse->updateOneById($item->id, ['current_cost' => $currentCost]);
            } else {
                $this->warehouse->updateOneById($item->id, ['current_cost' => $currentCost * $item->exchange]);
            }
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function show(Request $request)
    {
        LogEx::methodName($this->className, 'show');

        $user = $request->userInfo;
        $input = $request->input();
        $perPage = $input['perPage'] ?? '';
        $name = $input['name'] ?? '';
        $data = $this->drug->getDrugBase($user->drug_store_id, $name, $perPage);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getAllWithUnit(Request $request)
    {
        LogEx::methodName($this->className, 'getAllWithUnit');

        $user = $request->userInfo;
        $data = $this->drug->getListDrugWithUnit($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function insertByMasterData(Request $request)
    {
        LogEx::methodName($this->className, 'insertByMasterData');

        $user = $request->userInfo;
        $listId = $request->input('list_id');
        foreach ($listId as $id) {
            $drugInfo = $this->drug->findOneById($id);
            if (!empty($drug_info)) {
                $ware_house = array(
                    'drug_store_id' => $user->drug_store_id,
                    'drug_id' => $id,
                    'unit_id' => $drugInfo->unit_id,
                    'is_basic' => 'yes',
                    'quantity' => 0,
                    'current_cost' => null
                );
                $this->warehouse->create($ware_house);
            }
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function deleteManyById(Request $request)
    {
        LogEx::methodName($this->className, 'deleteManyById');

        $listId = $request->input('arrary_id');
        foreach ($listId as $id) {
            $check = $this->drug->findOneById($id);
            if ($check->is_master_data == 0) {
                $this->drug->deleteOneById($id);
                $this->warehouse->deleteOneBy('drug_id', $id);
            } else {
                $this->warehouse->deleteManyBy('drug_id', $id);
            }
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function getDrugMasterByName(Request $request)
    {
        LogEx::methodName($this->className, 'getDrugMasterByName');

        $input = $request->input();
        $name = $input['name'] ?? '';
        $perPage = $input['per_page'] ?? null;
        $data = $this->drug->getListMasterByName($name, $perPage);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function updateAmout(Request $request)
    {
        LogEx::methodName($this->className, 'updateAmout');

        $input = $request->input();
        $data = array(
            'drug_id' => $input['drug_id'],
            'unit_id' => $input['unit_id'],
            'amount' => $input['amount'],
            'number' => $input['number']
        );
        $result = $this->warehouse->updateAmountByUnit($data);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
    }

    public function getDrugByNumber($drug_id, Request $request)
    {
        LogEx::methodName($this->className, 'getDrugByNumber');

        $input = $request->input();
        $number = $input['number'] ?? '';
        $data = $this->drug->getDrugByNumber($drug_id, $number);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getAllNumberDrug($id)
    {
        LogEx::methodName($this->className, 'getAllNumberDrug');

        $data = $this->drug->getListNumber($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function checkDrugMaster(Request $request)
    {
        LogEx::methodName($this->className, 'checkDrugMaster');

        $input = $request->input();
        $drugCode = $input['drug_code'];
        $unitName = $input['unit_name'];
        $drugName = $input['drug_name'];
        $registryNumber = $input['registry_number'];
        // validate năm hạn sử dụng cho datatype timestamp
        $expiryDate = !empty($input['expiry_date']) ? rtrim($input['expiry_date']) : '';
        if (!empty($expiry_date)) {
            $yearExpiryDate = substr($expiry_date, 0, 4);
            $yearExpiryDate = intval($yearExpiryDate);
        }

        if (empty(rtrim($drugCode))) {
            if (!empty($expiry_date) && !empty($yearExpiryDate) && ($yearExpiryDate > 2037)) {
                $msg = "Hạn sử dụng không được quá 2037";
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
            } else {
                $msg = "Dữ liệu khớp";
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, $msg);
            }
        }
        $drug = $this->drugMaster->findOneBy('drug_code', $drugCode);
        if (empty($drug)) {
            $msg = "Mã thuốc quốc gia không đúng";
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
        }
        $unit = $this->unit->findOneById($drug->unit_id);
        if (rtrim($unit->name) != rtrim($unitName)) {
            $msg = "Đơn vị cơ bản không khớp với dữ liệu quốc gia";
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
        } elseif (rtrim($drugName) != rtrim($drug->name)) {
            $msg = "Tên thuốc không khớp";
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
        } elseif (rtrim($registryNumber) != rtrim($drug->registry_number)) {
            $msg = "Số đăng ký không khớp";
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
        } elseif (!empty($expiryDate) && !empty($yearExpiryDate) && ($yearExpiryDate > 2037)) {
            $msg = "Hạn sử dụng không được quá 2037";
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
        }
        $msg = "Dữ liệu khớp";
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, $msg);
    }

    public function getBarcode(Request $request)
    {
        LogEx::methodName($this->className, 'getBarcode');

        $result['success'] = false;
        $input = $request->input();

        $drugId = $input['drug_id'] ?? '';
        $unitId = $input['unit_id'] ?? '';
        $number = $input['number'] ?? '';

        if (empty($drugId) || empty($unitId) || empty($number)) {
            $msg = "Thông tin để lấy thuốc chưa đủ!";
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
        }

        $warehouse = $this->warehouse->findOneByCredentials(['drug_id' => $drugId, 'unit_id' => $unitId, 'number' => $number]);
        if (!$warehouse) {
            $msg = "Không tìm thấy thuốc!";
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, $msg);
        }
        $barcode = "" . str_pad($warehouse->id, 8, "0", STR_PAD_LEFT);
        $batchNum = $number;

        $manufacturingDate = $warehouse->manufacturing_date <> null ? date_format(date_create($warehouse->manufacturing_date), "ymd") : "";
        $expiryDate = $warehouse->expiry_date <> null ? date_format(date_create($warehouse->expiry_date), "d/m/Y") : "";
        $quantity = $request->input('quantity', 1);
        $drugName = $request->input('drug_name', '');
        $cost = $request->input('cost', 0);
        $unit = $request->input('unit', '');
        $printType = $request->input('printType', "1");

        try {
            $pdf = \App::make('dompdf.wrapper');

            $customPaper = array(
                0,
                0,
                93.52,
                215.433
            );

            if ($printType == 1)
                // Print barcode
                $pdf->loadView('barcode', compact('barcode', 'quantity', 'drugName', 'cost', 'unit', 'batchNum', 'expiryDate'))->setPaper($customPaper, 'landscape');
            else
                // Print qrcode
                $pdf->loadView('qrcode', compact('barcode', 'quantity', 'drugName', 'cost', 'unit', 'batchNum', 'manufacturingDate', 'expiryDate'))->setPaper($customPaper, 'landscape');

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, base64_encode($pdf->download('barcode.pdf')));
        } catch (Exception $e) {
            LogEx::try_catch($this->className, $e);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function decodeBarcode(Request $request)
    {
        LogEx::methodName($this->className, 'decodeBarcode');

        $input = $request->input();
        $barcode = $input['barcode'];

        if (empty($barcode)) {
            $msg = "Barcode chưa đúng định dạng";
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, $msg);
        }

        $idWarehouse = intval($barcode);
        $warehouse = $this->warehouse->findOneById($idWarehouse);
        if (!isset($warehouse)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $warehouse);
    }

    public function autoListWithPackages4ImportShort($inputText, Request $request)
    {
        LogEx::methodName($this->className, 'autoListWithPackages4ImportShort');

        return $this->autoListWithPackages4Import(1, $inputText, $request);
    }

    public function autoListWithPackages4Import($modeSearch, $inputText, Request $request)
    {
        LogEx::methodName($this->className, 'getAutocomListWithPackages');

        $requestInput = $request->input();
        $page = $requestInput['page'] ?? 1;
        $perPage = $requestInput['per_page'] ?? 50;
        $drugStoreId = $requestInput['userInfo']['drug_store_id'] ?? 0;

        // Get list drug from elasticsearch
        $data = $this->drug->getDrugListES(Utils::getParamsString($requestInput, array("page" => $page, "per_page" => $perPage, "drug_store_id" => $drugStoreId, "search_text" => $inputText), false));
        $ids = null;
        if ($data['total'] > 0) {
            // Get list id from response elasticsearch
            $ids = '{' . collect(Utils::getIds($data['items']))->implode(',') . '}';
        }
        // Get drug by list ids
        $items = [];
        if (isset($ids)) {
            $items = Utils::executeRawQuery("select * from v3.f_drug_by_ids(?)", [Utils::getParams($requestInput, array("drug_ids" => $ids), false)]);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $items);
    }

    public function autoListFavorite($inputText, Request $request)
    {
        LogEx::methodName($this->className, 'getAutocomListWithPackages');

        if ($inputText == 'search') {
            $request['search_text'] = $request['query'] ?? null;
            $query = "select * from v3.f_drug_autosearch_import(?)";
            DB::statement("create temp table tmp_output as $query", [Utils::getParams($request->input())]);
            $data = DB::select("select * from tmp_output limit 100", []);
        } else {
            $data = Utils::executeRawQuery("select * from v3.f_drug_autosearch_import(?)", [Utils::getParams($request->input())]);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from autoListFavorite
    */
    public function autoListFavoriteV3($inputText, Request $request)
    {
        LogEx::methodName($this->className, 'autoListFavoriteV3');

        $p_drug_store_id = $request->userInfo->drug_store_id;
        $searchText = $request["query"] ?? null;
        $queryDB = "1 = 1";

        if (!empty($searchText)) {
            $name = trim($searchText);
            $queryDB = $queryDB . " AND (d.name ~* '" . $name
                . "' or d.drug_code  ~* '" . $name
                . "' or d.barcode  ~* '" . $name
                . "' or d.substances  ~* '" . $name
                . "' or d.concentration  ~* '" . $name
                . "' or d.company  ~* '" . $name
                . "' or d.registry_number  ~* '" . $name
                ."')";
        }

        $tmp_favor = DB::table(DB::raw('drug d'))
            ->select('d.id',DB::raw('count(*) as cnt'))
            ->join(DB::raw('invoice_detail id'), function($join) {
                $join->on('id.drug_id','=','d.id')
                    ->whereRaw('id.updated_at > current_date - 30')
                ;
            })
            ->where('d.drug_store_id', '=', $p_drug_store_id)
            ->groupBy('d.id');

        $sql_with_bindings = str_replace_array('?', $tmp_favor->getBindings(), $tmp_favor->toSql());

        $query = DB::table(DB::raw('drug d'))
            ->select(
                'd.id',
                'd.name','d.short_name','d.substances',
                'd.concentration','d.package_form','d.company',
                'd.drug_code','d.country','d.active as active',
                'd.image','d.description','d.registry_number',
                'd.warning_days','d.warning_quantity_max',
                DB::raw("(select wh.main_cost
                from warehouse wh join unit u on u.id = wh.unit_id where
                wh.is_check = 'TRUE' and
                wh.drug_store_id = $p_drug_store_id and
                wh.drug_id = d.id and
                wh.is_basic = 'yes') as main_cost"),
                DB::raw("(select wh.pre_cost
                from warehouse wh join unit u on u.id = wh.unit_id where
                wh.is_check = 'TRUE' and
                wh.drug_store_id = $p_drug_store_id and
                wh.drug_id = d.id and
                wh.is_basic = 'yes') as pre_cost"),
                DB::raw("(select wh.current_cost
                from warehouse wh join unit u on u.id = wh.unit_id where
                wh.is_check = 'TRUE' and
                wh.drug_store_id = $p_drug_store_id and
                wh.drug_id = d.id and
                wh.is_basic = 'yes') as current_cost"),
                DB::raw("(select wh.unit_id
                from warehouse wh join unit u on u.id = wh.unit_id where
                wh.is_check = 'TRUE' and
                wh.drug_store_id = $p_drug_store_id and
                wh.drug_id = d.id and
                wh.is_basic = 'yes') as unit_id"),
                DB::raw("(select u.name
                from warehouse wh join unit u on u.id = wh.unit_id where
                wh.is_check = 'TRUE' and
                wh.drug_store_id = $p_drug_store_id and
                wh.drug_id = d.id and
                wh.is_basic = 'yes') as unit_name"),
                DB::raw("(select json_agg(json_build_object(
                                    'number', wh.number,
                                    'expiry_date', wh.expiry_date,
                                    'quantity', wh.quantity,
                                    'main_cost', wh.main_cost,
                                    'current_cost', wh.current_cost,
                                    'is_basic', wh.is_basic,
                                    'exchange', wh.exchange,
                                    'unit_id', wh.unit_id,
                                    'unit_name', u.name
                                ))::jsonb from warehouse wh join unit u on u.id = wh.unit_id where
                                wh.is_check = 'no' and
                                wh.drug_store_id = $p_drug_store_id and
                                wh.drug_id = d.id and
                                wh.quantity >= 1) as units"),
                DB::raw("(select json_agg(json_build_object(
                                    'number', warehouse.number,
                                    'expiry_date', warehouse.expiry_date,
                                    'quantity', warehouse.quantity
                                    ))::jsonb from warehouse where
                                    warehouse.drug_store_id = $p_drug_store_id and
                                    warehouse.drug_id = d.id and
                                    warehouse.is_basic = 'yes' and
                                    warehouse.is_check = false and
                                    warehouse.quantity >= 1) as numbers")
            )
            ->leftJoin(DB::raw("($sql_with_bindings) as t"),'t.id','=','d.id')
            ->where('d.drug_store_id', '=', $p_drug_store_id)
            ->where('d.active','=','yes')
            ->whereRaw($queryDB)
            ->orderBy('d.name','ASC');

        if ($inputText == 'search') {
            $subto = $query;
            $data = $subto
                ->limit(100)
                ->get();
        } else {
            $subfrom = $query;
            $data = Utils::executeRawQueryV3(
                $subfrom,
                $request->url(),
                $request->input()
            );
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * Tìm kiếm thuốc từ GDP theo các tiêu chí
     * drug_store_id: ID nhà thuốc, có thể null
     * search_text: Tên thuốc
     * source: gdp/gpp
     * drug_group_id: ID nhóm thuốc
     * drug_category_id: ID danh mục thuốc
     * alphabet_group: Tên thuốc theo alphabet a-d, e-g, ...
     * is_drug: true (là thuốc), false: là sản phẩm
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterDrugByCriteria(Request $request)
    {
        LogEx::methodName($this->className, 'filterDrugByCriteria');

        $requestInput = $request->input();
        $page = $requestInput['page'] ?? 1;
        $perPage = $requestInput['per_page'] ?? 10;
        $drugStoreId = $requestInput['drug_store_id'] ?? 0;

        // Get list drug from elasticsearch
        $data = $this->drug->getDrugListES(Utils::getParamsString($requestInput, array("page" => $page, "per_page" => $perPage, "drug_store_id" => $drugStoreId), false));
        $ids = null;
        if ($data['total'] > 0) {
            // Get list id from response elasticsearch
            $ids = '{' . collect(Utils::getIds($data['items']))->implode(',') . '}';
        }

        // Get drug by list ids
        $items = [];
        if (isset($ids)) {
            $items = Utils::executeRawQuery("select * from v3.f_drug_by_ids(?)", [Utils::getParams($requestInput, array("drug_ids" => $ids), false)]);
        }

        // Build response with item and pagination
        if (!empty($items->data)) {
            $data = new LengthAwarePaginator($items->data, $data['total'], $perPage, $page);

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } else {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ERROR);
        }

    }

    /**
     * api v3
     * from filterDrugByCriteria
    */

    public function filterDrugByCriteriaV3(Request $request)
    {
        LogEx::methodName($this->className, 'filterDrugByCriteriaV3');

        $requestInput = $request->input();
        $page = $requestInput['page'] ?? 1;
        $perPage = $requestInput['per_page'] ?? 10;
        $drugStoreId = $requestInput['drug_store_id'] ?? 0;

        // Get list drug from elasticsearch
        $data = $this->drug->getDrugListES(Utils::getParamsString($requestInput, array("page" => $page, "per_page" => $perPage, "drug_store_id" => $drugStoreId), false));
        $ids = null;
        if ($data['total'] > 0) {
            // Get list id from response elasticsearch
            $ids = implode(",", array_column($data['items'], 'id'));
        }
        // Get drug by list ids
        $items = [];
        if (isset($ids)) {
            $dataSQL = DB::select("
        				select d.id,
							 d.name,
							 d.short_name,
							 d.substances,
							 d.concentration,
							 d.package_form,
							 d.company,
							 d.drug_code,
							 d.country,
							 d.active::varchar          as active,
							 d.image,
							 d.description,
							 d.registry_number,
							 d.warning_days,
							 d.warning_quantity_max,
							 d.warning_unit,
							 d.drug_store_id,
							 b.main_cost,
							 b.pre_cost,
							 b.current_cost,
							 b.unit_id,
							 u.name                     AS unit_name,
							 (SELECT json_agg(json_build_object(
											 'quantity', w.quantity,
											 'is_basic', w.is_basic,
											 'exchange', w.exchange,
											 'current_cost', w.current_cost,
											 'main_cost', w.main_cost,
											 'warning_quantity', w.warning_quantity,
											 'manufacturing_date', w.manufacturing_date,
											 'mfg_date', w.mfg_date,
											 'unit_id', unit.id,
											 'unit_name', unit.name))::jsonb
								from warehouse w
												 inner join unit
																		on w.unit_id = unit.id
								where w.drug_id = d.id
									and w.is_check)         as other_units,
							 (SELECT json_agg(json_build_object(
											 'number', w.number,
											 'expiry_date', w.expiry_date,
											 'mfg_date', w.mfg_date,
											 'manufacturing_date', w.manufacturing_date,
											 'quantity', w.quantity))::jsonb
								FROM warehouse w
								where w.drug_id = d.id
									and w.is_basic = 'yes'
									and w.is_check = false) as numbers
				from (
					select * from drug where drug.id in ($ids)
				) d INNER JOIN warehouse b ON b.drug_id = d.id
						AND b.is_check = TRUE
						AND b.is_basic = 'yes'
								 INNER JOIN unit u ON u.id = b.unit_id
								ORDER  BY d.id;
            ");
            $items = $dataSQL;
        }
        // Build response with item and pagination
        $data = new LengthAwarePaginator($items, $data['total'], $perPage, $page);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }


    public function addDrug(Request $request)
    {
        LogEx::methodName($this->className, 'addDrug');

        $data = $this->drug->addDrug($request);
        if ($data <= 0) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * Upload file zip chứa ảnh
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadZipDrug(Request $request)
    {
        LogEx::methodName($this->className, 'uploadZipDrug');
        $imageBasePath = env('UPLOAD_ZIP_FILE', 'D:/zip/');
        LogEx::info($imageBasePath);
        LogEx::info(exec("whoami"));

        $file = $request->file("zipFile");
        $userInfo = $request->userInfo;

        $zip = new \ZipArchive();
        $zipStatus = $zip->open($file->path());
        $result = [];

        if ($zipStatus == true) {
            //Giải nén ra thư mục được chỉ định
            $zip->extractTo($imageBasePath . $userInfo->username);

            //Thực hiện upload lên kênh cdn của sphacy
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileName = $zip->getNameIndex($i);

                //Kiểm tra file có thỏa mãn điều kiện là png or jpg ==> Không thỏa mãn bỏ qua
                $type = substr($fileName, strrpos($fileName, "."));
                $type = strtolower($type);

                $fileNameId = substr($fileName, 0, strlen($fileName) - strlen($type));
                $fileNameId = substr($fileNameId, strrpos($fileNameId, "/") === FALSE ? 0 :
                    (strrpos($fileNameId, "/") + 1));

                if (in_array($type, ['.jpg', '.jpeg', '.gif', '.png'])) {
                    $newImageName = uniqid() . $type;
                    $target_dir = 'upload/images/' . $newImageName;
                    $data = file_get_contents($imageBasePath . $userInfo->username . '/' . $fileName);
                    file_put_contents($target_dir, $data);

                    //Hình thành kết quả
                    $result[$fileNameId] = $target_dir;
                }
            }

//            Thực hiện xóa bỏ ảnh cũ
            self::deleteDirectory($imageBasePath . $userInfo->username);
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
    }

    private function deleteDirectory($dirname)
    {
        if (is_dir($dirname))
            $dir_handle = opendir($dirname);
        if (isset($dir_handle))
            return false;

        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname . "/" . $file))
                    unlink($dirname . "/" . $file);
                else
                    self::deleteDirectory($dirname . '/' . $file);
            }
        }
        closedir($dir_handle);
        rmdir($dirname);
        return true;
    }

    public function editDrug($id, Request $request)
    {
        LogEx::methodName($this->className, 'addDrug');

        $data = $this->drug->editDrug($id, $request);
        if ($data <= 0) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function updateUnit($id, Request $request)
    {
        LogEx::methodName($this->className, 'updateUnit');

        $data = $this->drug->updateUnit($id, $request);
        if ($data <= 0) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function updateWarning($id, Request $request)
    {
        LogEx::methodName($this->className, 'updateWarning');

        $data = $this->drug->updateWarning($id, $request);
        if ($data <= 0) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function drugsInGroup($id, Request $request)
    {
        LogEx::methodName($this->className, 'drugsInGroup');

        $data = $this->drug->drugsInGroup($id, $request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from drugsInGroup
    */
    public function drugsInGroupV3($id, Request $request)
    {
        LogEx::methodName($this->className, 'drugsInGroup');

        $data = $this->drug->drugsInGroupV3($id, $request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function drugsInCategory($id, Request $request)
    {
        LogEx::methodName($this->className, 'drugsInCategory');

        $data = $this->drug->drugsInCategory($id, $request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function drugList(Request $request)
    {
        LogEx::methodName($this->className, 'drugList');

        $data = $this->drug->drugList($request);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function updateStatus($id, $status, Request $request)
    {
        LogEx::methodName($this->className, 'updateStatus');

        $userInfo = $request->userInfo;
        if (!in_array($status, ['yes', 'no'])) {
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
        }

        //Kiểm tra đủ điều kiện xóa hẳn không (Có => Xóa hẳn)
        $data = Utils::executeRawQuery("select v3.f_drug_check_delete(?) as result", [$id]);

        //Đủ điều kiện xóa
        if ($data[0]->result) {
            //Xóa thật
            $data = Utils::executeRawQuery("select v3.f_drug_delete(?) as result", [$id]);
        } else {
            //Thực hiện ngừng kinh doanh
            $data = $this->drug->updateStatus($id, $status, $userInfo);
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function drugScan($drugCode, $number, Request $request)
    {
        LogEx::methodName($this->className, 'drugScan');

        $user = $request->user();
        $data = Utils::executeRawQuery("
    select  row_number() over(order by w.exchange) as row_number,
            d.id,
            d.name,
            d.short_name,
            d.package_form,
            d.company,
            d.drug_code,
            d.country,
            d.image,
            d.registry_number,
            d.vat,
            w.quantity,
            w.number,
            w.main_cost,
            w.current_cost,
            w.expiry_date,
            w.unit_id,
            w.exchange,
            u.name  as unit_name,
            1       as scan
    from    warehouse w
        inner join drug d
            on  d.drug_store_id = w.drug_store_id
            and d.id            = w.drug_id
            and d.drug_code     = ?
        inner join unit u
            on  u.id            = w.unit_id
    where w.drug_store_id = ?
    and   w.number        = ?
    and   w.is_basic      = 'yes'
    and   w.is_check      = false
    order by w.exchange
", [$drugCode, $user->drug_store_id, $number]);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function drugScanNew(Request $request)
    {
        $requestInput = $request->input();
        return $this->drugScan($requestInput['drug_code'], $requestInput['number'], $request);
    }

    public function drugScanBarcode(Request $request)
    {
        LogEx::methodName($this->className, 'drugScan');

        $user = $request->user();
        $requestInput = $request->input();
        $data = Utils::executeRawQuery("select * from sphacy_v1_new.f_drug_scan(?, ?)", [$user->drug_store_id, $requestInput['scan_code']]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getDrugDetail($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDrugDetail');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery("select v3.f_drug_detail(?) as result", [Utils::getParams($requestInput, array('id' => $id))]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
    }

    public function search(Request $request)
    {
        LogEx::methodName($this->className, 'search');

        $requestInput = $request->input();
        $page = $requestInput['page'] ?? 1;
        $perPage = $requestInput['per_page'] ?? 30;
        $drugStoreId = $requestInput['userInfo']['drug_store_id'] ?? 0;
        $search = $requestInput['search'] ?? '';

        // Get list drug from elasticsearch
        $data = $this->drug->getDrugListES(Utils::getParamsString($requestInput, array("page" => $page, "per_page" => $perPage, "drug_store_id" => $drugStoreId, "search_text" => $search), false));
        $ids = null;
        if ($data['total'] > 0) {
            // Get list id from response elasticsearch
            $ids = '{' . collect(Utils::getIds($data['items']))->implode(',') . '}';
        }

        // Get drug by list ids
        $items = [];
        if (isset($ids)) {
            $items = Utils::executeRawQuery("select * from v3.f_drug_by_ids(?)", [Utils::getParams($requestInput, array("drug_ids" => $ids), false)]);
        }

        // Build response with item and pagination
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $items);
    }

    public function importDrug(Request $request)
    {
        LogEx::methodName($this->className, 'importDrug');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery('select * from v3.f_drug_import(?) as result', [Utils::getParams($requestInput)]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
    }

    /**
     * api v3
     * from importDrug
    */
    public function importDrugV3(Request $request)
    {
        LogEx::methodName($this->className, 'importDrugV3');
        $user = $request->userInfo;
        $data = $request->input();
        $count = 0;

        if (count($data['datas']) === 0) return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, 'Không có hóa đơn nào cần import');

        $data = $data['datas'];

        DB::beginTransaction();
        try {
            $collections = collect($data)->chunk(50);
            foreach ($collections as $collection) {
                foreach ($collection as $value) {

                    if (!(Unit::where('name', $value["unit_name"])->first()))
                        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR,
                            "Đơn vị tính ". $value["unit_name"]." không tồn tại");

                    $dataDrug = array(
                        "is_master_data" => Utils::coalesce($value, 'is_master_data', false),
                        "is_monopoly" => Utils::coalesce($value, 'is_monopoly', false),
                        "name" => Utils::coalesce($value, 'name', null),
                        "short_name" => Utils::coalesce($value, 'short_name', null),
                        "drug_category_id" => Utils::coalesce($value, 'drug_category_id', null),
                        "drug_group_id" => Utils::coalesce($value, 'drug_group_id', null),
                        "unit_id" => Unit::where('name', $value["unit_name"])->first()->id,
                        "current_cost" => Utils::coalesce($value, 'current_cost', null),
                        "main_cost" => Utils::coalesce($value, 'main_cost', null),
                        "base_ratio" => Utils::coalesce($value, 'base_ratio', null),
                        "drug_code" => Utils::coalesce($value, 'drug_code', null),
                        "barcode" => Utils::coalesce($value, 'barcode', null),
                        "registry_number" => Utils::coalesce($value, 'registry_number', null),
                        "country" => Utils::coalesce($value, 'country', null),
                        "company" => Utils::coalesce($value, 'company', null),
                        "package_form" => Utils::coalesce($value, 'package_form', null),
                        "concentration" => Utils::coalesce($value, 'concentration', null),
                        "substances" => Utils::coalesce($value, 'substances', null),
                        "quantity" => Utils::coalesce($value, 'quantity', null),
                        "warning_unit" => Utils::coalesce($value, 'warning_unit', null),
                        "warning_quantity_min" => Utils::coalesce($value, 'warning_quantity_min', null),
                        "warning_quantity_max" => Utils::coalesce($value, 'warning_quantity_max', null),
                        "warning_days" => Utils::coalesce($value, 'warning_days', null),
                        "is_drug" => Utils::coalesce($value, 'is_drug', false),
                    );

                    $image = "";

                    if (!empty($request->input('images'))) {
                        if (empty($value['image']))
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, "ID ảnh cần nhập");
                        if (empty($request->input('images')[$value['image']]))
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, "Không có ID ảnh phù hợp");
                        $image = $request->input('images')[$value['image']];
                    }

                    $drugs = $this->drugService->createOrUpdate($dataDrug, $image, $user->drug_store_id);

                    if (!$drugs) $count += 1;
                }
            }
            DB::commit();
            $listInvoices = [
                'count' => count($data),
                'insert_false' => $count
            ];

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $listInvoices);
        } catch (\Exception $e) {
            DB::rollBack();
            LogEx::try_catch($this->className, $e);

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    /**
     * Kiểm tra theo kịch bản test tại https://confluence.mhsolution.vn/pages/viewpage.action?pageId=27526561
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkImportDrug(Request $request)
    {
        LogEx::methodName($this->className, 'checkImportDrug');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery('select * from v3.f_drug_check_import(?)', [Utils::getParams($requestInput)]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
    }

    /**
     * api v3
     * from checkImportDrug
    */
    public function checkImportDrugV3(Request $request)
    {
        LogEx::methodName($this->className, 'checkImportDrugV3');

        $file = $request->file;
        $drug_store_id = $request->userInfo->drug_store_id;
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->setShouldFormatDates(true);
        $reader->open($file);
        $isDrung = $request->input('is_drug');

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getIndex() === 0) {
                foreach ($sheet->getRowIterator() as $key => $row) {
                    if ($key > 4) {
                        $data = [
                            //"active" => "no",
                            "barcode" => $row->toArray()[1],
                            "category_name" => $row->toArray()[3],
                            "company" => $row->toArray()[8],
                            "concentration" => $row->toArray()[6],
                            "country" => $row->toArray()[6],
                            "current_cost" => $row->toArray()[12],//*
                            "drug_code" => $row->toArray()[0],
                            "drug_store_id" => $drug_store_id,
                            "group_name" => $row->toArray()[4],
                            //"id" => "",
                            "image" => $row->toArray()[14],
                            "is_drug" => $isDrung == 1 ? true : false,
                            "name" => $row->toArray()[2],//*
                            "other_units" => [
                                ["name" => $row->toArray()[15], "exchange" => $row->toArray()[16], "out_price" => $row->toArray()[17]],
                                ["name" => $row->toArray()[18], "exchange" => $row->toArray()[19], "out_price" => $row->toArray()[20]],
                                ["name" => $row->toArray()[21], "exchange" => $row->toArray()[22], "out_price" => $row->toArray()[23]],
                                ["name" => $row->toArray()[24], "exchange" => $row->toArray()[25], "out_price" => $row->toArray()[26]],
                                ["name" => $row->toArray()[27], "exchange" => $row->toArray()[28], "out_price" => $row->toArray()[29]],
                            ],
                            "package_form" => $row->toArray()[7],
                            "registry_number" => $row->toArray()[10],
                            "substances" => $row->toArray()[5],
                            "undefined" => $row->toArray()[27],
                            "unit_name" => $row->toArray()[11],//*
                            "updated" => [
                                "new" => true,
                                "other_units" => true
                            ]
                        ];

                        if ($data["name"] && $data["current_cost"] && $data["unit_name"])
                            $datas[] = $data;

                        if (is_null($data["name"]))
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập tên thuốc');

                        if (is_null($data["current_cost"]))
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập giá bán');

                        if (is_null($data["unit_name"]))
                            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, 'Cần nhập đơn vị tính');

                    }
                }
                break;
            }
        }

        $reader->close();

//        if (!(Unit::whereIn('name', $listNames)->get()))
//            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR,
//                'Đơn vị tính không tồn tại');
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $datas);
    }

    public function drugStock(Request $request)
    {
        LogEx::methodName($this->className, 'stock');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery('select * from v3.f_drug_stock(?)', [Utils::getParams($requestInput)], $request->url(), $requestInput);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, UniversalDataMapping::hydrate($data));
    }

    public function drugClone(Request $request)
    {
        LogEx::methodName($this->className, 'drugClone');

        $requestInput = $request->input();
        $drugStoreId = $requestInput['drug_store_id'] ?? 0;
        $destStoreId = $requestInput['dest_store_id'] ?? 0;
        $drugIdList = $requestInput['drug_id_list'] ?? null;
        $data = Utils::executeRawQuery('select * from v3.f_drug_clone(?)', [Utils::getParams($requestInput, array('drug_store_id' => $drugStoreId, 'drug_id_list' => $drugIdList, 'dest_store_id' => $destStoreId), false)]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}

