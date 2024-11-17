<?php

namespace App\Repositories\InvoiceWarehouse;

use app\libextension\logex;
use App\LibExtension\Utils;
use App\Models\InvoiceWarehouse;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Support\Facades\DB;

class InvoiceWarehouseEloquentRepository extends AbstractBaseRepository implements InvoiceWarehouseRepositoryInterface
{
    protected $className = "InvoiceWarehouseEloquentRepository";

    public function __construct(InvoiceWarehouse $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function filter($invoiceWarehouseInput, $drugStoreId, $limit = null)
    {
        LogEx::methodName($this->className, 'filter');
        $limit = $limit ?? $invoiceWarehouseInput["limit"] ?? 10;
        $searchText = $invoiceWarehouseInput["query"] ?? null;
        $type = $invoiceWarehouseInput["type"] ?? null;
        $fromDate = $invoiceWarehouseInput["from_date"] ?? null;
        $toDate = $invoiceWarehouseInput["to_date"] ?? null;
        $status = $invoiceWarehouseInput["status"] ?? null;
        $createdBy = $invoiceWarehouseInput["created_by"] ?? null;
        $queryDB = '1 = 1';
        $queryDB = $queryDB . " AND invoice_warehouse.type = '" . $type . "'";

        if (isset($searchText)) {
            $queryDB = $queryDB . " AND (invoice_warehouse.code ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(drug.name) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(drug.drug_code) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(invoice_detail.number) ILIKE '%" . Utils::unaccent($searchText) . "%')";
        }

        if (isset($fromDate)) {
            $queryDB = $queryDB . " AND invoice_warehouse.created_at >= '" . $fromDate . " 00:00:00.000000'";
        }

        if (isset($toDate)) {
            $queryDB = $queryDB . " AND invoice_warehouse.created_at <= '" . $toDate . " 23:59:59.999999'";
        }

        if (isset($status)) {
            $queryDB = $queryDB . " AND invoice_warehouse.status = '" . $status . "'";
        }

        if (isset($createdBy)) {
            $queryDB = $queryDB . " AND invoice_warehouse.created_by = $createdBy";
        }

        return DB::table("invoice_warehouse")
                ->select(
                    'invoice_warehouse.id',
                    'invoice_warehouse.code',
                    'invoice_warehouse.created_at',
                    'invoice_warehouse.ref_code',
                    'invoice_warehouse.date',
                    DB::raw('users.name as created_by'),
                    'invoice_warehouse.reason',
                    DB::raw('count(invoice_detail.id) as quantity'),
                    'invoice_warehouse.status',
                    'invoice_warehouse.type'
                )
            ->join("invoice_detail", "invoice_detail.warehouse_invoice_id", "invoice_warehouse.id")
            ->join("drug", "drug.id", "invoice_detail.drug_id")
            ->join("users", "users.id", "invoice_warehouse.created_by")
            ->where('invoice_warehouse.drug_store_id', $drugStoreId)
            ->whereRaw($queryDB)
            ->groupBy(["invoice_warehouse.id", "users.name"])
            ->orderByDesc("invoice_warehouse.id")
            ->paginate($limit);
    }

    public function getByIdAndDrugStoreId($id, $drugStoreId)
    {
        return DB::table("invoice_warehouse")
            ->select('*')
            ->where("drug_store_id", "=", $drugStoreId)->where("id", "=", $id)->first();
    }
}
