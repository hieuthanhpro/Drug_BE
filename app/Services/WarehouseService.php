<?php

namespace App\Services;

use App\Http\Requests\InvoiceWarehouse\InvoiceWarehouseFilterRequest;
use App\LibExtension\Utils;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\InvoiceDetail\InvoiceDetailRepositoryInterface;
use App\Repositories\InvoiceWarehouse\InvoiceWarehouseRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use Carbon\Carbon;
use App\LibExtension\LogEx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


/**
 * Class InvoiceWarehouseService
 * @package App\Services
 */
class WarehouseService
{
    protected $className = "WarehouseService";

    protected $warehouse;
    protected $invoiceDetail;
    protected $invoice;


    public function __construct(WarehouseRepositoryInterface $invoiceWarehouse,
                                InvoiceDetailRepositoryInterface    $invoiceDetail,
                                InvoiceRepositoryInterface          $invoice)
    {
        LogEx::constructName($this->className, '__construct');
        $this->warehouse = $invoiceWarehouse;
        $this->invoiceDetail = $invoiceDetail;
        $this->invoice = $invoice;
    }

    public function createInvoiceWarehouse($model)
    {
        try {
            $data = $this->invoiceWarehouse->create($model);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return null;
        }
        return $data;
    }

    public function createInvoiceWarehouseManual($type, $requestInput, $user)
    {
        $requestInput["drug_store_id"] = $user["drug_store_id"];
        $requestInput["created_by"] = $user["id"];
        $requestInput["type"] = $type;
        $requestInput["status"] = $requestInput["status"] ?? "pending";
        if ($type === "export") {
            $requestInput["code"] = 'XK' . Utils::getSequenceDB('XK');
        } else {
            $requestInput["code"] = 'NK' . Utils::getSequenceDB('NK');
        }
        LogEx::info($requestInput["date"]);
        DB::beginTransaction();
        try {
            $dataInvoiceWarehouse = $this->invoiceWarehouse->create($requestInput);
            if ($dataInvoiceWarehouse) {
                $lineItems = [];
                foreach ($requestInput["line_items"] as $item) {
                    $item["warehouse_invoice_id"] = $dataInvoiceWarehouse->id;
                    array_push($lineItems, $item);
                }
                $this->invoiceDetail->insertBatchWithChunk($lineItems, sizeof($lineItems));
                $detailInvoice = $this->invoiceDetail->findManyBy("warehouse_invoice_id", $dataInvoiceWarehouse->id);

                // Tạo hóa đơn
                if ($requestInput["status"] !== "temp" && in_array($requestInput["invoice_type"], array('IV1', 'IV2', 'IV3', 'IV4', 'IV5', 'IV7', 'IV8'))) {
                    $invoice = $this->createInvoice($requestInput, $user["drug_store_id"], $user["id"]);
                    $this->invoiceWarehouse->updateOneById($dataInvoiceWarehouse->id, ["reason" => $this->getReason($invoice), "invoice_id" => $invoice->id]);
                    if ($invoice) {
                        $this->invoiceDetail->updateManyBy("warehouse_invoice_id", $dataInvoiceWarehouse->id, ["invoice_id" => $invoice->id]);
                    }
                }
                DB::commit();
                return array_merge($dataInvoiceWarehouse->toArray(), ["line_items" => $detailInvoice]);
            } else {
                DB::rollback();
            }
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return null;
        }
        return null;
    }

    public function updateInvoiceWarehouseManual($type, $requestInput, $user)
    {
        $requestInput["drug_store_id"] = $user["drug_store_id"];
        $requestInput["created_by"] = $user["id"];
        $requestInput["type"] = $type;
        $requestInput["status"] = $requestInput["status"] ?? "pending";
        if ($type === "export") {
            $requestInput["code"] = 'XK' . Utils::getSequenceDB('XK');
        } else {
            $requestInput["code"] = 'NK' . Utils::getSequenceDB('NK');
        }
        DB::beginTransaction();
        try {
            $this->invoiceWarehouse->updateOneById($requestInput["id"], $requestInput);
            $dataInvoiceWarehouse = $this->invoiceWarehouse->findOneById($requestInput["id"]);
            if ($dataInvoiceWarehouse) {
                $this->invoiceDetail->deleteManyBy("warehouse_invoice_id", $requestInput["id"]);
                $lineItems = [];
                foreach ($requestInput["line_items"] as $item) {
                    $item["warehouse_invoice_id"] = $requestInput["id"];
                    array_push($lineItems, $item);
                }
                $this->invoiceDetail->insertBatchWithChunk($lineItems, sizeof($lineItems));
                $detailInvoice = $this->invoiceDetail->findManyBy("warehouse_invoice_id", $requestInput["id"]);

                // Tạo hóa đơn
                if ($requestInput["status"] !== "temp" && in_array($requestInput["invoice_type"], array('IV1', 'IV2', 'IV3', 'IV4', 'IV5', 'IV7', 'IV8'))) {
                    if(isset($dataInvoiceWarehouse->invoice_id)){
                        $this->updateInvoice($requestInput, $dataInvoiceWarehouse->invoice_id);
                        $this->invoiceDetail->updateManyBy("warehouse_invoice_id", $requestInput["id"], ["invoice_id" => $dataInvoiceWarehouse->invoice_id]);
                    }else{
                        $invoice = $this->createInvoice($requestInput, $user["drug_store_id"], $user["id"]);
                        $this->invoiceWarehouse->updateOneById($requestInput["id"], ["reason" => $this->getReason($invoice), "invoice_id" => $invoice->id]);
                        if ($invoice) {
                            $this->invoiceDetail->updateManyBy("warehouse_invoice_id", $requestInput["id"], ["invoice_id" => $invoice->id]);
                        }
                    }
                }
                DB::commit();
                return array_merge($dataInvoiceWarehouse->toArray(), ["line_items" => $detailInvoice]);
            } else {
                DB::rollback();
            }
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return null;
        }
        return null;
    }

    public function createModelByInvoice($invoiceData, $userInfo, $isOrder = false)
    {
        $code = null;
        if (in_array($invoiceData->invoice_type, array('IV1', 'IV4', 'IV8'))) {
            $code = 'XK' . Utils::getSequenceDB('XK');
        } else if (in_array($invoiceData->invoice_type, array('IV2', 'IV3', 'IV7'))) {
            $code = 'NK' . Utils::getSequenceDB('NK');
        }
        $reason = $this->getReason($invoiceData);
        if ($code && $reason) {
            $model = array(
                'drug_store_id' => $userInfo->drug_store_id,
                'code' => $code,
                'type' => in_array($invoiceData->invoice_type, array('IV1', 'IV4', 'IV8')) ? 'export' : 'import',
                'reason' => $reason,
                'created_by' => $userInfo->id,
                'status' => $invoiceData->status === "done" ? "done" : "pending",
                'invoice_type' => $invoiceData->invoice_type,
                'invoice_id' => $invoiceData->id,
                'customer_id' => in_array($invoiceData->invoice_type, array('IV2', 'IV4')) ? null : $invoiceData->customer_id,
                'supplier_id' => in_array($invoiceData->invoice_type, array('IV2', 'IV4')) ? $invoiceData->customer_id : null
            );
            if($isOrder){
                $model['date'] = Carbon::now();
            }
            return $model;
        }
        return null;
    }

    public function getReason($invoiceData)
    {
        $reason = null;
        switch ($invoiceData->invoice_type) {
            case 'IV1':
                $reason = "Xuất kho bán hàng theo hoá đơn " . $invoiceData->invoice_code;
                break;
            case 'IV2':
            case 'IV7':
                $reason = "Nhập kho theo hoá đơn " . $invoiceData->invoice_code;
                break;
            case 'IV3':
                $reason = "Nhập kho Khách trả hàng theo hoá đơn " . $invoiceData->invoice_code;
                break;
            case 'IV4':
                $reason = "Xuất kho Trả hàng NCC theo hoá " . $invoiceData->invoice_code;
                break;
            case 'IV8':
                $reason = "Xuất huỷ theo hoá đơn " . $invoiceData->invoice_code;
                break;
        }
        return $reason;
    }

    public function validation($requestInput)
    {
        $validation = Validator::make($requestInput, [
            'line_items' => 'required',
            'invoice_type' => 'required',
            'date' => 'required'
        ], [
            'line_items.required' => 'Bạn chưa thêm sản phẩm nào',
            'invoice_type.required' => 'Bạn chưa chọn loại hóa đơn',
            'date.required' => 'Bạn chưa chọn ngày'
        ]);
        if (isset($requestInput['line_items']) && sizeof($requestInput['line_items']) > 0) {
            return Validator::make($requestInput['line_items'], [
                '*.drug_id' => 'required',
                '*.unit_id' => 'required',
                '*.number' => 'required',
                '*.expiry_date' => 'required',
                '*.exchange' => 'required',
                '*.quantity' => 'required'
            ], [
                '*.drug_id.required' => 'Thiếu id của sản phẩm',
                '*.unit_id.required' => 'Thiếu đơn vị của sản phẩm',
                '*.number.required' => 'Thiếu số lô của sản phẩm',
                '*.expiry_date.required' => 'Thiếu ngày hết hạn của sản phẩm',
                '*.quantity.required' => 'Thiếu số lượng của sản phẩm',
                '*.exchange.required' => 'Thiếu đơn vị quy đổi của sản phẩm'
            ]);
        } else {
            $validation->after(function ($validator) use ($requestInput) {
                $validator->errors()->add('line_items', 'Bạn chưa chọn sản phẩm nào');
            });
        }
        return $validation;
    }

    private function createInvoice($requestInput, $drug_store_id, $user_id)
    {
        $invoiceCode = "";
        $reason = "";
        switch ($requestInput["invoice_type"]) {
            case "IV2":
                $invoiceCode = "PN" . Utils::getSequenceDB("PN");
                $reason = "Nhập hàng từ NCC";
                break;
            case "IV7":
                $invoiceCode = "PN" . Utils::getSequenceDB("PN");
                $reason = "Nhập hàng tồn";
                break;
            case "IV3":
                $invoiceCode = "HDT" . Utils::getSequenceDB("HDT");
                $reason = "Khách trả lại thuốc";
                break;
            case "IV4":
                $invoiceCode = "PTH" . Utils::getSequenceDB("PTH");
                $reason = "Trả hàng cho NCC";
                break;
            case "IV1":
                $invoiceCode = "HD" . Utils::getSequenceDB("HD");
                $reason = "Bán hàng cho khách";
                break;
            case "IV5":
                $invoiceCode = "HD" . Utils::getSequenceDB("HD");
                $reason = "Khác";
                break;
            case "IV8":
                $invoiceCode = "HD" . Utils::getSequenceDB("HD");
                $reason = "Xuất hủy";
                break;
        }
        return $this->invoice->create(array(
            "drug_store_id" => $drug_store_id,
            "invoice_code" => $invoiceCode,
            "invoice_type" => $requestInput["invoice_type"],
            "warehouse_action_id" => $reason,
            "created_by" => $user_id,
            "status" => "pending",
            "date" => $requestInput["date"],
            "customer_id" => $requestInput["supplier_id"] ?? $requestInput["customer_id"] ?? null,
            "supplier_invoice_code" => $requestInput["ref_code"]
        ));
    }

    private function updateInvoice($requestInput, $id)
    {
        $model = array();
        if(isset($requestInput["supplier_id"]) || isset($requestInput["customer_id"])){
            $model["customer_id"] = $requestInput["supplier_id"] ?? $requestInput["customer_id"];
        }
        if(isset($requestInput["date"])){
            $model["date"] = $requestInput["date"];
        }
        if(isset($requestInput["ref_code"])){
            $model["supplier_invoice_code"] = $requestInput["ref_code"];
        }
        return $this->invoice->updateOneById($id, $model);
    }

    // New
    public function export(InvoiceWarehouseFilterRequest $invoiceWarehouseFilterRequest)
    {
        LogEx::methodName($this->className, 'export');
        $userInfo = $invoiceWarehouseFilterRequest->userInfo;
        $requestInput = $invoiceWarehouseFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->warehouse->filter(["type" => $requestInput["type"] ?? null], $userInfo->drug_store_id, 35000);
                    break;
                case "current_page":
                    $data = $this->warehouse->filter($invoiceWarehouseFilterRequest, $userInfo->drug_store_id);
                    break;
                case "current_search":
                    $invoiceWarehouseFilterRequest->request->remove("page");
                    $data = $this->warehouse->filter($invoiceWarehouseFilterRequest, $userInfo->drug_store_id, 35000);
                    break;
            }
        } else {
            $data = $this->invoiceWarehouse->filter($invoiceWarehouseFilterRequest, $userInfo->drug_store_id);
        }
        return $data;
    }
    /**
     * api v3
     * exportInout
    */
    public function exportInout(InvoiceWarehouseFilterRequest $invoiceWarehouseFilterRequest)
    {
        LogEx::methodName($this->className, 'exportInout');

        $requestInput = $invoiceWarehouseFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQuery(
                        'select * from v3.f_stockym_get_data(?)',
                        [Utils::getParams($requestInput)],
                        $invoiceWarehouseFilterRequest->url(),
                        $requestInput,
                        1,
                        3500
                    );
                    break;
                case "current_page":
                    $data = Utils::executeRawQuery(
                        'select * from v3.f_stockym_get_data(?)',
                        [Utils::getParams($requestInput)],
                        $invoiceWarehouseFilterRequest->url(),
                        $requestInput,
                        1
                    );
                    break;
                case "current_search":
                    $invoiceWarehouseFilterRequest->request->remove("page");
                    $data = Utils::executeRawQuery(
                        'select * from v3.f_stockym_get_data(?)',
                        [Utils::getParams($requestInput)],
                        $invoiceWarehouseFilterRequest->url(),
                        $requestInput,
                        1,
                        3500
                    );
                    break;
            }
        }

        return $data;
    }

    /**
     * api v3
     * from f_stockym_get_data on v3
    */
    public function getWarehouseInOutV3_old($request, $export = 0, $limit = 10)
    {
        LogEx::methodName($this->className, 'getWarehouseInOutV3');

        $drugStoreId = $request->userInfo->drug_store_id;
        $input = $request->input();
        $fromDate = $input["from_date"] ?? null;
        $toDate = $input["to_date"] ?? null;
        $reportBy = $input["report_by"] ?? null;
        $searchText = $input["query"] ?? null;
        $queryDB = '1 = 1';

        if (isset($fromDate)) {
            $queryDB = $queryDB . " AND t_invoice.receipt_date >= '" . $fromDate . " 00:00:00.000000'";
        }

        if (isset($toDate)) {
            $queryDB = $queryDB . " AND t_invoice.receipt_date <= '" . $toDate . " 23:59:59.999999'";
        }

        if (isset($searchText)) {
            $queryDB = $queryDB . " AND (t_invoice.invoice_code ~* '" . $searchText . "' or drug.drug_code  ~* '" .
                $searchText . "' or drug.name  ~* '" . $searchText . "' or id.number  ~* '" . $searchText .
                "')";
        }
        $invoiceType = 'IV1';

        if ($reportBy === 'import') {
            $invoiceType = 'IV2';
        }

        $resoult = DB::table("invoice as t_invoice")
            ->select(
                't_invoice.id',
                't_invoice.invoice_code',
                'drug.drug_code',
                'drug.name as drug_name',
                'id.number',
                'id.expiry_date as expiry_date',
                'id.unit_id',
                'unit.name as unit_name',
                DB::raw("SUM(case when t_invoice.invoice_type = '$invoiceType' 
                then coalesce(id.quantity, 0)::bigint else 0 end) as quantity"),
                DB::raw("(SELECT sum(r_id.quantity) as total from invoice r_invoice
                 inner join invoice_detail r_id on r_id.invoice_id = r_invoice.id and r_id.number = id.number
                 inner join drug on drug.id = r_id.drug_id
                 inner join unit on unit.id = r_id.unit_id
                 left join customer on customer.id = r_invoice.customer_id
                 where r_invoice.refer_id = t_invoice.id and r_invoice.status = 'done' and 
                 r_invoice.drug_store_id = $drugStoreId and $queryDB) as return_quantity"),
                DB::raw('coalesce(id.org_cost, id.cost) as cost'),
                DB::raw("case when id.combo_name is null or id.combo_name = '' then coalesce(id.org_cost, id.cost) - id.cost else 0 end as discount"),
                DB::raw('max(id.vat) as vat'),
                //amount frontend
                DB::raw("case  when '$invoiceType' = 'IV1' then t_invoice.receipt_date else t_invoice.created_at
                    end::date as created_at"),
                DB::raw("case when '$invoiceType' = 'IV1' then coalesce(customer.name, 'Khách lẻ')
                else supplier.name end as customer_name"),
                'customer.number_phone',
                't_invoice.supplier_invoice_code'
            )
            ->join("invoice_detail as id", function ($join) {
                $join->on("id.invoice_id", "=", "t_invoice.id")
                    ->where("id.drug_id", ">", 0);
            })
            ->join("drug", "drug.id", "id.drug_id")
            ->join("unit", "unit.id", "id.unit_id")
            ->leftJoin("customer", "customer.id", "t_invoice.customer_id")
            ->leftJoin("supplier", "supplier.id", "t_invoice.customer_id")
            ->where("t_invoice.drug_store_id", $drugStoreId)
            ->where("t_invoice.invoice_type", "=", $invoiceType)
            ->where("t_invoice.status", "=", "done")
            ->whereRaw($queryDB)
            ->orderByDesc('t_invoice.created_at')
            ->orderByDesc('t_invoice.updated_at')
            ->groupBy(["id.unit_id", "unit.name", "id.number", "id.expiry_date", "id.drug_id", "drug.drug_code",
                "id.org_cost", "id.cost", "id.combo_name", "id.quantity", "customer.name", "customer.number_phone",
                "drug.name", "t_invoice.id", "supplier.name"]);

        if ($export) return $resoult->paginate($limit);

        return $resoult;
    }

    /**
     * api v3
     * from f_stockym_get_data on v3
    */
    public function getWarehouseInOutV3($request, $export = 0, $limit = 10)
    {
        LogEx::methodName($this->className, 'getWarehouseInOutV3');

        $drugStoreId = $request->userInfo->drug_store_id;
        $input = $request->input();

        $p_store_id      = $drugStoreId;
        $p_start_date    = $input['from_date'] ?? null;
        $p_end_date      = $input['to_date'] ?? null;
        $p_drug_id       = $input['drug_id'] ?? null;
        $p_search_str    = $input['query'] ?? null;
        $v_curym         = Carbon::now()->toDateString();
        $v_ym            = $p_start_date;
        $v_nextym        = date('Y-m-d', strtotime($p_end_date . " + 30 days"));

        $tmp_stockym_get_invoice_end = DB::select("
            select  i.drug_store_id,
                    id.drug_id,
                    id.number,
                    max(id.expiry_date)                 as expiry_date,
                    max(w.unit_id)                      as unit_id,
                    sum(case
                        when i.invoice_type in ('IV2', 'IV7', 'IV3') then -1
                        else 1
                    end * id.quantity * id.exchange)    as quantity,
                    max(w.main_cost)                    as main_cost,
                    max(w.pre_cost)                     as pre_cost,
                    max(w.current_cost)                 as current_cost
            from    invoice i
                inner join invoice_detail id
                    on  id.invoice_id   = i.id
                    and id.drug_id      = {$p_drug_id}
                inner join warehouse w
                    on  w.drug_id       = id.drug_id
                    and w.is_check      = true
                    and w.is_basic      = 'yes'
                    and w.drug_store_id = {$p_store_id}
            where   i.drug_store_id     = {$p_store_id}
            and     i.status            in ('done', 'processing')
            and     case
                        when i.invoice_type in ('IV2', 'IV7') then i.created_at
                        else i.receipt_date
                    end::date           between {$p_end_date} + interval '1 day' and date_trunc('month', {$p_end_date} + interval '1 month') - interval '1 day'
            group by i.drug_store_id, id.drug_id, id.number
        ");

        return $tmp_stockym_get_invoice_end;
    }

    /**
     * api v3
     * from tmp_stockym_get_invoice_end
    */
    public function tmpStockymGetInvoiceEnd($drugStoreID)
    {
        LogEx::methodName($this->className, 'tmpStockymGetInvoiceEnd');

        return DB::table(DB::raw('invoice i'))
            ->select('i.drug_store_id','id.drug_id','id.number',
                DB::raw('max(id.expiry_date) as expiry_date'),
                DB::raw('max(w.unit_id) as unit_id'),
                DB::raw('sum(case
                when i.invoice_type in (\'IV2\', \'IV7\', \'IV3\') then -1
                else 1
            end * id.quantity * id.exchange) as quantity'),
                DB::raw('max(w.main_cost) as main_cost'),
                DB::raw('max(w.pre_cost) as pre_cost'),
                DB::raw('max(w.current_cost) as current_cost'))
            ->join(DB::raw('invoice_detail id'), function($join) use ($drugStoreID) {
                $join->on('id.invoice_id', '=', 'i.id');
            })
            ->join(DB::raw('warehouse w'), function($join) use ($drugStoreID) {
                $join->on('w.drug_id', '=', 'id.drug_id')
                    ->whereRaw('w.is_check = true')
                    ->where('w.is_basic', '=', 'yes')
                    ->where('w.drug_store_id', '=', $drugStoreID);
            })
            ->whereRaw('i.invoice_type IN (\'IV2\', \'IV7\')theni.created_atelsei.receipt_dateend::datep_end_date+interval\'1 day')
            ->where('i.drug_store_id', '=', $drugStoreID)
            ->whereIn('i.status',['done', 'processing'])
            ->groupBy(['i.drug_store_id','id.drug_id','id.number'])
            ->get()
            ->toArray();
    }
}
