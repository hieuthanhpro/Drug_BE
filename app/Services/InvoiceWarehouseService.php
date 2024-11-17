<?php

namespace App\Services;

use App\Http\Requests\InvoiceWarehouse\InvoiceWarehouseFilterRequest;
use App\LibExtension\Utils;
use App\Models\InvoiceWarehouse;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\InvoiceDetail\InvoiceDetailRepositoryInterface;
use App\Repositories\InvoiceWarehouse\InvoiceWarehouseRepositoryInterface;
use Carbon\Carbon;
use App\LibExtension\LogEx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


/**
 * Class InvoiceWarehouseService
 * @package App\Services
 */
class InvoiceWarehouseService
{
    protected $className = "InvoiceWarehouseService";

    protected $invoiceWarehouse;
    protected $invoiceDetail;
    protected $invoice;

    public function __construct(InvoiceWarehouseRepositoryInterface $invoiceWarehouse,
                                InvoiceDetailRepositoryInterface    $invoiceDetail,
                                InvoiceRepositoryInterface          $invoice)
    {
        LogEx::constructName($this->className, '__construct');
        $this->invoiceWarehouse = $invoiceWarehouse;
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

        if (empty($requestInput["is_import"])) {
            if ($type === "export") {
                $requestInput["code"] = 'XK' . Utils::getSequenceDB('XK');
            } else {
                $requestInput["code"] = 'NK' . Utils::getSequenceDB('NK');
            }
        }

        LogEx::info($requestInput["date"]);
        DB::beginTransaction();

        try {
            $dataInvoiceWarehouse = $this->invoiceWarehouse->create($requestInput);

            if (!empty($requestInput["is_import"]))
                $isImport = InvoiceWarehouse::where('id', $dataInvoiceWarehouse->id)->update(['is_import' => true]);

            if ($dataInvoiceWarehouse) {;
                $lineItems = [];
                foreach ($requestInput["line_items"] as $item) {
                    if ( !empty($item['expiry_date']) && !empty($item['mfg_date']) ) {
                        if ($item['expiry_date'] < $item['mfg_date']) return -10;
                    }
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
                    $data = $this->invoiceWarehouse->filter(["type" => $requestInput["type"] ?? null], $userInfo->drug_store_id, 35000);
                    break;
                case "current_page":
                    $data = $this->invoiceWarehouse->filter($invoiceWarehouseFilterRequest, $userInfo->drug_store_id);
                    break;
                case "current_search":
                    $invoiceWarehouseFilterRequest->request->remove("page");
                    $data = $this->invoiceWarehouse->filter($invoiceWarehouseFilterRequest, $userInfo->drug_store_id, 35000);
                    break;
            }
        } else {
            $data = $this->invoiceWarehouse->filter($invoiceWarehouseFilterRequest, $userInfo->drug_store_id);
        }
        return $data;
    }

    /**
     * api v3
     * from f_invoice_warehouse_detail on v3
    */
    public function invoiceWarehouseDetailV3($id, $request)
    {
        LogEx::methodName($this->className, 'invoiceWarehouseDetailV3');

        $p_drug_store_id = $request->userInfo->drug_store_id;
        $p_is_id_invoice = Utils::coalesce($request->input(), 'is_id_invoice', 'false');
        $p_id = $id;

        $v_invoice_data = InvoiceWarehouse::query()
            ->select('invoice_warehouse.*','u.name as created_by_name','s.name as supplier_name','c.name as customer_name')
            ->leftJoin(DB::raw('users u'),'u.id','=','invoice_warehouse.created_by')
            ->leftJoin(DB::raw('supplier s'),'s.id','=','invoice_warehouse.supplier_id')
            ->leftJoin(DB::raw('customer c'),'c.id','=','invoice_warehouse.customer_id')
            ->where('invoice_warehouse.drug_store_id', '=', $p_drug_store_id)
            ->when(!empty($p_is_id_invoice), function ($query) use ($p_id, $p_is_id_invoice) {
                ($p_is_id_invoice == 'false') ?
                    $query->where('invoice_warehouse.id', '=', $p_id) :
                    $query->where('invoice_warehouse.invoice_id', '=', $p_id);
            })
            ->first();

        $tmp_invoice_detail = DB::table(DB::raw('invoice_detail id'))
            ->select('id.drug_id',DB::raw('to_jsonb(id.*) || jsonb_build_object(
                                    \'drug_code\', d.drug_code,
                                    \'drug_name\', d.name,
                                    \'image\', d.image,
                                    \'unit_name\', u.name,
                                    \'drug\', case when d.id > 0 then to_jsonb(d.*) else null end
                                )       as invoice_detail'))
            ->leftJoin(DB::raw('drug d'),'d.id','=','id.drug_id')
            ->leftJoin(DB::raw('unit u'),'u.id','=','id.unit_id')
            ->where('id.warehouse_invoice_id', '=', $p_id)
            ->where('id.drug_id', '>', 0);

        $tmp_drug_units = DB::table(DB::raw('warehouse w'))
            ->select('w.drug_id',DB::raw('jsonb_agg(jsonb_build_object(\'unit_id\',u.id,\'unit_name\',
            u.name,\'exchange\',w.exchange,\'is_basic\',w.is_basic)) as units'))
            ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
            ->whereRaw('w.is_check = true')
            ->joinSub(
                $tmp_invoice_detail,
                't',
                function ($join) {
                    $join->on('t.drug_id', '=', 'w.drug_id');
                }
            )
            ->groupBy('w.drug_id');

        $tmp_drug_numbers = DB::table(DB::raw('warehouse w'))
            ->select('w.drug_id',DB::raw('jsonb_agg(jsonb_build_object(\'number\',w.number,
            \'expiry_date\',w.expiry_date,\'quantity\',w.quantity)) as numbers'))
            ->where('w.is_basic','=','yes')
            ->whereRaw('w.is_check = false')
            ->joinSub(
                $tmp_invoice_detail,
                't',
                function ($join) {
                    $join->on('t.drug_id', '=', 'w.drug_id');
                }
            )
            ->groupBy('w.drug_id')
        ;

        $query_invoice_detail = str_replace_array('?', $tmp_invoice_detail->getBindings(), $tmp_invoice_detail->toSql());

        $joinSub = DB::table(DB::raw("({$query_invoice_detail}) id"))
            ->select(DB::raw('id.invoice_detail || 
            jsonb_build_object(\'drug\', id.invoice_detail->\'drug\' || 
            jsonb_build_object(\'units\', tdu.units, \'numbers\', tdn.numbers)) as invoice_detail'))
            ->leftJoinSub(
                $tmp_drug_numbers,
                'tdn',
                function ($join) {
                    $join->on('tdn.drug_id', '=', 'id.drug_id');
                })
            ->leftJoinSub(
                $tmp_drug_units,
                'tdu',
                function ($join) {
                    $join->on('tdu.drug_id', '=', 'id.drug_id');
            })->get();

        $v_return_data = [];

        foreach ($joinSub as $key => $item) {
            $joinSub[$key]->invoice_detail = json_decode($item->invoice_detail);
            $v_return_data[] = $joinSub[$key]->invoice_detail;
        }

        return [
            'invoice' => $v_invoice_data,
            'invoice_detail' => $v_return_data
        ];
    }

    public function invoiceWarehouseDetailV3Old($id, $request)
    {
        LogEx::methodName($this->className, 'invoiceWarehouseDetailV3');

        $p_drug_store_id = $request->userInfo->drug_store_id;
        $p_is_id_invoice = Utils::coalesce($request->input(), 'is_id_invoice', 'false');
        $p_id = $id;

        $v_invoice_data = InvoiceWarehouse::query()
            ->select('invoice_warehouse.*','u.name as created_by_name','s.name as supplier_name','c.name as customer_name')
            ->leftJoin(DB::raw('users u'),'u.id','=','invoice_warehouse.created_by')
            ->leftJoin(DB::raw('supplier s'),'s.id','=','invoice_warehouse.supplier_id')
            ->leftJoin(DB::raw('customer c'),'c.id','=','invoice_warehouse.customer_id')
            ->where('invoice_warehouse.drug_store_id', '=', $p_drug_store_id)
            ->when(!empty($p_is_id_invoice), function ($query) use ($p_id, $p_is_id_invoice) {
                ($p_is_id_invoice == 'false') ?
                    $query->where('invoice_warehouse.id', '=', $p_id) :
                    $query->where('invoice_warehouse.invoice_id', '=', $p_id);
            })
            ->first();

        $tmp_invoice_detail = DB::table(DB::raw('invoice_detail id'))
            ->select('id.drug_id',DB::raw('to_jsonb(id.*) || jsonb_build_object(
                                    \'drug_code\', d.drug_code,
                                    \'drug_name\', d.name,
                                    \'image\', d.image,
                                    \'unit_name\', u.name,
                                    \'drug\', case when d.id > 0 then to_jsonb(d.*) else null end
                                )       as invoice_detail'))
            ->leftJoin(DB::raw('drug d'),'d.id','=','id.drug_id')
            ->leftJoin(DB::raw('unit u'),'u.id','=','id.unit_id')
            ->where('id.warehouse_invoice_id', '=', $p_id)
            ->where('id.drug_id', '>', 0);

        $tmp_drug_units = DB::table(DB::raw('warehouse w'))
            ->select('w.drug_id',DB::raw('jsonb_agg(jsonb_build_object(\'unit_id\',u.id,\'unit_name\',
            u.name,\'exchange\',w.exchange,\'is_basic\',w.is_basic)) as units'))
            ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
            ->whereRaw('w.is_check = true')
            ->joinSub(
                $tmp_invoice_detail,
                't',
                function ($join) {
                    $join->on('t.drug_id', '=', 'w.drug_id');
                }
            )
            ->groupBy('w.drug_id');

        $tmp_drug_numbers = DB::table(DB::raw('warehouse w'))
            ->select('w.drug_id',DB::raw('jsonb_agg(jsonb_build_object(\'number\',w.number,
            \'expiry_date\',w.expiry_date,\'quantity\',w.quantity)) as numbers'))
            ->where('w.is_basic','=','yes')
            ->whereRaw('w.is_check = false')
            ->joinSub(
                $tmp_invoice_detail,
                't',
                function ($join) {
                    $join->on('t.drug_id', '=', 'w.drug_id');
                }
            )
            ->groupBy('w.drug_id')
        ;

        $query_invoice_detail = str_replace_array('?', $tmp_invoice_detail->getBindings(), $tmp_invoice_detail->toSql());

        $joinSub = DB::table(DB::raw("({$query_invoice_detail}) id"))
            ->select(DB::raw('id.invoice_detail || 
            jsonb_build_object(\'drug\', id.invoice_detail->\'drug\' || 
            jsonb_build_object(\'units\', tdu.units, \'numbers\', tdn.numbers)) as invoice_detail'))
            ->leftJoinSub(
                $tmp_drug_numbers,
                'tdn',
                function ($join) {
                    $join->on('tdn.drug_id', '=', 'id.drug_id');
                })
            ->leftJoinSub(
                $tmp_drug_units,
                'tdu',
                function ($join) {
                    $join->on('tdu.drug_id', '=', 'id.drug_id');
                })->get();

        //$v_return_data = [];

        foreach ($joinSub as $key => $item) {
            $joinSub[$key]->invoice_detail = json_decode($item->invoice_detail);
            //$v_return_data[] = $joinSub[$key]->invoice_detail;
        }

        $v_return_data['invoice_detail'] = $joinSub;

        return [
            'invoice' => $v_invoice_data,
            'invoice_detail' => $v_return_data
        ];
    }
}
