<?php
/**
 * Created by PhpStorm.
 * User: hieu
 * Date: 3/2/2019
 * Time: 2:02 PM
 */

namespace App\Services;

use App\Http\Requests\InvoiceWarehouse\InvoiceWarehouseFilterRequest;
use App\Http\Requests\Report\ReportGoodsInOutFilterRequest;
use App\Http\Requests\Report\ReportRevenueFilterRequest;
use App\Http\Requests\Report\ReportRevenueProfitFilterRequest;
use App\Http\Requests\Report\ReportSalePersonFilterRequest;
use App\Models\Drug;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use Config;
use App\Models\Warehouse;
use App\Services\CommonConstant;
use App\Models\InvoiceDetail;
use App\Models\Invoice;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Repositories\InvoiceDetail\InvoiceDetailRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;

/**
 * Class Base chứa hàm xử lý common cho API trạm và sensor
 * VD : login, header, footer của một file XML
 *
 * @package App\Services
 */
class ReportService
{
    protected $className = "ReportService";

    protected $drug;
    protected $warehouse;
    protected $invoice_detail;

    public function __construct(
        DrugRepositoryInterface          $drug,
        WarehouseRepositoryInterface     $warehouse,
        InvoiceDetailRepositoryInterface $invoice_detail
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->drug = $drug;
        $this->warehouse = $warehouse;
        $this->invoice_detail = $invoice_detail;

    }

    public function getInventoryByDrugstore($drug_store_id, $from_date, $to_date, $category, $group_drug, $name)
    {
        LogEx::methodName($this->className, 'getInventoryByDrugstore');

        $data_result = array();
        $drug_store = Drug::leftJoin('drug_category', 'drug_category.id', '=', 'drug.drug_category_id')
            ->leftJoin('drug_group', 'drug_group.id', '=', 'drug.drug_group_id')
            ->join('warehouse', 'warehouse.drug_id', 'drug.id')
            ->select(
                'drug.*',
                'warehouse.number'
            );

        if ($category != null) {
            $drug_store = $drug_store->where('drug_category.id', $category);
        }
        if ($group_drug != null) {
            $drug_store = $drug_store->where('drug_group.id', $group_drug);
        }
        if ($name != null) {
            $drug_store = $drug_store->where('drug.name', 'ilike', '%' . $name . '%');
        }
        $drug_store = $drug_store->where('drug.drug_store_id', $drug_store_id)
            ->where('warehouse.number', '!=', '')
            ->where('warehouse.is_check', 0)
            ->where('warehouse.quantity', '>', 0)
            ->where('warehouse.is_basic', 'yes')
            ->paginate(20);

        $drug_store = json_decode(json_encode($drug_store), true);

        $current_page = $drug_store['current_page'];
        $first_page_url = $drug_store['first_page_url'];
        $from = $drug_store['from'];
        $last_page = $drug_store['last_page'];
        $to = $drug_store['to'];
        $total = $drug_store['total'];
        $path = $drug_store['path'];
        $last_page_url = $drug_store['last_page_url'];
        $next_page_url = $drug_store['next_page_url'];

        $date_invoice = [];
        if (!empty($from_date)) {
            $date_invoice['invoice_from_date'] = Carbon::createFromFormat('Y-m-d', $from_date);
        }

        if (!empty($to_date)) {
            $date_invoice['invoice_to_date'] = Carbon::createFromFormat('Y-m-d', $to_date);
        }

        foreach ($drug_store['data'] as $value) {
            $tmp = $this->getValueDrugById($value['id'], $value['number'], $drug_store_id, $date_invoice);
            if (!empty($tmp)) {
                foreach ($tmp as $item) {
                    $data_result['data'][] = $item;
                }
            }
        }

        // Tỉnh tổng tiền
        $sum = $this->getInventorySum($drug_store_id, $date_invoice);

        $data_result['total_quantity_beginning'] = $sum['total_quantity_beginning'];
        $data_result['total_cost_beginning'] = $sum['total_cost_beginning'];

        $data_result['total_quantity_ending'] = $sum['total_quantity_ending'];
        $data_result['total_cost_ending'] = $sum['total_cost_ending'];

        $data_result['total_export'] = $sum['total_export'];
        $data_result['total_export_cost'] = $sum['total_export_cost'];

        $data_result['total_import'] = $sum['total_import'];
        $data_result['total_import_cost'] = $sum['total_import_cost'];

        $data_result['current_page'] = $current_page;
        $data_result['first_page_url'] = $first_page_url;
        $data_result['from'] = $from;
        $data_result['last_page'] = $last_page;
        $data_result['to'] = $to;
        $data_result['total'] = $total;
        $data_result['path'] = $path;
        $data_result['last_page_url'] = $last_page_url;
        $data_result['next_page_url'] = $next_page_url;

        return $data_result;
    }

    //Quan start add 01042020
    public function getInventoryByDrugstoreFull($drug_store_id)
    {
        LogEx::methodName($this->className, 'getInventoryByDrugstoreFull');

        $data_result = array();
        $query = Drug::join('warehouse', 'warehouse.drug_id', 'drug.id')
            ->join('unit', 'warehouse.unit_id', 'unit.id')
            ->select(\DB::raw('warehouse.*, drug.*, unit.name as unit_name, warehouse.number as batch_number, warehouse.expiry_date as warehouse_expiry_date, warehouse.quantity as totalQuantity, warehouse.quantity * warehouse.current_cost as totalCost'));
        //->select(\DB::raw('warehouse.*, drug.*, unit.name as unit_name, warehouse.number as batch_number, DATE_FORMAT(warehouse.expiry_date, \'%Y-%m-%d\') as warehouse_expiry_date, warehouse.quantity as totalQuantity, warehouse.quantity * warehouse.current_cost as totalCost'));

        $query = $query->where('drug.drug_store_id', $drug_store_id)
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->where('warehouse.number', '!=', '')
            ->where('warehouse.is_check', 0)
            ->where('warehouse.quantity', '>', 0)
            ->where('warehouse.is_basic', 'yes')
            ->orderBy('drug.name', 'asc')
            ->orderBy('warehouse_expiry_date', 'asc');

        $data = null;
        $data = $query->get();
        return $data;

        //return $data_result;
    }
    //Quan end add 01042020

    /*
     *      IV1 : so luong basn
     *      IV2 : so luong nhap
     *      IV3 khach hang tra lai
     *      IV4 Tra lai NCC
     *      IV8 Xuat huy
     *
     * */
    public function reportDrugByType($drug_store_id, $type)
    {
        LogEx::methodName($this->className, 'reportDrugByType');

        $data = null;
        $query = InvoiceDetail::join('drug', 'drug.id', '=', 'invoice_detail.drug_id')
            ->join('drugstores', 'drugstores.id', '=', 'drug.drug_store_id')
            ->join('warehouse', 'warehouse.unit_id', '=', 'invoice_detail.unit_id')
            ->join('unit', 'warehouse.unit_id', '=', 'unit.id')
            ->join('invoice', 'invoice.id', '=', 'invoice_detail.invoice_id');
        $query = $query->select('drug.*', \DB::raw('SUM(invoice_detail.quantity*warehouse.exchange) as quantity'));
        $query = $query->where('drugstores.id', $drug_store_id)
            ->where('warehouse.is_check', 1)
            ->where('invoice.invoice_type', $type);

        $data = $query->groupBy('drug.name')->orderBy('quantity', 'DESC')->take(10)->get();
        return $data;

    }

    public function getInventorySum($drug_store_id, $date_invoice)
    {
        LogEx::methodName($this->className, 'getInventorySum');

        $sum = [];
        $return_data = $this->getHistoryWarehouse($drug_store_id, $date_invoice);

        $quantity_beginning = 0;
        $cost_beginning = 0;
        if (!empty($date_invoice['invoice_from_date'])) {
            $date_invoice_old['invoice_to_date'] = $date_invoice['invoice_from_date']->copy()->subDay();
            $old_data = $this->getHistoryWarehouse($drug_store_id, $date_invoice_old);

            $quantity_beginning = $old_data['quantity_change'];
            $cost_beginning = $old_data['cost_change'];
        }

        $sum['total_quantity_beginning'] = $quantity_beginning;
        $sum['total_cost_beginning'] = $cost_beginning;

        $sum['total_quantity_ending'] = $quantity_beginning + $return_data['quantity_change'];
        $sum['total_cost_ending'] = $cost_beginning + $return_data['cost_change'];

        $sum['total_import'] = $return_data['iv7']['quantity'] + $return_data['iv2']['quantity'] + $return_data['iv3']['quantity'];
        $sum['total_import_cost'] = $return_data['iv7']['total_cost'] + $return_data['iv2']['total_cost'] + $return_data['iv3']['total_cost'];

        $sum['total_export'] = $return_data['iv8']['quantity'] + $return_data['iv1']['quantity'] + $return_data['iv4']['quantity'];
        $sum['total_export_cost'] = $return_data['iv8']['total_cost'] + $return_data['iv1']['total_cost'] + $return_data['iv4']['total_cost'];

        return $sum;
    }

    public function getValueDrugById($drug_id, $number, $drug_store_id, $date_invoice)
    {
        LogEx::methodName($this->className, 'getValueDrugById');

        $temp = array();
        $drug = $this->drug->findOneById($drug_id);
        $drug_return = null;
        $drug_return['id'] = $drug->id;
        $drug_return['name'] = $drug->name;
        $drug_return['image'] = $drug->image;
        $drug_return['drug_code'] = $drug->drug_code;
        $drug_return['barcode'] = $drug->barcode;
        $drug_return['vat'] = $drug->vat;

        $warehouse = DB::table('warehouse')
            ->select(
                'warehouse.quantity',
                'warehouse.number',
                'warehouse.expiry_date',
                'warehouse.main_cost',
                'unit.name as unit_name',
                'warehouse.current_cost'
            )
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('warehouse.drug_id', $drug_id)
            ->where('warehouse.number', $number)
            ->where('warehouse.is_basic', 'yes')
            ->first();
        if (!empty($warehouse)) {
            $drug_return['warehouse'] = $warehouse;
        }

        $return_data = $this->getHistoryWarehouse($drug_store_id, $date_invoice, $drug_id, $number);

        $quantity_beginning = 0;
        $cost_beginning = 0;
        if (!empty($date_invoice['invoice_from_date'])) {
            $date_invoice_old['invoice_to_date'] = $date_invoice['invoice_from_date']->copy()->subDay();
            $old_data = $this->getHistoryWarehouse($drug_store_id, $date_invoice_old, $drug_id, $number);

            $quantity_beginning = $old_data['quantity_change'];
            $cost_beginning = $old_data['cost_change'];
        }

        $drug_return['iv2'] = $return_data['iv2'];
        $drug_return['iv1'] = $return_data['iv1'];
        $drug_return['iv3'] = $return_data['iv3'];
        $drug_return['iv4'] = $return_data['iv4'];
        $drug_return['iv7'] = $return_data['iv7'];
        $drug_return['iv8'] = $return_data['iv8'];
        $drug_return['vouchers_check'] = $return_data['vouchers_check'];

        $drug_return['quantity_ending'] = $quantity_beginning + $return_data['quantity_change'];
        $drug_return['cost_ending'] = $cost_beginning + $return_data['cost_change'];

        $drug_return['quantity_beginning'] = $quantity_beginning;
        $drug_return['cost_beginning'] = $cost_beginning;

        $temp[] = $drug_return;

        return $temp;
    }

    public function getHistoryWarehouse($drug_store_id, $date_invoice, $drug_id = null, $number = null)
    {
        LogEx::methodName($this->className, 'getHistoryWarehouse');

        $data = [];

        $data['iv1'] = $this->getInvoiceDetail($drug_store_id, $date_invoice, 'IV1', $drug_id, $number);
        $data['iv2'] = $this->getInvoiceDetail($drug_store_id, $date_invoice, 'IV2', $drug_id, $number);
        $data['iv3'] = $this->getInvoiceDetail($drug_store_id, $date_invoice, 'IV3', $drug_id, $number);
        $data['iv4'] = $this->getInvoiceDetail($drug_store_id, $date_invoice, 'IV4', $drug_id, $number);
        $data['iv7'] = $this->getInvoiceDetail($drug_store_id, $date_invoice, 'IV7', $drug_id, $number);
        $data['iv8'] = $this->getInvoiceDetail($drug_store_id, $date_invoice, 'IV8', $drug_id, $number);
        $data['vouchers_check'] = $this->getVoucherCheckDetail($drug_store_id, $date_invoice, $drug_id, $number);
        $data['quantity_change'] = $data['iv7']['quantity'] + $data['iv2']['quantity'] - $data['iv4']['quantity'] +
            $data['vouchers_check']['quantity'] - $data['iv8']['quantity'] - $data['iv1']['quantity'] + $data['iv3']['quantity'];

        $data['cost_change'] = $data['iv7']['total_cost'] + $data['iv2']['total_cost'] - $data['iv4']['total_cost'] +
            $data['vouchers_check']['total_cost'] - $data['iv8']['total_cost'] - $data['iv1']['total_cost'] + $data['iv3']['total_cost'];

        return $data;
    }

    public function getInvoiceDetail($drug_store_id, $date_invoice, $type, $drug_id, $number)
    {
        LogEx::methodName($this->className, 'getInvoiceDetail');

        $data_result = array();
        $qty = null;
        $total_cost = null;
        $query = DB::table('invoice_detail')
            ->select(
                'invoice_detail.*'
            )
            ->join('invoice', 'invoice.id', 'invoice_detail.invoice_id')
            ->where('invoice.drug_store_id', $drug_store_id)
            ->where('invoice.invoice_type', $type)
            ->where('invoice.status', 'done');

        if (!empty($drug_id)) {
            $query->where('invoice_detail.drug_id', $drug_id);
        }
        if (!empty($number)) {
            $query->where('invoice_detail.number', $number);
        }
        if (!empty($date_invoice['invoice_from_date'])) {
            $query->where('invoice.receipt_date', '>=', $date_invoice['invoice_from_date']->copy()->startOfDay());
        }

        if (!empty($date_invoice['invoice_to_date'])) {
            $query->where('invoice.receipt_date', '<=', $date_invoice['invoice_to_date']->copy()->endOfDay());
        }

        $data = $query->get();
        foreach ($data as $value) {
            $exchange = $this->getExchangeDrug($value->unit_id, $value->drug_id);
            if (!empty($exchange->exchange)) {
                $qty = $qty + $value->quantity * $exchange->exchange;
            } else {
                $qty = $qty + $value->quantity * $value->exchange;
            }
            $total_cost = $total_cost + $value->cost * $value->quantity;
        }

        $data_result['quantity'] = $qty;
        $data_result['total_cost'] = $total_cost;

        return $data_result;

    }

    private function getVoucherCheckDetail($drug_store_id, $date_invoice, $drug_id, $number)
    {
        LogEx::methodName($this->className, 'getVoucherCheckDetail');

        $data_result = array();
        $qty = null;
        $total_cost = null;
        $query = DB::table('check_detail')
            ->select(
                'check_detail.*'
            )
            ->join('vouchers_check', 'vouchers_check.id', 'check_detail.vouchers_check_id')
            ->where('vouchers_check.drug_store_id', $drug_store_id);

        if (!empty($drug_id)) {
            $query->where('check_detail.drug_id', $drug_id);
        }
        if (!empty($number)) {
            $query->where('check_detail.number', $number);
        }
        if (!empty($date_invoice['invoice_from_date'])) {
            $query->where('vouchers_check.created_at', '>=', $date_invoice['invoice_from_date']->copy()->startOfDay());
        }

        if (!empty($date_invoice['invoice_to_date'])) {
            $query->where('vouchers_check.created_at', '<=', $date_invoice['invoice_to_date']->copy()->endOfDay());
        }

        $data = $query->get();
        foreach ($data as $value) {
            $exchange = $this->getExchangeDrug($value->unit_id, $value->drug_id);
            if (!empty($exchange->exchange)) {
                $qty = $qty + $value->diff_amount * $exchange->exchange;
            } else {
                $qty = $qty + $value->diff_amount;
            }
            $total_cost = $total_cost + $value->diff_value;
        }

        $data_result['quantity'] = $qty;
        $data_result['total_cost'] = $total_cost;

        return $data_result;
    }


    public function getExchangeDrug($unit_id, $drug_id)
    {
        LogEx::methodName($this->className, 'getExchangeDrug');

        $data = DB::table('warehouse')
            ->select(
                'warehouse.exchange'
            )
            ->where('warehouse.drug_id', $drug_id)
            ->where('warehouse.is_check', 1)
            ->where('warehouse.unit_id', $unit_id)
            ->first();
        return $data;


    }

    public function reportRevenueMoney($drug_store_id, $type, $from_date, $to_date)
    {
        LogEx::methodName($this->className, 'reportRevenueMoney');
        $data = DB::select('select * from f_report_revenue(?, ?, ?)', [$drug_store_id, $from_date, $to_date]);
        return $data;

        /* if ($type == 1) { */
        /*     $query = Invoice::selectRaw( */
        /*         'invoice.created_at::date, ' . */
        /*         '0 as drug_code, ' . */
        /*         'SUM(invoice.pay_amount + invoice.discount - invoice.vat_amount) as amount, ' . */
        /*         'SUM(invoice.discount) as discount,' . */
        /*         'SUM(invoice.pay_amount - invoice.vat_amount) as total, ' . */
        /*         'SUM(invoice.amount - invoice.discount - invoice.vat_amount - invoice.pay_amount) as debt, ' . */
        /*         'SUM(invoice.amount - invoice.discount - invoice.vat_amount) as sumamount ' */
        /*     ); */
        /*     $query = $query->where('invoice.drug_store_id', $drug_store_id) */
        /*         ->where('invoice.invoice_type', "IV1") */
        /*         ->where('invoice.status', 'done'); */

        /*     $data = $query->whereRaw('created_at::date between \'' . $form_date . '\' and \'' . $to_date . '\'') */
        /*         // ->where('created_at::date', '<=', $to_date) */
        /*         ->groupBy(DB::raw('invoice.created_at::date'))->orderByRaw('invoice.created_at::date desc')->get(); */
        /* } elseif ($type == 2) { */
        /*     // $data = null; */
        /*     $data = DB::table('invoice') */
        /*         ->select( */
        /*             'invoice.updated_at', */
        /*             'invoice.id', */
        /*             // 'invoice.amount', */
        /*             'invoice.discount', */
        /*             'invoice.invoice_code', */
        /*             'customer.name as customer_name' */
        /*         ) */
        /*         ->selectRaw('invoice.amount - (select SUM(i2.amount) as amount from invoice i2 where i2.refer_id = invoice.id) as amount') */
        /*         ->join('customer', 'customer.id', 'invoice.customer_id') */
        /*         ->where('invoice.drug_store_id', $drug_store_id) */
        /*         ->where('invoice.invoice_type', "IV1") */
        /*         ->where('invoice.updated_at', '>=', $form_date) */
        /*         ->where('invoice.updated_at', '<=', $to_date) */
        /*         ->get(); */
        /*     // foreach ($data_tmp as $value) { */
        /*     //     $value = json_decode(json_encode($value), true); */
        /*     //     $condition = Invoice::select('invoice.refer_id', \DB::raw('SUM(invoice.amount) as amount')) */
        /*     //         ->where('invoice.refer_id', $value['id']) */
        /*     //         ->groupBy('invoice.refer_id')->first(); */
        /*     //     if (!empty($condition)) { */
        /*     //         $value['amount'] = $value['amount'] - $condition->amount; */
        /*     //     } */
        /*     //     $data[] = $value; */
        /*     // } */

        /* } elseif ($type == 3) { */
        /*     // $data = array(); */
        /*     $data = DB::table('invoice') */
        /*         ->select( */
        /*             'invoice.updated_at', */
        /*             'invoice.amount', */
        /*             'invoice.discount', */
        /*             'invoice.invoice_code', */
        /*             'invoice.invoice_code', */
        /*             'invoice_detail.drug_id', */
        /*             'invoice_detail.unit_id', */
        /*             'invoice_detail.number', */
        /*             'invoice_detail.quantity', */
        /*             'invoice_detail.cost', */
        /*             'drug.drug_code', */
        /*             'drug.name as drug_name', */
        /*             'warehouse.exchange', */
        /*             'warehouse.current_cost', */
        /*             'warehouse.main_cost', */
        /*             'unit.name as unit_name' */
        /*         ) */
        /*         ->join('invoice_detail', 'invoice.id', 'invoice_detail.invoice_id') */
        /*         ->join('drug', 'invoice_detail.drug_id', 'drug.id') */
        /*         ->join('warehouse', 'warehouse.drug_id', 'drug.id') */
        /*         ->join('unit', 'warehouse.unit_id', 'unit.id') */
        /*         ->whereRaw('warehouse.number = invoice_detail.number') */
        /*         ->whereRaw('warehouse.unit_id = invoice_detail.unit_id') */
        /*         ->where('invoice.drug_store_id', $drug_store_id) */
        /*         ->where('invoice.invoice_type', "IV1") */
        /*         ->where('invoice.updated_at', '>=', $form_date) */
        /*         ->where('invoice.updated_at', '<=', $to_date) */
        /*         ->get(); */

        /*     // foreach ($temp as $value) { */
        /*     //     $drug = DB::table('drug') */
        /*     //         ->select( */
        /*     //             'drug.name as drug_name', */
        /*     //             'drug.drug_code as drug_code', */
        /*     //             'warehouse.exchange', */
        /*     //             'warehouse.current_cost', */
        /*     //             'warehouse.main_cost', */
        /*     //             'unit.name as unit_name' */
        /*     //         ) */
        /*     //         ->join('warehouse', 'warehouse.drug_id', 'drug.id') */
        /*     //         ->join('unit', 'warehouse.unit_id', 'unit.id') */
        /*     //         ->where('drug.id', $value->drug_id) */
        /*     //         ->where('warehouse.number', $value->number) */
        /*     //         ->where('warehouse.unit_id', $value->unit_id) */
        /*     //         ->first(); */

        /*     //     if (!empty($drug)) { */
        /*     //         $value->drug_code = $drug->drug_code; */
        /*     //         $value->drug_name = $drug->drug_name; */
        /*     //         $value->exchange = $drug->exchange; */
        /*     //         $value->current_cost = $drug->current_cost; */
        /*     //         $value->main_cost = $drug->main_cost; */
        /*     //         $value->unit_name = $drug->unit_name; */
        /*     //         $data[] = $value; */
        /*     //     } */
        /*     // } */
        /* } */
        /* return $data; */
    }


    public function getListExportImport($drug_store_id, $type, $form_date, $to_date)
    {
        LogEx::methodName($this->className, 'getListExportImport');

        if ($type == 1) {
            $data = DB::table('invoice')
                ->select(
                    'invoice.updated_at',
                    'invoice.amount',
                    'invoice.vat_amount',
                    'invoice.discount',
                    'invoice.invoice_code',
                    'supplier.name as supplier_name'
                )
                ->join('supplier', 'supplier.id', 'invoice.customer_id')
                ->where('invoice.drug_store_id', $drug_store_id)
                ->where('invoice.invoice_type', "IV2")
                ->where('invoice.updated_at', '>=', $form_date)
                ->where('invoice.updated_at', '<=', $to_date)
                ->get();
        } elseif ($type == 2) {
            $data = DB::table('invoice')
                ->select(
                    'invoice.updated_at',
                    'invoice.amount',
                    'invoice.vat_amount',
                    'invoice.discount',
                    'invoice.invoice_code',
                    'customer.name as customer_name'
                )
                ->join('customer', 'customer.id', 'invoice.customer_id')
                ->where('invoice.drug_store_id', $drug_store_id)
                ->where('invoice.invoice_type', "IV1")
                ->where('invoice.updated_at', '>=', $form_date)
                ->where('invoice.updated_at', '<=', $to_date)
                ->get();
        } else {
            $data = DB::table('invoice')
                ->select(
                    'invoice.updated_at',
                    'invoice.amount',
                    'invoice.vat_amount',
                    'invoice.discount',
                    'invoice.invoice_code'
                )
                ->where('invoice.drug_store_id', $drug_store_id)
                ->where('invoice.invoice_type', "IV7")
                ->where('invoice.updated_at', '>=', $form_date)
                ->where('invoice.updated_at', '<=', $to_date)
                ->get();
        }

        return $data;
    }

    public function getGoodsInOut($request)
    {
        LogEx::methodName($this->className, 'getGoodsInOut');

        $user = $request->userInfo;
        $input = $request->input();
        $from_date = $input['from_date'] ?? null;
        $to_date = $input['to_date'] ?? null;
        $reportBy = $input['reportBy'] ?? ($input['report_by'] ?? null);
        $searchStr = $input['searchStr'] ?? ($input['search_str'] ?? null);
        $invoiceCode = $input['invoiceCode'] ?? ($input['invoice_code'] ?? null);

        $data = Utils::executeRawQuery('select * from f_report_goods_in_out(?, ?, ?, ?, ?, ?)', [$user->drug_store_id, $reportBy, $from_date, $to_date, $searchStr, $invoiceCode], $request->url(), $request->input());

        return Utils::getSumData($data, $input, 'select sum(t.quantity) as quantity, sum(t.return_quantity) as return_quantity, sum(t.cost) as cost, sum(t.discount) as discount, sum(t.amount) as amount from tmp_output t');

        /* $invoice_type = $reportBy == 'import' ? 'IV2' : 'IV1'; */
        /* $col = [ */
        /*     'drug.drug_code', */
        /*     'drug.name as drug_name', */
        /*     'invoice_detail.number', */
        /*     'invoice_detail.expiry_date', */
        /*     'invoice_detail.unit_id', */
        /*     'unit.name as unit_name', */
        /*     'invoice_detail.quantity', */
        /*     'invoice_detail.cost', */
        /*     'invoice_detail.vat as vatp', */
        /*     'invoice_detail.created_at' */
        /* ]; */
        /* $query = DB::table('invoice'); */
        /* if ($reportBy == 'import') { */
        /*     $col[] = 'supplier.name as customer_name'; */
        /*     $col[] = 'invoice.supplier_invoice_code'; */

        /*     $query = $query */
        /*         ->select($col) */
        /*         ->leftJoin('supplier', 'supplier.id', 'invoice.customer_id'); */
        /* } else { */
        /*     $col[] = 'customer.name as customer_name'; */
        /*     $col[] = 'customer.number_phone'; */
        /*     $query = $query */
        /*         ->select($col) */
        /*         ->leftJoin('customer', 'customer.id', 'invoice.customer_id'); */
        /* } */

        /* $query = $query */
        /*     ->join('invoice_detail', 'invoice_detail.invoice_id', 'invoice.id') */
        /*     ->join('drug', 'drug.id', 'invoice_detail.drug_id') */
        /*     ->join('unit', 'unit.id', 'invoice_detail.unit_id') */
        /*     ->where('invoice.drug_store_id', $drug_store_id) */
        /*     ->where('invoice.invoice_type', $invoice_type) */
        /*     ->where('invoice.status', 'done') */
        /*     ->whereRaw('invoice.created_at::date between \'' . $from_date . '\' and \'' . $to_date . '\'') */
        /* ; */

        /* if (isset($inputText) && $inputText != '') { */
        /*     $query = $query */
        /*         ->join('drug_autosearch','drug_autosearch.drug_id', 'drug.id') */
        /*         ->where('drug_autosearch.drug_store_id', $drug_store_id) */
        /*         ->whereRaw(Utils::build_query_AutoCom_search($inputText, 'drug_autosearch.name_pharma_properties')); */
        /* } */

        /* $query = $query->orderByRaw('invoice.created_at::date desc, invoice.updated_at desc'); */
        /* return $query->get(); */
    }

    public function getSalesPerson($request)
    {
        LogEx::methodName($this->className, 'getGoodsInOut');

        $input = $request->input();
        $page = $input['page'] ?? 1;
        $per_page = $input['per_page'] ?? 10;

        $data = Utils::executeRawQuery("select * from v3.f_report_sale_person(?)", [Utils::getParams($input, array("page" => $page, "per_page" => $per_page,), true)]);
        $countSum = Utils::executeRawQuery("select * from v3.f_report_sale_person_count(?)", [Utils::getParams($input, array("page" => $page, "per_page" => $per_page,), true)]);

        $data = new LengthAwarePaginator($data, $countSum[0]->total, $per_page, $page);
        $options = array(
            'total_amount' => $countSum[0]->total_amount
        );
        return array_merge($data->toArray(), $options);
    }

    public function getRevenueProfit($request)
    {
        LogEx::methodName($this->className, 'getRevenueProfit');
        $user = $request->userInfo;
        $input = $request->input();
        $from_date = isset($input['from_date']) ? $input['from_date'] : null;
        $to_date = isset($input['to_date']) ? $input['to_date'] : null;

        $data = Utils::executeRawQuery('select * from f_report_revenue_profit(?, ?, ?)', [$user->drug_store_id, $from_date, $to_date], $request->url(), $input);

        return Utils::getSumData($data, $input, 'select sum(t.cost) as cost ,sum(t.return_cost) as return_cost, sum(t.revenue) as revenue, sum(t.profit) as profit, sum(t.profit) * 100 / sum(t.revenue) as profit_rate from tmp_output t');
    }

    public function unique_multidim_array($array, $key)
    {
        LogEx::methodName($this->className, 'unique_multidim_array');

        $temp_array = array();
        $i = 0;
        $key_array = array();

        foreach ($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }

    //New export
    public function exportRevenue(ReportRevenueFilterRequest $reportRevenueFilterRequest)
    {
        LogEx::methodName($this->className, 'export');
        $userInfo = $reportRevenueFilterRequest->userInfo;
        $requestInput = $reportRevenueFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->filterRevenue(["type" => $requestInput["type"] ?? null], $userInfo->drug_store_id,
                        35000);
                    break;
                case "current_page":
                    $data = $this->filterRevenue($reportRevenueFilterRequest, $userInfo->drug_store_id);
                    break;
                case "current_search":
                    $reportRevenueFilterRequest->request->remove("page");
                    $data = $this->filterRevenue($reportRevenueFilterRequest, $userInfo->drug_store_id, 35000);
                    break;
            }
        } else {
            $data = $this->filterRevenue($reportRevenueFilterRequest, $userInfo->drug_store_id);
        }
        return $data;
    }

    public function exportRevenueProfit(ReportRevenueProfitFilterRequest $reportRevenueProfitFilterRequest)
    {
        LogEx::methodName($this->className, 'exportRevenueProfit');
        $userInfo = $reportRevenueProfitFilterRequest->userInfo;
        $requestInput = $reportRevenueProfitFilterRequest->input();
        $requestInput['url'] = $reportRevenueProfitFilterRequest->url();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->filterRevenueProfit(
                        //["type" => $requestInput["type"] ?? null],
                        $requestInput,
                        $userInfo->drug_store_id,
                        1,
                        35000
                    );
                    break;
                case "current_page":
                    $data = $this->filterRevenueProfit(
                        $requestInput,
                        $userInfo->drug_store_id,
                        1
                    );
                    break;
                case "current_search":
                    $reportRevenueProfitFilterRequest->request->remove("page");
                    $data = $this->filterRevenueProfit(
                        $requestInput,
                        $userInfo->drug_store_id,
                        1,
                        35000);
                    break;
            }
        } else {
            $data = $this->filterRevenueProfit($requestInput, $userInfo->drug_store_id, 1);
        }
        return $data;
    }

    public function exportSalePerson(ReportSalePersonFilterRequest $reportSalePersonFilterRequest)
    {
        LogEx::methodName($this->className, 'exportRevenueProfit');
        $userInfo = $reportSalePersonFilterRequest->userInfo;
        $requestInput = $reportSalePersonFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->filterSalePerson(
                        ["type" => $requestInput["type"] ?? null], $userInfo->drug_store_id,
                        1,
                        35000);
                    break;
                case "current_page":
                    $data = $this->filterSalePerson($reportSalePersonFilterRequest, $userInfo->drug_store_id, 1);
                    break;
                case "current_search":
                    $reportSalePersonFilterRequest->request->remove("page");
                    $data = $this->filterSalePerson($reportSalePersonFilterRequest, $userInfo->drug_store_id, 1, 35000);
                    break;
            }
        } else {
            $data = $this->filterSalePerson($reportSalePersonFilterRequest, $userInfo->drug_store_id);
        }
        return $data;
    }

    public function exportGoodsInOut(ReportGoodsInOutFilterRequest $reportGoodsInOutFilterRequest)
    {
        LogEx::methodName($this->className, 'exportGoodsInOut');
        $userInfo = $reportGoodsInOutFilterRequest->userInfo;
        $requestInput = $reportGoodsInOutFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->filterGoodsInOut(["type" => $requestInput["type"] ?? null], $userInfo->drug_store_id, 1,35000);
                    break;
                case "current_page":
                    $data = $this->filterGoodsInOut($reportGoodsInOutFilterRequest, $userInfo->drug_store_id, 1);
                    break;
                case "current_search":
                    $reportGoodsInOutFilterRequest->request->remove("page");
                    $data = $this->filterGoodsInOut($reportGoodsInOutFilterRequest, $userInfo->drug_store_id, 1,35000);
                    break;
            }
        } else {
            $data = $this->filterSalePerson($reportGoodsInOutFilterRequest, $userInfo->drug_store_id, 1);
        }
        return $data;
    }

    //new filter
    public function filterRevenue($reportRevenueFilterRequest, $drugStoreId, $limit = null)
    {
        LogEx::methodName($this->className, 'filterRevenue');
        $limit = $limit ?? $drugFilterInput["limit"] ?? 10;
        $fromDate = $reportRevenueFilterRequest["from_date"] ?? null;
        $toDate = $reportRevenueFilterRequest["to_date"] ?? null;
        $queryDB = '1 = 1';

        if (!empty($fromDate)) {
            $queryDB = $queryDB . " AND invoice.created_at >= '" . $fromDate . " 00:00:00.000000'";
        }

        if (!empty($toDate)) {
            $queryDB = $queryDB . " AND invoice.created_at <= '" . $toDate . " 23:59:59.999999'";
        }

        $subQuery = DB::table("invoice")
            ->select(
                DB::raw("row_number() OVER (ORDER BY invoice.receipt_date) as id"),
                'invoice.receipt_date',
                DB::raw("SUM(case when coalesce(invoice.payment_method, 'cash') <> 'cash'
                then 0 else case when invoice.invoice_type = 'IV3' then - invoice.amount else
                invoice.pay_amount end end) as cash_amount"),
                DB::raw("SUM(case when coalesce(invoice.payment_method, 'cash') = 'cash'
                 then 0 else case when invoice.invoice_type = 'IV3' then - invoice.amount
                 else invoice.pay_amount end end) as not_cash_amount"),
                DB::raw("SUM(case when coalesce(invoice.method, 'direct') <> 'direct'
                then 0 else case when invoice.invoice_type = 'IV3' then - invoice.amount
                else invoice.pay_amount - coalesce(invoice.vat_amount, 0) end end) as direct_amount"),
                DB::raw("SUM(case when coalesce(invoice.method, 'direct') = 'direct'
                then 0 else case when invoice.invoice_type = 'IV3' then - invoice.amount
                else invoice.pay_amount - coalesce(invoice.vat_amount, 0) end end)  as not_direct_amount"),
                DB::raw("SUM(coalesce(invoice.vat_amount, 0)) as vat_amount"),
                DB::raw("SUM(case when invoice.invoice_type = 'IV3' then - invoice.amount
                else invoice.pay_amount + invoice.discount - coalesce(invoice.vat_amount, 0) end) as amount"),
                DB::raw("SUM(invoice.discount) as discount"),
                DB::raw("SUM(case when invoice.invoice_type = 'IV3' then - invoice.amount
                else invoice.pay_amount - coalesce(invoice.vat_amount, 0) end) as total"),
                DB::raw("SUM(invoice.amount - invoice.discount + coalesce(invoice.vat_amount, 0) - invoice.pay_amount) as debt"),
                DB::raw("SUM(case when invoice.invoice_type = 'IV3' then - invoice.amount
                else invoice.amount - invoice.discount end) as sumamount")
            )
            ->where('invoice.drug_store_id', $drugStoreId)
            ->where('invoice.status', "=", "done")
            ->whereRaw($queryDB)
            ->groupBy(["invoice.invoice_type", "invoice.receipt_date"])
            ->orderByDesc("invoice.receipt_date");
        return DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
            ->mergeBindings($subQuery)
            ->where("cash_amount", ">", 0)
            ->orWhere("not_cash_amount", ">", 0)
            ->orWhere("direct_amount", ">", 0)
            ->orWhere("not_direct_amount", ">", 0)
            ->orWhere("amount", ">", 0)
            ->orWhere("total", ">", 0)
            ->orWhere("sumamount", ">", 0)
            ->paginate($limit);
    }

    public function filterRevenueProfit($reportRevenueProfitFilterRequest, $drugStoreId, $export = null, $limit = null)
    {
        LogEx::methodName($this->className, 'filterRevenueProfit');

        $chart = $reportRevenueProfitFilterRequest["chart"] ?? 0;
        $limit = $limit ?? $drugFilterInput["limit"] ?? 10;
        $fromDate = $reportRevenueProfitFilterRequest["from_date"] ?? (($chart > 0) ? date('Y-m-d', strtotime("-6 days")) : null);
        $toDate = $reportRevenueProfitFilterRequest["to_date"] ?? (($chart > 0) ? Carbon::now()->toDateString() : null);
        $queryDB = '1 = 1';

        if (isset($fromDate)) {
            $queryDB = $queryDB . " AND invoice.created_at >= '" . $fromDate . " 00:00:00.000000'";
        }

        if (isset($toDate)) {
            $queryDB = $queryDB . " AND invoice.created_at <= '" . $toDate . " 23:59:59.999999'";
        }

        $subQuery1 = DB::table("invoice")
            ->select(
                'id.created_at',
                DB::raw('max(id.drug_id) as drug_id'),
                'id.number',
                DB::raw("case when max(id.exchange) = 0 then 0
					when max(id.combo_name) is not null and max(id.combo_name) <> ''
					then max(coalesce(id.org_cost, id.cost)) / max(id.exchange)
					else max(id.cost) / max(id.exchange) end as current_cost"),
                DB::raw("case when max(id.exchange) = 0 then 0
                else max(coalesce(id.org_cost, id.cost)) / max(id.exchange) end as org_cost"),
                DB::raw("sum(id.quantity * id.exchange) as sell_quantity "),
                DB::raw("coalesce(sum(id3.quantity * id3.exchange), 0) as return_quantity")
            )
            ->join('invoice_detail as id', 'id.invoice_id', 'invoice.id')
            ->leftJoin('invoice as i3', function ($join) {
                $join->on('i3.refer_id', '=', 'invoice.id')
                    ->where('i3.status', '=', "'done'");
            })
            ->leftJoin('invoice_detail as id3', function ($join) {
                $join->on('id3.invoice_id', '=', 'i3.id')
                    ->whereColumn('id3.drug_id', 'id.drug_id');
            })
            ->where('invoice.drug_store_id', $drugStoreId)
            ->where('invoice.invoice_type', '=', "'IV1'")
            ->where('invoice.status', '=', "'done'")
            ->whereRaw($queryDB)
            ->groupBy(["id.drug_id", "id.number", "id.created_at"]);

        $subQuery2 = DB::table("invoice")
            ->select(
                'id.created_at',
                'id.drug_id',
                'id.number',
                DB::raw('id.cost / id.exchange as current_cost'),
                DB::raw('coalesce(id.org_cost, id.cost) / id.exchange as org_cost'),
                DB::raw('0 as sell_quantity'),
                DB::raw('id.quantity * id.exchange as return_quantity')
            )
            ->join('invoice_detail as id', 'id.invoice_id', 'invoice.id')
            ->join('invoice as i1', function ($join) {
                $join->on('i1.id', '=', 'i1.refer_id')
                    ->where('i1.status', '=', "'done'");
            })
            ->where('invoice.drug_store_id', $drugStoreId)
            ->where('invoice.invoice_type', '=', "'IV3'")
            ->where('invoice.status', '=', "'done'")
            ->whereRaw($queryDB)
            ->unionAll($subQuery1);
        $sql_with_bindings = str_replace_array('?', $subQuery2->getBindings(), $subQuery2->toSql());

        if ($chart > 0) {
            $datas['chart'] = DB::table(DB::raw("($sql_with_bindings) as invoice"))
                ->select(
                    DB::raw('Date(invoice.created_at) as created_at'),
                    //doanh thu tuan
                    DB::raw('sum(invoice.current_cost * (invoice.sell_quantity - invoice.return_quantity)) as revenue'),
                    //gia von
                    DB::raw("sum(case when warehouse.pre_cost is null or warehouse.pre_cost = 0 then warehouse.main_cost
				    else warehouse.pre_cost end * (invoice.sell_quantity))	as cost"),
                    //loi nhuan gop
                    DB::raw("sum(case when warehouse.pre_cost is null or warehouse.pre_cost = 0 then warehouse.main_cost
                    else warehouse.pre_cost end * (invoice.sell_quantity - invoice.return_quantity) * -1 + invoice.current_cost *
                    (invoice.sell_quantity - invoice.return_quantity)) as profit")
                )
                ->join('drug', function ($join) use ($drugStoreId) {
                    $join->on('drug.id', '=', 'invoice.drug_id')
                        ->where('drug.drug_store_id', $drugStoreId)
                        ->where('drug.active', '=', 'yes');
                })
                ->join('warehouse', function ($join) use ($drugStoreId) {
                    $join->on('warehouse.drug_id', '=', 'drug.id')
                        ->whereColumn('warehouse.number', 'invoice.number')
                        ->where('warehouse.is_basic', '=', 'yes')
                        ->where('warehouse.is_check', '=', false)
                        ->where('warehouse.drug_store_id', $drugStoreId);
                })
                ->join('unit', 'unit.id', 'warehouse.unit_id')
                ->groupBy(DB::raw('Date(invoice.created_at)'))
                ->get()
                ->toArray();

            $tmpDatas = [];

            foreach ($datas['chart'] as $item) {
                $tmpDatas[] = get_object_vars($item);
            }

            $datas['sum_data'] = [
                'revenue' => array_sum(array_column($tmpDatas, 'revenue')),
                'cost' => array_sum(array_column($tmpDatas, 'cost')),
                'profit' => array_sum(array_column($tmpDatas, 'profit'))
            ];

            return $datas;
        } else {
            $query = DB::table(DB::raw("($sql_with_bindings) as invoice"))
                ->select(
                    'invoice.drug_id',
                    'drug.name as drug_name',
                    'drug.drug_code',
                    'invoice.number',
                    'warehouse.expiry_date',
                    'warehouse.unit_id',
                    'unit.name as unit_name',
                    'invoice.sell_quantity',
                    'invoice.return_quantity',
                    DB::raw("case
                        when warehouse.pre_cost is null or warehouse.pre_cost = 0 then warehouse.main_cost
                        else warehouse.pre_cost end	as pre_cost"),
                    DB::raw('warehouse.current_cost as warehouse_current_cost'),
                    'invoice.current_cost',
                    'invoice.org_cost',
                    DB::raw("coalesce(invoice.org_cost, invoice.current_cost) - invoice.current_cost as discount"),
                    DB::raw("case when warehouse.pre_cost is null or warehouse.pre_cost = 0 then warehouse.main_cost
				else warehouse.pre_cost end * (invoice.sell_quantity)	as cost"),
                    DB::raw('invoice.current_cost * invoice.return_quantity as return_cost'),
                    DB::raw('invoice.current_cost * (invoice.sell_quantity - invoice.return_quantity) as revenue'),
                    DB::raw("case when warehouse.pre_cost is null or warehouse.pre_cost = 0 then warehouse.main_cost
				else warehouse.pre_cost end * (invoice.sell_quantity - invoice.return_quantity) * -1 + invoice.current_cost *
				(invoice.sell_quantity - invoice.return_quantity) as profit"),
                    DB::raw("case
				when invoice.current_cost * (invoice.sell_quantity - invoice.return_quantity) = 0 then 0
				else round((case when warehouse.pre_cost is null or warehouse.pre_cost = 0 then warehouse.main_cost
						else warehouse.pre_cost end * (invoice.sell_quantity - invoice.return_quantity) * -1 + invoice.current_cost *
						(invoice.sell_quantity - invoice.return_quantity)) * 100 /
						(invoice.current_cost * (invoice.sell_quantity - invoice.return_quantity)), 2)
			        end	as profit_rate")
                )
                ->join('drug', function ($join) use ($drugStoreId) {
                    $join->on('drug.id', '=', 'invoice.drug_id')
                        ->where('drug.drug_store_id', $drugStoreId)
                        ->where('drug.active', '=', 'yes');
                })
                ->join('warehouse', function ($join) use ($drugStoreId) {
                    $join->on('warehouse.drug_id', '=', 'drug.id')
                        ->whereColumn('warehouse.number', 'invoice.number')
                        ->where('warehouse.is_basic', '=', 'yes')
                        ->where('warehouse.is_check', '=', false)
                        ->where('warehouse.drug_store_id', $drugStoreId);
                })
                ->join('unit', 'unit.id', 'warehouse.unit_id')
                //->whereRaw($queryDB)
                ->orderByRaw('drug.name, warehouse.expiry_date');
                //->paginate($limit);

                $queries = $query;
                $query_sum = $query
                    ->get()
                    ->toArray();
                $data = Utils::executeRawQueryV3(
                    $queries,
                    $reportRevenueProfitFilterRequest['url'],
                    $reportRevenueProfitFilterRequest,
                    $export,
                    $limit
                );
                $query_sums = [];

                foreach ($query_sum as $item) {
                    $query_sums[] = get_object_vars($item);
                }

                $sum_data = [
                    'cost' => array_sum(array_column($query_sums, 'cost')),
                    'return_cost' => array_sum(array_column($query_sums, 'return_cost')),
                    'revenue' => array_sum(array_column($query_sums, 'revenue')),
                    'profit' => array_sum(array_column($query_sums, 'profit')),
                    'profit_rate' => array_sum(array_column($query_sums, 'profit_rate'))
                ];

                return Utils::getSumDataV3($data, $reportRevenueProfitFilterRequest, $sum_data);
        }
    }

    public function filterSalePerson($reportSalePersonFilterRequest, $drugStoreId, $export = null, $limit = null)
    {
        LogEx::methodName($this->className, 'filterRevenue');
        $limit = $limit ?? $drugFilterInput["limit"] ?? 10;
        $fromDate = $reportSalePersonFilterRequest["from_date"] ?? null;
        $toDate = $reportSalePersonFilterRequest["to_date"] ?? null;
        $sale = $reportSalePersonFilterRequest["sale"] ?? null;
        $searchText = $reportSalePersonFilterRequest["query"] ?? null;
        $monopoly = empty($reportSalePersonFilterRequest["is_monopoly"]) ? null : $reportSalePersonFilterRequest["is_monopoly"];
        $queryDB = '1 = 1';
        $url = $reportSalePersonFilterRequest['url'] ?? null;

        if (isset($fromDate)) {
            $queryDB = $queryDB . " AND t_invoice.receipt_date >= '" . $fromDate . " 00:00:00.000000'";
        }

        if (isset($toDate)) {
            $queryDB = $queryDB . " AND t_invoice.receipt_date <= '" . $toDate . " 23:59:59.999999'";
        }
        if (isset($sale)) {
            $queryDB = $queryDB . " AND t_invoice.sale_id = $sale";
        }
        if (isset($searchText)) {
            $queryDB = $queryDB . " AND (t_invoice.invoice_code ~* '" . $searchText . "' or drug.name  ~* '" . $searchText . "')";
        }
        if (isset($monopoly)) {
            if ($monopoly == 'true') {
                $queryDB = $queryDB . " AND drug.is_monopoly = true";
            } else {
                $queryDB = $queryDB . " AND (drug.is_monopoly is null or drug.is_monopoly = false)";
            }
        }

        $datas = DB::table("invoice as t_invoice")
            ->select(
                't_invoice.id',
                't_invoice.invoice_code',
                'drug.drug_code',
                DB::raw('coalesce(drug.is_monopoly, false) as is_monopoly'),
                'drug.name as drug_name',
                'id.number',
                'id.expiry_date',
                'id.unit_id',
                'unit.name as unit_name',
                DB::raw("SUM(case when t_invoice.invoice_type = 'IV1' then coalesce(id.quantity, 0)::bigint else 0 end) as quantity"),
                DB::raw("(SELECT sum(r_id.quantity) as total from invoice r_invoice
                 inner join invoice_detail r_id on r_id.invoice_id = r_invoice.id and r_id.number = id.number
                 inner join drug on drug.id = r_id.drug_id
                 inner join unit on unit.id = r_id.unit_id
                 inner join customer on customer.id = r_invoice.customer_id
                 inner join users on users.id = r_invoice.sale_id
                 where r_invoice.refer_id = t_invoice.id and r_invoice.status = 'done' and
                 r_invoice.drug_store_id = $drugStoreId and $queryDB) as return_quantity"),
                DB::raw('coalesce(id.org_cost, id.cost) as cost'),
                't_invoice.receipt_date as created_at',
                DB::raw("coalesce(customer.name, 'Khách lẻ') as customer_name"),
                'customer.number_phone',
                'users.name as sale_name'
            )
            ->join("invoice_detail as id", function ($join) {
                $join->on("id.invoice_id", "=", "t_invoice.id")
                    ->where("id.drug_id", ">", 0);
            })
            ->join("drug", "drug.id", "id.drug_id")
            ->join("unit", "unit.id", "id.unit_id")
            ->join("customer", "customer.id", "t_invoice.customer_id")
            ->join("users", "users.id", "t_invoice.sale_id")
            ->where("t_invoice.drug_store_id", $drugStoreId)
            ->where("t_invoice.invoice_type", "=", "IV1")
            ->where("t_invoice.status", "=", "done")
            ->whereRaw($queryDB)
            ->orderByDesc('t_invoice.receipt_date')
            ->orderByDesc('t_invoice.updated_at')
            ->groupBy(["id.unit_id", "unit.name", "id.number", "id.expiry_date", "id.drug_id", "drug.drug_code",
                "id.org_cost", "id.cost", "id.combo_name", "id.quantity", "customer.name", "customer.number_phone",
                "users.name", "drug.name", "t_invoice.id", "drug.is_monopoly"]);
            //->paginate($limit);
        if ($export) {
            return $datas->paginate($limit);
        }

        $queries = $datas;
        $query_sum = $datas
            ->get()
            ->toArray();

        $dataSum = Utils::executeRawQueryV3(
            $queries,
            $url,
            $reportSalePersonFilterRequest
        );

        foreach ($query_sum as $index => $item) {
            $query_sum[$index]->amount =( $item->cost) * ($item->return_quantity ?? 1);
        }

        $sum_data = [
            'total_amount' => array_sum(array_column($query_sum, 'amount'))
        ];

        return Utils::getSumDataV3($dataSum, $reportSalePersonFilterRequest, $sum_data);
    }

    //filter goodsInOut
    public function filterGoodsInOut($reportGoodsInOutFilterRequest, $drugStoreId, $export = null, $limit = null)
    {
        LogEx::methodName($this->className, 'filterGoodsInOut');

        $limit = $limit ?? $drugFilterInput["limit"] ?? 10;
        $fromDate = $reportGoodsInOutFilterRequest["from_date"] ?? null;
        $toDate = $reportGoodsInOutFilterRequest["to_date"] ?? null;
        $reportBy = $reportGoodsInOutFilterRequest["report_by"] ?? null;
        $searchText = $reportGoodsInOutFilterRequest["query"] ?? null;
        $queryDB = '1 = 1';
        $url = $reportGoodsInOutFilterRequest['url'] ?? null;

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

        $datas = DB::table("invoice as t_invoice")
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
        //if ($export) {
        //    return $datas->paginate($limit);
        //}

        $queries = $datas;
        $query_sum = $datas
            ->get()
            ->toArray();

        $dataSum = Utils::executeRawQueryV3(
            $queries,
            $url,
            $reportGoodsInOutFilterRequest,
            $export,
            $limit
        );

        $sum_data = [
            'amount' => array_sum(array_column($query_sum, 'cost')) *
                (
                    array_sum(array_column($query_sum, 'quantity')) -
                    array_sum(array_column($query_sum, 'return_quantity'))
                ),
            'cost' => array_sum(array_column($query_sum, 'cost')),
            'discount' => array_sum(array_column($query_sum, 'discount')),
            'quantity' => array_sum(array_column($query_sum, 'quantity')),
            'return_quantity' => array_sum(array_column($query_sum, 'return_quantity'))
        ];

        return Utils::getSumDataV3($dataSum, $reportGoodsInOutFilterRequest, $sum_data);
    }

    /**
     * api v3
     * from f_report_revenue on v1
     */
    public function reportRevenueV3($drugStoreId, $formDate, $toDate)
    {
        LogEx::methodName($this->className, 'reportRevenueV3');

        return DB::table(DB::raw('invoice i'))
            ->select(
                'i.receipt_date as created_at',
                DB::raw('0 as drug_code'),
                DB::raw('SUM(case when coalesce(i.payment_method, \'cash\') <> \'cash\' then 0 else case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount
            end end) as cash_amount'),
                DB::raw('SUM(case when coalesce(i.payment_method, \'cash\') = \'cash\' then 0 else case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount
            end end) as not_cash_amount'),
                DB::raw('SUM(case when coalesce(i.method, \'direct\') <> \'direct\' then 0 else case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount - coalesce(i.vat_amount, 0)
            end end) as direct_amount'),
                DB::raw('SUM(case when coalesce(i.method, \'direct\') = \'direct\' then 0 else case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount - coalesce(i.vat_amount, 0)
            end end) as not_direct_amount'),
                DB::raw('sum(coalesce(i.vat_amount,0)) as vat_amount'),
                DB::raw('SUM(case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount + i.discount - coalesce(i.vat_amount, 0) + coalesce(i.discount_promotion, 0)
            end) as amount'),
                DB::raw('SUM(i.discount + coalesce(i.discount_promotion, 0)) as discount'),
                DB::raw('SUM(case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount - coalesce(i.vat_amount, 0)
            end) as total'),
                DB::raw('SUM(i.amount - i.discount + coalesce(i.vat_amount, 0) - i.pay_amount - coalesce(i.discount_promotion, 0)) as debt'),
                DB::raw('SUM(case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.amount - i.discount - coalesce(i.discount_promotion, 0)
            end) as sumamount')
            )
            ->where('i.drug_store_id', '=', $drugStoreId)
            ->where('i.status', '=', 'done')
            ->where(function ($query) use ($formDate, $toDate) {
                $query->where(function ($query) use ($formDate, $toDate) {
                    $query->where('i.invoice_type', '=', 'IV1')
                        ->whereBetween('i.receipt_date', [$formDate, $toDate]);
                })
                    ->orWhere(function ($query) use ($formDate, $toDate) {
                        $query->where('i.invoice_type', '=', 'IV3')
                            ->whereBetween('i.receipt_date', [$formDate, $toDate]);
                    });
            })
            ->groupBy('i.receipt_date')
            ->orderBy('i.receipt_date', 'DESC');
    }

    /**
     * api v3
     * from f_report_revenue on v1 for mobile
     */
    public function reportRevenueV3Mobile($drugStoreId, $formDate, $toDate)
    {
        LogEx::methodName($this->className, 'reportRevenueV3');

        return DB::table(DB::raw('invoice i'))
            ->select(
                'i.receipt_date as created_at',
                DB::raw('0 as drug_code'),
                DB::raw('SUM(case when coalesce(i.payment_method, \'cash\') <> \'cash\' then 0 else case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount
            end end) as cash_amount'),
                DB::raw('SUM(case when coalesce(i.payment_method, \'cash\') = \'cash\' then 0 else case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount
            end end) as not_cash_amount'),
                DB::raw('SUM(case when coalesce(i.method, \'direct\') <> \'direct\' then 0 else case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount - coalesce(i.vat_amount, 0)
            end end) as direct_amount'),
                DB::raw('SUM(case when coalesce(i.method, \'direct\') = \'direct\' then 0 else case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount - coalesce(i.vat_amount, 0)
            end end) as not_direct_amount'),
                DB::raw('sum(coalesce(i.vat_amount,0)) as vat_amount'),
                DB::raw('SUM(case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount + i.discount - coalesce(i.vat_amount, 0) + coalesce(i.discount_promotion, 0)
            end) as amount'),
                DB::raw('SUM(i.discount + coalesce(i.discount_promotion, 0)) as discount'),
                DB::raw('SUM(case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.pay_amount - coalesce(i.vat_amount, 0)
            end) as total'),
                DB::raw('SUM(i.amount - i.discount + coalesce(i.vat_amount, 0) - i.pay_amount - coalesce(i.discount_promotion, 0)) as debt'),
                DB::raw('SUM(case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.amount - i.discount - coalesce(i.discount_promotion, 0)
            end) as sumamount')
            )
            ->where('i.drug_store_id', '=', $drugStoreId)
            ->where('i.status', '=', 'done')
            ->where(function ($query) use ($formDate, $toDate) {
                $query->where(function ($query) use ($formDate, $toDate) {
                    $query->where('i.invoice_type', '=', 'IV1')
                        ->whereBetween('i.receipt_date', [$formDate, $toDate]);
                })
                    ->orWhere(function ($query) use ($formDate, $toDate) {
                        $query->where('i.invoice_type', '=', 'IV3')
                            ->whereBetween('i.receipt_date', [$formDate, $toDate]);
                    });
            })
            ->groupBy('i.receipt_date')
            ->orderBy('i.receipt_date', 'DESC');
    }

    /**
     * api v3
     * from f_report_prescription_statistic on v1 and export
     */
    public function exporPrescriptionStatisticV3($request)
    {
        LogEx::methodName($this->className, 'exporPrescriptionStatisticV3');

        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->reportPrescriptionStatisticV3($request, 1, 35000);
                    break;
                case "current_page":
                    $data = $this->reportPrescriptionStatisticV3($request, 1);
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = $this->reportPrescriptionStatisticV3($request, 1, 35000);
                    break;
            }
        }

        return $data;
    }

    /**
     * api v3
     * reportPrescriptionStatisticV3
    */
    public function reportPrescriptionStatisticV3($request, $export = 0, $limit = 10)
    {
        LogEx::methodName($this->className, 'reportPrescriptionStatisticV3');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $fromDate = $requestInput['from_date'] ?? null;
        $toDate = $requestInput['to_date'] ?? null;
        $search = $requestInput['search'] ?? null;

        $resoult = DB::table(DB::raw('invoice i'))
            ->select(
                'i.id as invoice_id',
                'i.invoice_code',
                'i.receipt_date as receipt_date',
                'p.doctor',
                'p.name_patient',
                'p.code_invoice',
                'd.name as drug_name',
                DB::raw('id.quantity * coalesce(id.exchange, 1)
                - (coalesce(rd.quantity, 0) * coalesce(rd.exchange, 0)) as quantity'),
                'u.name as unit_name',
                'i.description'
            )
            ->join(DB::raw('invoice_detail id'), function ($join) {
                $join->on('id.invoice_id', '=', 'i.id')
                    ->where('id.drug_id', '>', 0);
            })
            ->join(DB::raw('prescription p'), 'p.invoice_id', '=', 'i.id')
            ->join(DB::raw('drug d'), function ($join) {
                $join->on('d.id', '=', 'id.drug_id')
                    ->where('d.active', '=', 'yes');
            })
            ->join(DB::raw('warehouse w'), function ($join) {
                $join->on('w.drug_id', '=', 'id.drug_id')
                    ->where('w.is_basic', '=', 'yes')
                    ->whereRaw('w.is_check = true');
            })
            ->join(DB::raw('unit u'), 'u.id', '=', 'w.unit_id')
            ->leftJoin(DB::raw('invoice ri'), function ($join) {
                $join->on('ri.refer_id', '=', 'i.id')
                    ->where('ri.invoice_type', '=', 'IV3');
            })
            ->leftJoin(DB::raw('invoice_detail rd'), function ($join) {
                $join->on('rd.invoice_id', '=', 'ri.id')
                    ->on('rd.drug_id', '=', 'id.drug_id')
                    ->on('rd.number', '=', 'id.number')
                    ->on('rd.unit_id', '=', 'id.unit_id')
                    ->on('rd.combo_name', '=', 'id.combo_name');
            })
            ->where('i.drug_store_id', '=', $user->drug_store_id)
            ->where('i.invoice_type', '=', 'IV1')
            ->when(!empty($fromDate), function ($query) use ($fromDate) {
                $query->where('i.receipt_date', '>=', $fromDate);
            })
            ->when(!empty($toDate), function ($query) use ($toDate) {
                $query->where('i.receipt_date', '<=', $toDate);
            })
            ->when(!empty($search), function ($query) use ($search) {
                $query->where(
                    (DB::raw('lower(vn_unaccent(p.name_patient))')),
                    'ILIKE',
                    '%' . strtolower(Utils::unaccent($search)) . '%')
                    ->orWhere(
                        (DB::raw('lower(vn_unaccent(p.doctor))')),
                        'ILIKE',
                        '%' . strtolower(Utils::unaccent($search)) . '%')
                    ->orWhere(
                        (DB::raw('lower(vn_unaccent(p.code_invoice))')),
                        'ILIKE',
                        '%' . strtolower(Utils::unaccent($search)) . '%');
            })
            ->orderBy('i.id', 'ASC')
            ->orderBy('id.id', 'ASC');

        if ($export) return $resoult->paginate($limit);

        return $resoult;
    }

    /**
     * api v3
     * from f_report_special_drug on v1
     */
    public function reportSpecialDrugV3($drugStoreID, $userID, $search = null, $fromDate, $toDate, $reportType = null)
    {
        LogEx::methodName($this->className, 'reportSpecialDrugV3');

        $pDrugID = null;
        $v_curym = Carbon::now()->toDateString();
        $v_ym = $toDate;
        $v_nextym = date('Y-m-d', strtotime($toDate . "+30 days"));

        $tmp_stockym_get_invoice_end = DB::query()
            ->from(DB::raw('invoice i'))
            ->select(
                'i.drug_store_id',
                'id.drug_id',
                'id.number',
                DB::raw('max(id.expiry_date) as expiry_date'),
                DB::raw('max(w.unit_id) as unit_id'),
                DB::raw('sum(case
                when i.invoice_type in (\'IV2\', \'IV7\', \'IV3\') then -1
                else 1
            end * id.quantity * id.exchange) as quantity'),
                DB::raw('max(w.main_cost) as main_cost'),
                DB::raw('max(w.pre_cost) as pre_cost'),
                DB::raw('max(w.current_cost) as current_cost'))
            ->join(DB::raw('invoice_detail id'), function($join) use ($pDrugID) {
                $join->on('id.invoice_id', '=', 'i.id');
                if ($pDrugID) $join->where('id.drug_id', '=', $pDrugID);
            })
            ->join(DB::raw('warehouse w'), function($join) use($drugStoreID) {
                $join->on('w.drug_id', '=', 'id.drug_id')
                    ->whereRaw('w.is_check = true')
                    ->where('w.is_basic', '=', 'yes')
                    ->where('w.drug_store_id', '=', $drugStoreID);
            })
            ->where('i.drug_store_id', '=', $drugStoreID)
            ->whereIn('i.status',['done', 'processing'])
            ->where(function ($query) use ($toDate, $fromDate) {
                $query->where(function ($query) use ($toDate, $fromDate) {
                    $query->whereDate(DB::raw('Date(i.created_at)'), '>=', $toDate)
                        ->orWhereDate(
                            DB::raw('Date(i.created_at)'),
                            '<=',
                            date('Y-m-d', strtotime($toDate . "+30 days"))
                        );
                })
                ->orWhere(function ($query) use ($toDate, $fromDate) {
                    $query->whereDate(DB::raw('Date(i.receipt_date)'), '>=', $toDate)
                        ->orWhereDate(
                            DB::raw('Date(i.receipt_date)'),
                            '<=',
                            date('Y-m-d', strtotime($toDate . "+30 days"))
                        );
                });
            })
            ->groupBy(['i.drug_store_id', 'id.drug_id', 'id.number']);

        $tmp_stockym_get_stockym_end = DB::query()
            ->from(DB::raw('t_stockym s'))
            ->select(
                's.drug_store_id',
                's.drug_id',
                's.number',
                's.expiry_date',
                's.unit_id',
                DB::raw('s.quantity + coalesce(i.quantity, 0) as quantity'),
                DB::raw('coalesce(i.main_cost,s.main_cost) as main_cost'),
                DB::raw('coalesce(i.pre_cost,s.pre_cost) as pre_cost'),
                DB::raw('coalesce(i.current_cost,s.current_cost) as current_cost')
            )
            ->leftJoinSub(
                $tmp_stockym_get_invoice_end,
                'i',
                function ($join) {
                    $join->on('i.drug_id', '=', 's.drug_id')
                        ->on('i.number', '=', 's.number');
                }
            )
            ->where('s.drug_store_id', '=', $drugStoreID)
            //->where('s.ym', '=', $v_nextym)
            ->when($pDrugID, function ($query) use ($pDrugID) {
                $query->Where('s.drug_id', '=', $pDrugID);
            });

        return $tmp_stockym_get_stockym_end;
    }
}



