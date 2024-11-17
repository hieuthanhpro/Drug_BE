<?php

namespace App\Services;

use App\LibExtension\LogEx;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\DrugCategory\DrugCategoryRepositoryInterface;
use App\Repositories\DrugGroup\DrugGroupRepositoryInterface;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\TDrugUnit\TDrugUnitRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class DrugStoreService
 * @package App\Services
 */
class DrugStoreService
{
    protected $className = "DrugStoreService";
    private $drugStoreRepository;
    private $drugCategoryRepository;
    private $drugGroupRepository;
    private $drugRepository;
    private $warehouseRepository;
    private $tDrugUnitRepository;
    private $invoiceRepository;

    public function __construct(DrugStoreRepositoryInterface $drugStoreRepository, DrugCategoryRepositoryInterface $drugCategoryRepository,
                                DrugGroupRepositoryInterface $drugGroupRepository, DrugRepositoryInterface $drugRepository,
                                WarehouseRepositoryInterface $warehouseRepository, TDrugUnitRepositoryInterface $tDrugUnitRepository,
                                InvoiceRepositoryInterface   $invoiceRepository)
    {
        LogEx::constructName($this->className, '__construct');

        $this->drugStoreRepository = $drugStoreRepository;
        $this->drugCategoryRepository = $drugCategoryRepository;
        $this->drugGroupRepository = $drugGroupRepository;
        $this->drugRepository = $drugRepository;
        $this->warehouseRepository = $warehouseRepository;
        $this->tDrugUnitRepository = $tDrugUnitRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * Lọc nhà thuốc
     * @param $requestInput
     * @return mixed
     */
    public function filterDrugStore($requestInput)
    {
        return $this->drugStoreRepository->filterDrugStore($requestInput);
    }

    /**
     * Tạo mới hoặc cập nhật nhà thuốc
     * @param $requestInput
     * @return mixed|null
     */
    public function createOrUpdate($requestInput)
    {
        DB::beginTransaction();
        try {
            if (isset($requestInput['id'])) {
                $this->drugStoreRepository->updateOneById($requestInput['id'], $requestInput);
                $drugStore = $this->drugStoreRepository->findOneById($requestInput['id']);
            } else {
                $requestInput['status'] = 1;
                $drugStore = $this->drugStoreRepository->create($requestInput);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return null;
        }
        return $drugStore;
    }

    /**
     * Xóa một nhà thuốc
     * @param $id
     * @return bool
     */
    public function deleteDrugStore($id)
    {
        DB::beginTransaction();
        try {
            $this->drugStoreRepository->deleteDrugStoreOrData($id, true);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    /**
     * Copy thuốc từ một nhà thuốc
     * @param $requestInput
     * @return bool
     */
    public function drugStoreCopyDrug($requestInput)
    {
        $srcStoreId = $requestInput['src_store_id'];
        $destStoreId = $requestInput['dest_store_id'];

        DB::beginTransaction();
        try {
            $this->drugStoreRepository->deleteDrugStoreOrData($destStoreId);
            $this->drugCategoryRepository->copyDrugCategoryByStoreId($srcStoreId, $destStoreId);
            $this->drugGroupRepository->copyDrugGroupByStoreId($srcStoreId, $destStoreId);
            $this->drugRepository->copyDrugByStoreId($srcStoreId, $destStoreId);
            $this->warehouseRepository->copyWarehouseByStoreId($srcStoreId, $destStoreId);
            $this->tDrugUnitRepository->copyTDrugUnitByStoreId($destStoreId);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    /**
     * Check số thuốc, hóa đơn, tồn kho của nhà thuốc
     * @param $drugStore
     * @return array
     */
    public function checkDrugStore($drugStore)
    {
        $drugCount = $this->drugRepository->countDrugByStoreId($drugStore->id);
        $invoiceCount = $this->invoiceRepository->countInvoiceByStoreId($drugStore->id);
        $warehouse = $this->warehouseRepository->countWarehouseByStoreId($drugStore->id);
        $remove = ['username', 'password', 'token', 'usernamedqg', 'passworddqg'];
        $drugStore = array_diff_key($drugStore->toArray(), array_flip($remove));
        $drugStore['drug_count'] = $drugCount;
        $drugStore['invoice_count'] = $invoiceCount;
        $drugStore['warehouse_count'] = $warehouse;
        return $drugStore;
    }

    /**
     * Lấy danh sách nhà thuốc theo source: GPP hoặc GDP và ở trạng thái cho chọn
     * @param null $source
     * @return \Illuminate\Support\Collection
     */
    public function getDrugStoreBySource($source = null)
    {
        if (isset($source)) {
            return DB::table('drugstores')
                ->select('id', 'name', 'type')
                ->where('show_select', '=', '1')
                ->where('type', '=', $source)
                ->get();
        }
        return DB::table('drugstores')
            ->select('id', 'name', 'type')
            ->where('show_select', '=', '1')
            ->get();
    }
}
