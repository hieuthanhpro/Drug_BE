<?php

/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/12/2018
 * Time: 11:07 AM
 */

namespace App\Repositories\Invoice;

use App\Http\Requests\Invoice\InvoiceFilterRequest;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\Invoice;
use App\Repositories\AbstractBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceEloquentRepository extends AbstractBaseRepository implements InvoiceRepositoryInterface
{
    protected $className = "InvoiceEloquentRepository";

    public function __construct(Invoice $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function countInvoiceByTime($dug_store_id)
    {
        LogEx::methodName($this->className, 'countInvoiceByTime');

        $data_reslust = array();

        $now = Carbon::now()->setTime(0, 0, 0, 0);
        $firstDateOfCurMonth = (clone $now)->firstOfMonth();
        $firstDateOfLastMonth = (clone $now)->addMonth(-1)->firstOfMonth();

        $query = DB::table("invoice")->where('drug_store_id', $dug_store_id);
        // Current/this month: First Date of Current Month <= create_at <= now
        $data_current_month = (clone $query)->whereDate('created_at', '>=', $firstDateOfCurMonth)
            ->whereDate('created_at', '<=', $now)
            ->get();

        // Last month: First Date of last Month <= create_at < First Date of current/this Month
        $data_last_month = (clone $query)->whereDate('created_at', '>=', $firstDateOfLastMonth)
            ->whereDate('created_at', '<', $firstDateOfCurMonth)
            ->get();

        if (!empty($data_current_month)) {
            $total_amount_current = 0;
            $total_amount_last = 0;
            foreach ($data_current_month as $value) {
                $total_amount_current = $total_amount_current + $value->amount;
            }
            foreach ($data_last_month as $item) {
                $total_amount_last = $total_amount_last + $item->amount;
            }

            $data_reslust['current_month'] = array(
                'invoice' => count($data_current_month),
                'amount' => $total_amount_current
            );
            $data_reslust['last_month'] = array(
                'invoice' => count($data_last_month),
                'amount' => $total_amount_last
            );
        } else {
            $total_amount_last = 0;
            $data_reslust['current_month'] = null;
            foreach ($data_last_month as $item) {
                $total_amount_last = $total_amount_last + $item->amount;
            }
            $data_reslust['last_month'] = array(
                'invoice' => count($data_last_month),
                'amount' => $total_amount_last
            );
        }
        return $data_reslust;
    }

    public function getInvoiceByMonth($drug_store_id)
    {
        LogEx::methodName($this->className, 'getInvoiceByMonth');

        $query = DB::table("invoice")->select(DB::raw("to_char(created_at, 'YYYYMM') as ym, coalesce(sum(pay_amount - vat_amount), 0) as amount, count(*) as invoice"))
            ->where('drug_store_id', $drug_store_id)
            ->where('invoice_type', "IV1")
            ->where('invoice.status', 'done')
            ->whereRaw("to_char(created_at, 'YYYYMM') in (to_char(current_date, 'YYYYMM'), to_char(current_date - interval '1 month', 'YYYYMM'))")
            ->groupBy(DB::raw("to_char(created_at, 'YYYYMM')"))
            ->orderByRaw("to_char(created_at, 'YYYYMM')");
        $query_result = $query->get()->toArray();
        $current_month = array();
        $current_month['invoice'] = 0;
        $current_month['amount'] = 0;
        $last_month = $current_month;
        $result_length = count($query_result);
        if ($result_length > 0) {
            $current_month['invoice'] = $query_result[$result_length - 1]->invoice;
            $current_month['amount'] = $query_result[$result_length - 1]->amount;
        }
        if ($result_length > 1) {
            $last_month['invoice'] = $query_result[0]->invoice;
            $last_month['amount'] = $query_result[0]->amount;
        }
        $data = array(
            'current_month' => $current_month,
            'last_month' => $last_month
        );
        return $data;
    }

    public function getInvoiceByDay($drug_store_id)
    {
        LogEx::methodName($this->className, 'getInvoiceByDay');

        $query = DB::table("invoice")->select(DB::raw("coalesce(sum(pay_amount - vat_amount), 0) as amount, count(*) as invoice"))
            ->where('drug_store_id', $drug_store_id)
            ->where('invoice_type', "IV1")
            ->where('invoice.status', 'done')
            ->whereRaw('created_at::date = current_date');
        return $query->get()->toArray()[0];
    }

    public function getInvoiceByWeek($dug_store_id, $startDate, $endDate)
    {
        LogEx::methodName($this->className, 'getInvoiceByWeek');

        $query = DB::table("invoice")->select(DB::raw("to_char(created_at::date, 'YYYY-MM-DD') as date, SUM(pay_amount - vat_amount) as total_amount"))
            ->where('drug_store_id', $dug_store_id)
            ->where('invoice_type', "IV1")
            ->where('invoice.status', 'done')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy(DB::raw('created_at::date'));
        return $query->get()->toArray();
    }

    public function getInvoiceByYear($year, $dug_store_id)
    {
        LogEx::methodName($this->className, 'getInvoiceByYear');

        $query = DB::table("invoice")->select(DB::raw("to_char(created_at, 'MM')::int as m, SUM(pay_amount - vat_amount) as total_amount"))
            ->where('drug_store_id', $dug_store_id)
            ->where('invoice_type', "IV1")
            ->where('invoice.status', 'done')
            ->whereRaw("to_char(created_at, 'YYYY') = '$year'")
            ->groupBy(DB::raw("to_char(created_at, 'MM')"))
            ->orderByRaw("to_char(created_at, 'MM')")
            ->get()->toArray();
        $data_result = array();
        for ($i = 1; $i <= 12; $i++) {
            // $firstDateOfMonth = Carbon::create($year, $i, 1);
            // $firstDateOfNextMonth = $firstDateOfMonth->addMonth(1);

            // $data_result['month_' . $i] = $query->whereDate('created_at', '>=', $firstDateOfMonth)
            //     ->whereDate('created_at', '<', $firstDateOfNextMonth)
            //     ->first();
            $data_result['month_' . $i] = array(
                'total_amount' => null
            );
        }

        for ($i = 0; $i < count($query); $i++) {
            $row = $query[$i];
            $data_result['month_' . $row->m] = array(
                'total_amount' => $row->total_amount
            );
        }
        return $data_result;
    }


    public function getInvoiceByCodition($drug_store_id, $form_date = null, $to_date = null, $invoice_code = null, $cutomer = null, $invoice_tye = null, $drug = null, $supplier_invoice_code = null, $addition = [])
    {
        LogEx::methodName($this->className, 'getInvoiceByCodition');

        $data = null;
        $query = Invoice::leftJoin('customer', 'customer.id', '=', 'invoice.customer_id')
            ->leftJoin('supplier', 'supplier.id', '=', 'invoice.customer_id')
            // ->leftJoin('warehouse_log', 'warehouse_log.invoice_id', '=', 'invoice.id')
            // ->leftJoin('users', 'users.id', '=', 'warehouse_log.user_id')
            ->select(
                'invoice.*',
                'customer.name as customer_name',
                'customer.number_phone as customer_phone',
                'supplier.name as supplier_name',
                'supplier.number_phone as supplier_phone',
                'supplier.email as supplier_email',
                'supplier.address as supplier_address',
                'supplier.website'
            // 'users.name as user_fullname',
            // 'users.username as user_username'
            );

        if (!empty($addition['original_invoice_code'])) {
            $query = $query->join('invoice as original_invoice', 'original_invoice.id', '=', 'invoice.refer_id')
                ->where('original_invoice.invoice_code', '=', $addition['original_invoice_code']);
        }

        if ($form_date != null && $to_date != null) {
            $query = $query->where('invoice.created_at', '>=', $form_date)
                ->where('invoice.created_at', '<=', $to_date);
        }

        if ($supplier_invoice_code != null) {
            $query = $query->where('invoice.supplier_invoice_code', 'like', $supplier_invoice_code . '%');
        }
        if ($invoice_tye != null) {
            $query = $query->where('invoice.invoice_type', $invoice_tye);
            if ($cutomer != null) {
                if ($invoice_tye == "IV2" || $invoice_tye == "IV4") {
                    $query = $query->where('supplier.name', 'ilike', $cutomer . '%');
                } elseif ($invoice_tye == "IV1" || $invoice_tye == "IV3") {
                    $query = $query->where('customer.name', 'like', $cutomer . '%');
                }
            }
        }

        if (!empty($addition['tax_number'])) {
            $tax_number = trim($addition['tax_number']);
            if ($invoice_tye == "IV2" || $invoice_tye == "IV4") {
                $query = $query->where('supplier.tax_number', 'like', '%' . $tax_number . '%');
            } elseif ($invoice_tye == "IV1" || $invoice_tye == "IV3") {
                $query = $query->where('customer.tax_number', 'like', '%' . $tax_number . '%');
            }
        }

        if (!empty($addition['number_phone'])) {
            $number_phone = trim($addition['number_phone']);
            if ($invoice_tye == "IV2" || $invoice_tye == "IV4") {
                $query = $query->where('supplier.number_phone', 'like', '%' . $number_phone . '%');
            } elseif ($invoice_tye == "IV1" || $invoice_tye == "IV3") {
                $query = $query->where('customer.number_phone', 'like', '%' . $number_phone . '%');
            }
        }

        if (!empty($addition['drug_name']) || !empty($addition['number'])) {
            $query = $query->join('invoice_detail', 'invoice_detail.invoice_id', '=', 'invoice.id');

            if (!empty($addition['drug_name'])) {
                $name = trim($addition['drug_name']);
                $query = $query->join('drug', 'drug.id', '=', 'invoice_detail.drug_id')
                    ->where('drug.name', 'ilike', '%' . $name . '%');
            }

            if (!empty($addition['number'])) {
                $number = trim($addition['number']);
                $query = $query->join('warehouse', 'warehouse.drug_id', '=', 'invoice_detail.drug_id')
                    ->where('warehouse.number', $number);
            }

            $query = $query->distinct('invoice.id');
        }

        $data = $query->where('invoice.drug_store_id', $drug_store_id)
            ->orderBy('invoice.id', 'DESC')
            ->paginate(10);
        return $data;
    }

    public function getListInvoiceIV1($drug_store_id, $searchData)
    {

        LogEx::methodName($this->className, 'getListInvoiceIV1');

        $query = DB::table("invoice")
            ->select(
                'invoice.*',
                'customer.name as customer_name',
                'customer.number_phone as customer_phone'
            )
            ->selectRaw("case when prescription.id is not null then '1' else '0' end as has_prescription")
            ->leftJoin('prescription', 'prescription.invoice_id', 'invoice.id');
        $paginate = isset($searchData['perPage']) ? $searchData['perPage'] : 10;

        // Add condition from form
        Utils::sqlWhere($query, 'invoice.invoice_code', '=', $searchData, 'invoice_code');
        Utils::sqlWhere($query, 'invoice.invoice_type', '=', $searchData, 'invoice_type');
        // Utils::sqlWhere($query, 'invoice.created_at', '>=', $searchData, 'from_date');
        // Utils::sqlWhere($query, 'invoice.created_at', '<=', $searchData, 'to_date');
        if (!empty($searchData['from_date'])) {
            $query = $query->whereRaw('invoice.receipt_date::date >= \'' . $searchData['from_date'] . '\'');
        }
        if (!empty($searchData['to_date'])) {
            $query = $query->whereRaw('invoice.receipt_date::date <= \'' . $searchData['to_date'] . '\'');
        }
        Utils::sqlWhere($query, 'invoice.supplier_invoice_code', '=', $searchData, 'supplier_invoice_code');

        if (!empty($searchData['customer_id'])) {
            $query = $query->join('customer', 'customer.id', '=', 'invoice.customer_id')
                ->where('customer.id', '=', $searchData['customer_id']);
        } else {
            $query = $query->leftJoin('customer', 'customer.id', '=', 'invoice.customer_id');
        }

        // Invoice_detail (drug_id, batch_number)
        if (!empty($searchData['drug_id']) || !empty($searchData['drug_name']) || !empty($searchData['batch_number'])) {
            $subInvoiceDetail = DB::table("invoice_detail")->select('invoice_id');

            if (!empty($searchData['drug_id']) || !empty($searchData['drug_name'])) {
                $subInvoiceDetail->join('drug', 'drug.id', '=', 'invoice_detail.drug_id')
                    ->where('drug.drug_store_id', $drug_store_id);

                Utils::sqlWhere($subInvoiceDetail, 'drug.drug_code', '=', $searchData, 'drug_id');
                Utils::sqlWhere($subInvoiceDetail, 'drug.name', 'ilike', $searchData, 'drug_name');
            }

            Utils::sqlWhere($subInvoiceDetail, 'number', '=', $searchData, 'batch_number');
            $subInvoiceDetail->groupBy('invoice_id');

            $query = $query->joinSub($subInvoiceDetail, 'subInvoiceDetail', function ($join) {
                $join->on('subInvoiceDetail.invoice_id', '=', 'invoice.id');
            });
        }

        // Execute SQL
        $data = $query->where('invoice.drug_store_id', $drug_store_id)
            ->orderBy('invoice.id', 'DESC')
            ->paginate($paginate);
        return $data;
    }

    public function getListInvoiceIV2($drug_store_id, $searchData)
    {

        LogEx::methodName($this->className, 'getListInvoiceIV2');

        $query = DB::table("invoice")
            ->select(
                'invoice.*',
                'supplier.name as supplier_name'
            );
        $paginate = isset($searchData['perPage']) ? $searchData['perPage'] : 10;

        // Add condition from form
        Utils::sqlWhere($query, 'invoice.invoice_code', '=', $searchData, 'invoice_code');
        Utils::sqlWhere($query, 'invoice.invoice_type', '=', $searchData, 'invoice_type');
        // Utils::sqlWhere($query, 'invoice.receipt_date', '>=', $searchData, 'from_date');
        // Utils::sqlWhere($query, 'invoice.receipt_date', '<=', $searchData, 'to_date');
        if (!empty($searchData['from_date'])) {
            $query = $query->whereRaw('invoice.created_at::date >= \'' . $searchData['from_date'] . '\'');
        }
        if (!empty($searchData['to_date'])) {
            $query = $query->whereRaw('invoice.created_at::date <= \'' . $searchData['to_date'] . '\'');
        }
        Utils::sqlWhere($query, 'invoice.supplier_invoice_code', '=', $searchData, 'supplier_invoice_code');

        if (!empty($searchData['supplier_id'])) {
            $query = $query->join('supplier', 'supplier.id', '=', 'invoice.customer_id')
                ->where('supplier.id', '=', $searchData['supplier_id']);
        } else {
            $query = $query->leftJoin('supplier', 'supplier.id', '=', 'invoice.customer_id');
        }

        // Invoice_detail (drug_id, batch_number)
        if (!empty($searchData['drug_id']) || !empty($searchData['drug_name']) || !empty($searchData['batch_number'])) {
            $subInvoiceDetail = DB::table("invoice_detail")->select('invoice_id');

            if (!empty($searchData['drug_id']) || !empty($searchData['drug_name'])) {
                $subInvoiceDetail->join('drug', 'drug.id', '=', 'invoice_detail.drug_id')
                    ->where('drug.drug_store_id', $drug_store_id);

                Utils::sqlWhere($subInvoiceDetail, 'drug.drug_code', '=', $searchData, 'drug_id');
                Utils::sqlWhere($subInvoiceDetail, 'drug.name', 'ilike', $searchData, 'drug_name');
            }

            Utils::sqlWhere($subInvoiceDetail, 'number', '=', $searchData, 'batch_number');
            $subInvoiceDetail->groupBy('invoice_id');

            $query = $query->joinSub($subInvoiceDetail, 'subInvoiceDetail', function ($join) {
                $join->on('subInvoiceDetail.invoice_id', '=', 'invoice.id');
            });
        }

        // Execute SQL
        $data = $query->where('invoice.drug_store_id', $drug_store_id)
            ->orderBy('invoice.id', 'DESC')
            ->paginate($paginate);
        return $data;
    }


    public function getListInvoiceIV7($drug_store_id, $searchData)
    {

        LogEx::methodName($this->className, 'getListInvoiceIV7');


        $query = DB::table("invoice")
            ->select(
                'invoice.*',
                'supplier.name as supplier_name',
                'refiv.invoice_code as ref_invoice_code'
            )
            ->leftJoin('invoice as refiv', 'refiv.id', 'invoice.refer_id');
        $paginate = isset($searchData['perPage']) ? $searchData['perPage'] : 10;

        // Add condition from form
        Utils::sqlWhere($query, 'invoice.invoice_code', '=', $searchData, 'invoice_code');
        Utils::sqlWhere($query, 'invoice.invoice_type', '=', $searchData, 'invoice_type');
        // Utils::sqlWhere($query, 'invoice.receipt_date', '>=', $searchData, 'from_date');
        // Utils::sqlWhere($query, 'invoice.receipt_date', '<=', $searchData, 'to_date');
        if (!empty($searchData['from_date'])) {
            $query = $query->whereRaw('invoice.created_at::date >= \'' . $searchData['from_date'] . '\'');
        }
        if (!empty($searchData['to_date'])) {
            $query = $query->whereRaw('invoice.created_at::date <= \'' . $searchData['to_date'] . '\'');
        }
        Utils::sqlWhere($query, 'invoice.supplier_invoice_code', '=', $searchData, 'supplier_invoice_code');

        if (!empty($searchData['supplier_id'])) {
            $query = $query->join('supplier', 'supplier.id', '=', 'invoice.customer_id')
                ->where('supplier.id', '=', $searchData['supplier_id']);
        } else {
            $query = $query->leftJoin('supplier', 'supplier.id', '=', 'invoice.customer_id');
        }

        // Invoice_detail (drug_id, batch_number)
        if (!empty($searchData['drug_id']) || !empty($searchData['drug_name']) || !empty($searchData['batch_number'])) {


            $subInvoiceDetail = DB::table("invoice_detail")->select('invoice_id');

            if (!empty($searchData['drug_id']) || !empty($searchData['drug_name'])) {
                $subInvoiceDetail->join('drug', 'drug.id', '=', 'invoice_detail.drug_id')
                    ->where('drug.drug_store_id', $drug_store_id);

                Utils::sqlWhere($subInvoiceDetail, 'drug.drug_code', '=', $searchData, 'drug_id');
                Utils::sqlWhere($subInvoiceDetail, 'drug.name', 'ilike', $searchData, 'drug_name');
            }

            Utils::sqlWhere($subInvoiceDetail, 'number', '=', $searchData, 'batch_number');
            $subInvoiceDetail->groupBy('invoice_id');

            $query = $query->joinSub($subInvoiceDetail, 'subInvoiceDetail', function ($join) {
                $join->on('subInvoiceDetail.invoice_id', '=', 'invoice.id');
            });
        }

        // Execute SQL
        $data = $query->where('invoice.drug_store_id', $drug_store_id)
            ->orderBy('invoice.id', 'DESC')
            ->paginate($paginate);
        return $data;
    }


    public function getDetailById($id)
    {
        LogEx::methodName($this->className, 'getDetailById');

        $data_result = array();

        $data_result['invoice'] = $this->getInvoiceData($id);

        if ($data_result['invoice'] && $data_result['invoice']->invoice_type == "IV2") {
            $invoice_refund = DB::table("invoice")
                ->select(
                    'invoice.id'
                )
                ->where('invoice.refer_id', $data_result['invoice']->id)
                ->get()->toArray();
            $data_result['refund_invoice'] = $invoice_refund;
        }
        if ($data_result['invoice'] && $data_result['invoice']->invoice_type == "IV4") {
            $original_invoice = DB::table("invoice")
                ->select(
                    'invoice.*'
                )
                ->where('invoice.id', $data_result['invoice']->refer_id)
                ->get()->toArray();
            $data_result['original_invoice'] = $original_invoice;
        }

        $clinic = DB::table("prescription")
            ->select(
                'prescription.*'
            )
            ->where('invoice_id', $id)
            ->get();

        $detail_result = null;
        $data_detail = DB::table("invoice_detail")
            ->select(
                'invoice_detail.*',
                'unit.name as unit_name',
                'drug.name as drug_name',
                'drug.image as image',
                'drug.drug_code',
                'drug.image'
            )
            ->join('unit', 'unit.id', 'invoice_detail.unit_id')
            ->join('drug', 'drug.id', 'invoice_detail.drug_id')
            ->where('invoice_id', $id)
            ->get();
        if ($data_detail) {
            foreach ($data_detail as $item) {
                $item = json_decode(json_encode($item), true);
                $item['current_cost'] = $item['cost'];
                $detail_result[] = $item;
                // $tmp = DB::table("warehouse")
                //     ->select(
                //         'warehouse.*'
                //     )
                //     ->where('drug_id', $item['drug_id'])
                //     ->where('number', $item['number'])
                //     ->where('unit_id', $item['unit_id'])
                //     ->first();
                // if (!empty($tmp)) {
                //     $item['current_cost'] = $tmp->current_cost;
                //     $detail_result[] = $item;
                // } else {
                //     $tmp = DB::table("warehouse")
                //         ->select(
                //             'warehouse.*'
                //         )
                //         ->where('drug_id', $item['drug_id'])
                //         ->where('number', $item['number'])
                //         //                        ->where('unit_id', $item['unit_id'])
                //         ->first();

                //     $item['current_cost'] = $tmp->current_cost;
                //     $detail_result[] = $item;
                // }
            }
        }

        if (!empty($clinic)) {
            $data_result['clinic'] = $clinic;
        }
        $data_result['invoice_detail'] = $detail_result;
        return $data_result;
    }

    public function getIV7DetailById($id, $drug_store_id)
    {
        LogEx::methodName($this->className, 'getIV7DetailById');

        $data_result = array();

        $data_result = [
            'invoice' => $this->getIV7InvoiceData($id, $drug_store_id),
            'invoice_detail' => $this->getIV7InvoiceDetail($id)
        ];

        return $data_result;
    }

    private function getIV7InvoiceDetail($id)
    {
        LogEx::methodName($this->className, 'getIV7InvoiceDetail');

        $invoiceDetailData = DB::table("invoice_detail")
            ->select(
                'invoice_detail.*',
                'unit.id as unit_id',
                'unit.name as unit_name',
                'drug.name as drug_name',
                'drug.image as image',
                'drug.drug_code',
                'drug.image'
            )
            ->join('unit', 'unit.id', 'invoice_detail.unit_id')
            ->join('drug', 'drug.id', 'invoice_detail.drug_id')
            ->where('invoice_id', $id)
            ->get();

        return $invoiceDetailData;
    }

    private function getIV7InvoiceData($id, $drug_store_id)
    {
        LogEx::methodName($this->className, 'getIV7InvoiceData');

        $invoiceData = DB::table("invoice")
            ->select(
                'invoice.*',
                'supplier.name as supplier_name',
                'supplier.number_phone as supplier_phone',
                // 'supplier.email as supplier_email',
                'supplier.address as supplier_address',
                // 'supplier.tax_number as supplier_tax_number',
                // 'supplier.website',
                'users.name as user_fullname',
                'users.username as user_username'
            )
            ->leftJoin('supplier', 'supplier.id', 'invoice.customer_id')
            ->leftJoin('users', 'users.id', 'invoice.created_by')
            ->where('invoice.id', $id)
            ->where('invoice.drug_store_id', $drug_store_id)
            ->get();

        return $invoiceData;
    }


    private function getInvoiceData($id)
    {
        LogEx::methodName($this->className, 'getInvoiceData');

        $data_invoice = DB::table("invoice")
            ->select(
                'invoice.*',
                'customer.name as customer_name',
                'customer.address',
                'customer.email',
                'customer.number_phone',
                'supplier.name as supplier_name',
                'supplier.number_phone as supplier_phone',
                'supplier.email as supplier_email',
                'supplier.address as supplier_address',
                'supplier.tax_number as supplier_tax_number',
                'supplier.website',
                'users.name as user_fullname',
                'users.username as user_username',
                'refiv.invoice_code as ref_invoice_code'
            )
            ->leftJoin('invoice as refiv', 'refiv.id', 'invoice.refer_id')
            ->leftJoin('customer', 'customer.id', 'invoice.customer_id')
            ->leftJoin('supplier', 'supplier.id', 'invoice.customer_id')
            ->leftJoin('warehouse_log', 'warehouse_log.invoice_id', '=', 'invoice.id')
            ->leftJoin('users', 'users.id', '=', 'invoice.created_by')
            ->where('invoice.id', $id)
            ->get()->toArray();

        if (count($data_invoice) > 0) {
            $invoice = $data_invoice[0];
            if ($invoice->invoice_type == "IV2" || $invoice->invoice_type == "IV4") {
                unset($invoice->customer_name);
                unset($invoice->address);
                unset($invoice->email);
                unset($invoice->number_phone);
            } else {
                unset($invoice->supplier_name);
                unset($invoice->supplier_phone);
                unset($invoice->supplier_email);
                unset($invoice->supplier_address);
                unset($invoice->supplier_tax_number);
            }
            return $invoice;
        }

        return null;
    }

    public function getHistory($list_drug, $drug_name, $from_date = null, $to_date = null)
    {
        LogEx::methodName($this->className, 'getHistory');

        $data_result = array();
        foreach ($list_drug as $value) {
            if (!empty($from_date) && !empty($to_date)) {
                $data = DB::table("invoice_detail")
                    ->select(
                        'invoice_detail.*',
                        'invoice.invoice_code',
                        'invoice.id as invoice_id',
                        'drug.name as drug_name'
                    )
                    ->join('invoice', 'invoice.id', 'invoice_detail.invoice_id')
                    ->join('drug', 'invoice_detail.drug_id', 'drug.id')
                    ->whereDate('invoice_detail.created_at', '>=', $from_date)
                    ->whereDate('invoice_detail.created_at', '<=', $to_date)
                    ->where('invoice_detail.drug_id', $value)
                    ->where('drug.name', 'ilike', $drug_name . '%')
                    ->orderBy('invoice_detail.id', 'DESC')
                    ->get()->toArray();
                if (!empty($data)) {
                    $data_result[] = $data;
                }
            } else {
                $data = DB::table("invoice_detail")
                    ->select(
                        'invoice_detail.*',
                        'invoice.invoice_code',
                        'invoice.id as invoice_id',
                        'drug.name as drug_name'
                    )
                    ->join('invoice', 'invoice.id', 'invoice_detail.invoice_id')
                    ->join('drug', 'invoice_detail.drug_id', 'drug.id')
                    ->where('invoice_detail.drug_id', $value)
                    ->where('drug.name', 'ilike', $drug_name . '%')
                    ->orderBy('invoice_detail.id', 'DESC')
                    ->get()->toArray();
                if (!empty($data)) {
                    $data_result[] = $data;
                }
            }
        }
        return $data_result;
    }

    public function cancelInvoice($id)
    {
        LogEx::methodName($this->className, 'cancelInvoice');

        $data = $this->updateOneById($id, ['status' => 'cancel']);
        return $data;
    }

    public function getInvoiceReturn($id)
    {
        LogEx::methodName($this->className, 'getInvoiceReturn');

        $data_result = array();
        $data = $this->findManyBy('refer_id', $id)->toArray();
        if (empty($data)) {
            return null;
        } else {
            foreach ($data as $value) {
                $data_result[] = $this->getDetailById($value['id']);
            }
            return $data_result;
        }
    }

    public function getDrugRemain($id)
    {
        LogEx::methodName($this->className, 'getDrugRemain');

        $data_result = array();

        $data_result['invoice'] = $this->getInvoiceData($id);

        $detail_result = null;
        $data_detail = DB::table("invoice_detail")
            ->select(
                'invoice_detail.*',
                'unit.name as unit_name',
                'drug.name as drug_name',
                'drug.image as image',
                'drug.drug_code',
                'drug.image'
            )
            ->join('unit', 'unit.id', 'invoice_detail.unit_id')
            ->join('drug', 'drug.id', 'invoice_detail.drug_id')
            ->where('invoice_id', $id)
            ->get();
        $datas_refund = DB::table("invoice_return")->where('refer_id', $id)->get()->toArray();

        if ($data_detail) {
            foreach ($data_detail as $item) {
                $item = json_decode(json_encode($item), true);

                foreach ($datas_refund as $data_refund) {
                    if (
                        $item['drug_id'] == $data_refund->drug_id &&
                        $item['number'] == $data_refund->number &&
                        $item['unit_id'] == $data_refund->unit_id
                    ) {
                        $item['quantity'] = $item['quantity'] - $data_refund->return_quantity;
                    }
                }
                if ($item['quantity'] == 0) {
                    continue;
                }

                $tmp = DB::table("warehouse")
                    ->select(
                        'warehouse.*'
                    )
                    ->where('drug_id', $item['drug_id'])
                    ->where('number', $item['number'])
                    ->where('unit_id', $item['unit_id'])
                    ->first();

                if (!empty($tmp)) {
                    $item['current_cost'] = $tmp->current_cost;
                    $detail_result[] = $item;
                } else {
                    $tmp = DB::table("warehouse")
                        ->select(
                            'warehouse.*'
                        )
                        ->where('drug_id', $item['drug_id'])
                        ->where('number', $item['number'])
                        //                        ->where('unit_id', $item['unit_id'])
                        ->first();

                    $item['current_cost'] = $tmp->current_cost;
                    $detail_result[] = $item;
                }
            }
        }

        $data_result['invoice_detail'] = $detail_result;
        return $data_result;
    }

    public function getListByCustomer($customer_id, $form_date = null, $to_date = null)
    {
        LogEx::methodName($this->className, 'getListByCustomer');

        if ($to_date == null || $form_date == null) {
            $data = DB::table("invoice")
                ->select(
                    'invoice.*',
                    'vouchers.id as id_vouchers',
                    'vouchers.code'
                )
                ->leftJoin('vouchers', 'vouchers.invoice_id', 'invoice.id')
                ->where('invoice.customer_id', $customer_id)
                ->whereRaw("invoice.invoice_type in ('IV1', 'IV3')")
                ->orderBy('invoice.id', 'DESC')
                ->get();
        } else {
            $data = DB::table("invoice")
                ->select(
                    'invoice.*',
                    'vouchers.id as vouchers_id',
                    'vouchers.code'
                )
                ->leftJoin('vouchers', 'vouchers.invoice_id', 'invoice.id')
                ->where('invoice.customer_id', $customer_id)
                ->whereDate('invoice.updated_at', '>=', $form_date)
                ->whereDate('invoice.updated_at', '<=', $to_date)
                ->orderBy('invoice.id', 'DESC')
                ->get();
        }
        return $data;
    }

    public function getListDetailByCustomer($customer_id, $form_date = null, $to_date = null)
    {
        LogEx::methodName($this->className, 'getListDetailByCustomer');

        $data = DB::table("invoice")
            ->select(
                'invoice.id as invoice_id',
                'invoice.invoice_code',
                'invoice.created_at as buy_date',
                'drug.drug_code',
                'drug.name as drug_name',
                'unit.name as unit_name',
                'invoice_detail.*'
            )
            ->join('invoice_detail', 'invoice_detail.invoice_id', 'invoice.id')
            ->join('drug', 'drug.id', 'invoice_detail.drug_id')
            ->join('unit', 'unit.id', 'invoice_detail.unit_id')
            ->where('invoice.customer_id', $customer_id)
            ->whereRaw("invoice.invoice_type = 'IV1'");
        if (isset($form_date)) {
            $data = $data->whereDate('invoice.updated_at', '>=', $form_date);
        }
        if (isset($to_date)) {
            $data = $data->whereDate('invoice.updated_at', '>=', $to_date);
        }
        return $data
            ->orderBy('invoice.created_at', 'DESC')
            ->orderBy('invoice.id', 'DESC')
            ->orderBy('invoice_detail.id', 'ASC')
            ->get();
    }

    public function getListBySupplier($supplier_id)
    {
        LogEx::methodName($this->className, 'getListBySupplier');

        $data = DB::table("invoice")
            ->select(
                'invoice.*',
                'vouchers.id as id_vouchers',
                'vouchers.code'
            )
            ->leftJoin('vouchers', 'vouchers.invoice_id', 'invoice.id')
            ->where('invoice.customer_id', $supplier_id)
            ->whereRaw("invoice.invoice_type in ('IV2', 'IV7')")
            ->orderBy('invoice.id', 'DESC')
            ->get();
        return $data;
    }

    public function getDetailForDose($id, $drug_store_id)
    {
        LogEx::methodName($this->className, 'getDetailForDose');

        $data_invoice = DB::table("invoice")
            ->select(
                'invoice.*',
                'customer.name as customer_name',
                'customer.address',
                'customer.email',
                'customer.number_phone'
            )
            ->leftJoin('customer', 'customer.id', 'invoice.customer_id')
            ->where('invoice.id', $id)
            ->where('invoice.drug_store_id', $drug_store_id)
            ->get();
        return $data_invoice;
    }

    public function getDashboardActivities($drugStoreId)
    {
        LogEx::methodName($this->className, 'getDashboardActivities');

        $data = DB::select('select * from f_dashboard_get_activities(?)', [$drugStoreId]);
        return $data;
    }

    public function getDashboardStatistic($drugStoreId)
    {
        LogEx::methodName($this->className, 'getDashboardStatistic');

        $data = DB::select('select * from f_dashboard_get_statistic(?)', [$drugStoreId]);
        return $data;
    }

    public function countInvoiceByStoreId($storeId)
    {
        $count = DB::select("select count(*) from invoice where drug_store_id = " . $storeId);
        return $count[0]->count;
    }

    public function filter($invoiceFilterInput, $drugStoreId, $limit = null)
    {
        LogEx::methodName($this->className, 'filter');
        $limit = $limit > 10 ? $limit : ($invoiceFilterInput["per_page"] ?? 10);
        $searchText = $invoiceFilterInput["query"] ?? null;
        $invoiceType = $invoiceFilterInput["invoice_type"] ?? null;
        $sale = $invoiceFilterInput["sale"] ?? null;
        $createdBy = $invoiceFilterInput["created_by"] ?? null;
        $customer = $invoiceFilterInput["customer"] ?? null;//customer_id
        $supplierID = $invoiceFilterInput["supplier_id"] ?? null;
        $fromDate = $invoiceFilterInput["from_date"] ?? null;
        $toDate = $invoiceFilterInput["to_date"] ?? null;
        $status = $invoiceFilterInput["status"] ?? null;
        $shippingStatus = $invoiceFilterInput["shipping_status"] ?? null;
        $paymentStatus = $invoiceFilterInput["payment_status"] ?? null;
        $isDebt = $invoiceFilterInput["is_debt"] ?? null;
        $url = $invoiceFilterInput["url"] ?? null;
        $type_export = $invoiceFilterInput["type_export"] ?? null;

        $queryDB = '1 = 1';

        $queryDB = $queryDB . " AND invoice.invoice_type = '" . $invoiceType . "'";

        $queryFultextDB = '1 = 1 ';

        if (!empty($searchText)) {
            $name = trim($searchText);
            $queryFultextDB = $queryFultextDB . " AND (invoice.invoice_code ILIKE '%" . $searchText . "'";
            $queryFultextDB = $queryFultextDB . " OR exists (select 1 from invoice_detail id join drug on drug.id = id.drug_id where id.invoice_id = invoice.id";
            $queryFultextDB = $queryFultextDB . " AND (invoice.warehouse_action_id ~* '" . $name
                . "' or users.name  ~* '" . $name
                . "' or supplier.name  ~* '" . $name
                . "' or supplier.number_phone  ~* '" . $name
                . "' or supplier.address  ~* '" . $name
                ."')))";
        }

        if (isset($sale)) {
            $queryDB = $queryDB . " AND invoice.sale_id = " . $sale;
        }

        if (isset($createdBy)) {
            $queryDB = $queryDB . " AND invoice.created_by = " . $createdBy;
        }

        if (isset($customer)) {
            if ($customer == 0) {
                $queryDB = $queryDB . " AND invoice.customer_id is null";
            } else {
                $queryDB = $queryDB . " AND invoice.customer_id = " . $customer;
            }
        }

        if (isset($supplierID)) {
            $queryDB = $queryDB . " AND supplier.id = " . $supplierID;
        }

        if (isset($fromDate)) {
            if (in_array($invoiceType, array("IV2", "IV7"))) {
                $queryDB = $queryDB . " AND invoice.created_at >= '" . $fromDate . " 00:00:00.000000'";
            } else {
                $queryDB = $queryDB . " AND invoice.receipt_date >= '" . $fromDate . " 00:00:00.000000'";
            }
        }

        if (isset($toDate)) {
            if (in_array($invoiceType, array("IV2", "IV7"))) {
                $queryDB = $queryDB . " AND invoice.created_at <= '" . $toDate . " 23:59:59.999999'";
            } else {
                $queryDB = $queryDB . " AND invoice.receipt_date <= '" . $toDate . " 23:59:59.999999'";
            }
        }

        if (isset($status) && in_array($status, array('done', 'cancel', 'processing', 'pending', 'temp'))) {
            $queryDB = $queryDB . " AND invoice.status = '" . $status . "'";
        } else {
            $queryDB = $queryDB . " AND invoice.status in ('done', 'cancel', 'processing', 'pending')";
        }

        if (isset($shippingStatus)) {
            $queryDB = $queryDB . " AND invoice.shipping_status ILIKE '%" . $shippingStatus . "'";
        }

        if (isset($paymentStatus)) {
            $queryDB = $queryDB . " AND invoice.payment_status ILIKE '%" . $shippingStatus . "'";
        }

        if (isset($isDebt) && $isDebt == true) {
            $queryDB = $queryDB .
                " AND( invoice.amount + invoice.vat_amount - invoice.discount - (case when invoice.pay_amount is null then 0 else invoice.pay_amount end) > 0 )";
        }

        $invoices = DB::table('invoice')
            ->select(
                'invoice.id',
                'invoice.drug_store_id',
                'invoice.supplier_invoice_code',
                'invoice.invoice_code',
                'invoice.invoice_type',
                'invoice.warehouse_action_id',
                'invoice.refer_id',
                'invoice.customer_id',
                'invoice.amount',
                'invoice.vat_amount',
                'invoice.pay_amount',
                'invoice.discount',
                'invoice.discount_promotion',
                'invoice.created_by',
                'users.name as created_name',
                'invoice.description',
                'invoice.status',
                'invoice.payment_status',
                'invoice.image',
                'invoice.receipt_date',
                'invoice.created_at',
                'invoice.updated_at',
                'invoice.method',
                'invoice.payment_method',
                'invoice.shipping_status',
                'customer.name as customer_name',
                'customer.number_phone as customer_phone',
                'customer.address as customer_address',
                DB::raw('(select users.name from users where users.id = invoice.sale_id) as sale_name'),
                DB::raw('CASE WHEN invoice.is_order = true THEN drugstores.name ELSE supplier.name END as supplier_name'),
                DB::raw('CASE WHEN invoice.is_order = true THEN drugstores.phone ELSE supplier.number_phone END as supplier_phone'),
                DB::raw('CASE WHEN invoice.is_order = true THEN drugstores.address ELSE supplier.address END as supplier_address'),
                DB::raw("case when invoice.invoice_type in ('IV2', 'IV7') then supplier.tax_number
                        else customer.tax_number end  as tax_number"),
                //DB::raw("case when ret.id is not null then 1 else 0 end as has_return_iv1"),
                //DB::raw("case when invoice.invoice_type in ('IV3') then 1 else 0 end as has_return_iv3"),
                DB::raw("coalesce(
                    (case when ret.id is not null then 1 else null end),
                    (case when invoice.invoice_type in ('IV3') then 1 else null end)
                ) as has_return"),
                DB::raw("case when prescription.id is not null then 1 else 0 end as has_prescription"),
                DB::raw("case when comb.id is not null then 1 else 0 end as has_combo"),
                DB::raw("ref.invoice_code as ref_invoice_code"),
                'invoice.is_order',
                't_order.order_code',
                'invoice.is_import'
            )
            ->join("users", "users.id", "invoice.created_by")
            ->leftJoin("customer", function ($join) use ($invoiceType) {
                if (in_array($invoiceType, array("IV1", "IV3"))) {
                    $join->on('customer.id', '=', 'invoice.customer_id');
                } else {
                    $join->where("customer.id", "=", 0);
                }
            })
            ->leftJoin("supplier", function ($join) use ($invoiceType) {
                if (!in_array($invoiceType, array("IV1", "IV3"))) {
                    $join->on('supplier.id', '=', 'invoice.customer_id');
                } else {
                    $join->where("supplier.id", "=", 0);
                }
            })
            ->leftJoin("drugstores", "drugstores.id", "invoice.customer_id")
            ->leftJoin("prescription", function ($join) use ($invoiceType) {
                if ($invoiceType === 'IV1') {
                    $join->on('prescription.invoice_id', '=', 'invoice.id');
                } else {
                    $join->where("prescription.invoice_id", "=", 0);
                }
            })
            ->leftJoin("invoice as ret", function ($join) use ($invoiceType) {
                if (in_array($invoiceType, array("IV1", "IV2"))) {
                        $join->on('ret.refer_id', '=', 'invoice.id')
                            ->whereIn('ret.invoice_type', ["IV3", "IV4"]);
                } else {
                    $join->where("ret.refer_id", "=", 0);
                }
            })
            ->leftJoin("invoice as ref", 'ref.id', '=', 'invoice.refer_id')
            ->leftJoin("invoice_detail as comb", function ($join) use ($invoiceType) {
                if ($invoiceType === 'IV1') {
                    $join->on('comb.invoice_id', '=', 'invoice.id')
                        ->whereNotIn('comb.combo_name', ["", "Đơn thuốc"]);
                } else {
                    $join->where("comb.invoice_id", "=", 0);
                }
            })->leftJoin("t_order", function ($join) {
                $join->on("t_order.in_invoice_id", "=", "invoice.id")->whereOr("t_order.out_invoice_id", "=", "invoice.id");
            })
            ->where("invoice.drug_store_id", "=", $drugStoreId)
            ->whereRaw($queryDB)
            ->whereRaw($queryFultextDB)
            ->orderByDesc("invoice.id")
            ->distinct();

        if ($type_export) {
            return $invoices->paginate($limit);
        }

        $queries = $invoices;
        $query_sum = $invoices
            ->get()
            ->toArray();

        $data = Utils::executeRawQueryV3(
            $queries,
            $url,
            $invoiceFilterInput
        );

        $sum_data = [
            'amount' => array_sum(array_column($query_sum, 'amount')),
            'debt' => array_sum(array_column($query_sum, 'debt')),
            'discount' => array_sum(array_column($query_sum, 'discount')),
            'pay_amount' => array_sum(array_column($query_sum, 'pay_amount')),
            'vat_amount' => array_sum(array_column($query_sum, 'vat_amount'))
        ];

        return Utils::getSumDataV3($data, $invoiceFilterInput, $sum_data);
    }
}
