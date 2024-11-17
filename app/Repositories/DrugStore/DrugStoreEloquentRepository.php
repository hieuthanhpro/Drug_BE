<?php

namespace App\Repositories\DrugStore;

use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\DrugStore;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DrugStoreEloquentRepository extends AbstractBaseRepository implements DrugStoreRepositoryInterface
{
    protected $className = "DrugStoreEloquentRepository";

    public function __construct(DrugStore $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function filterDrugStore($requestInput)
    {
        $select = "SELECT ds.* from drugstores ds";

        $selectCount = "SELECT count(ds.*) as total FROM drugstores ds";
        $where = " WHERE 1 = 1";
        if (isset($requestInput["status"])) {
            $where = $where . " and ds.status ~* '" . $requestInput["status"] . "'";
        }
        if (isset($requestInput["type"])) {
            $where = $where . " and ds.type = " . $requestInput["type"];
        }
        if (isset($requestInput["query"])) {
            $keySearch = trim($requestInput['query']);
            $where = $where . " AND (ds.name ~* '" . $keySearch
                . "' or ds.address  ~* '" . $keySearch
                . "' or ds.reg_number  ~* '" . $keySearch
                . "' or ds.pharmacist  ~* '" . $keySearch
                . "' or ds.phone  ~* '" . $keySearch
                . "' or ds.base_code  ~* '" . $keySearch
                ."')";
        }
        $order = " order by ds.id desc";
        if (!empty($requestInput['page']) && (!empty($requestInput['per_page']) || !empty($requestInput['perPage']))) {
            $page = $requestInput['page'] ?? 1;
            $perPage = $requestInput['per_page'] ?? 10;
            $offset = ($page - 1) * $perPage;
            $limit = " limit " . $perPage . " offset " . $offset;
            $data = DB::select($select . $where . $order . $limit);
            $dataCount = DB::select($selectCount . $where);
            return new LengthAwarePaginator($data, $dataCount[0]->total, $perPage, $page);
        } else {
            return DB::select($select . $where . $order);
        }
    }

    public function getDrugStoresByStatusAndType($status, $type)
    {
        LogEx::methodName($this->className, 'getDrugStoresByStatus');
        return DrugStore::select('*')->where('status', '=', $status)->where('type', '=', $type)->get();
    }

    public function getDrugStoresByStatusAndIds($status, $ids)
    {
        LogEx::methodName($this->className, 'getDrugStoresByStatusAndIds');
        return DrugStore::select('*')->where('status', '=', $status)->whereIn('id', $ids)->get();
    }

    public function deleteDrugStoreOrData($id, $isDeleteDrugStore = false)
    {
        DB::table('warehouse')->where('drug_store_id', $id)->delete();
        DB::table('drug')->where('drug_store_id', $id)->delete();
        DB::table('drug_group')->where('drug_store_id', $id)->delete();
        DB::table('drug_category')->where('drug_store_id', $id)->delete();
        DB::table('cashbook')->where('drug_store_id', $id)->delete();
        DB::table('cash_type')->where('drug_store_id', $id)->delete();
        DB::table('price_rule')->whereExists(function ($query) use ($id) {
            $query->select(DB::raw(1))
                ->from('promotion')
                ->whereRaw('promotion.drug_store_id = ' . $id . ' and promotion.id = price_rule.promotion_id');
        })->delete();
        DB::table('promotion')->where('drug_store_id', $id)->delete();
        DB::table('promotion_logs')->where('drug_store_id', $id)->delete();
        DB::table('t_drug_unit')->where('drug_store_id', $id)->delete();
        DB::table('t_stock')->where('drug_store_id', $id)->delete();
        DB::table('t_stockym')->where('drug_store_id', $id)->delete();
        DB::table('invoice_detail')->whereExists(function ($query) use ($id) {
            $query->select(DB::raw(1))
                ->from('invoice')
                ->whereRaw('drug_store_id = ' . $id . ' and (is_order is null or is_order = false) and invoice.id = invoice_detail.invoice_id');
        })->delete();
        DB::table('prescription')->whereExists(function ($query) use ($id) {
            $query->select(DB::raw(1))
                ->from('invoice')
                ->whereRaw('invoice.drug_store_id = ' . $id . ' and invoice.id = prescription.invoice_id');
        })->delete();
        DB::table('invoice')->where('drug_store_id', $id)->where(function ($query) {
            $query->whereNull('is_order')
                ->orWhere('is_order', '=', false);
        })->delete();
        DB::table('invoice_warehouse')->where('drug_store_id', $id)->delete();
        DB::table('customer')->where('drug_store_id', $id)->delete();
        if ($isDeleteDrugStore) {
            DB::table('users')->where('drug_store_id', $id)->delete();
            DB::table('drugstores')->where('id', $id)->delete();
        }
    }
}
