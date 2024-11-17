<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 10:43 AM
 */

namespace App\Repositories\Vouchers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Vouchers;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class VouchersEloquentRepository extends AbstractBaseRepository implements VouchersRepositoryInterface
{
    protected $className = "VouchersEloquentRepository";

    public function __construct(Vouchers $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


    public function getListVouchersByCondition($drug_store_id, $form_date = null, $to_date = null, $type = null, $invoice_type = null, $amount = null)
    {
        LogEx::methodName($this->className, 'getListVouchersByCondition');

        $condition = '';
        if ($amount != null) {
            $amount = intval($amount);
        }
        $condition = $condition . '' . 'vouchers.created_at >= ' . "'" . $form_date . "'" . ' AND ' . 'vouchers.created_at <= ' . "'" . $to_date . "'";
        if ($type != null) {
            $condition = $condition . ' AND vouchers.type = ' . "'" . $type . "'";
        }
        if ($invoice_type != null) {
            $condition = $condition . ' AND vouchers.invoice_type = ' . "'" . $invoice_type . "'";
        }
        if ($amount != null) {
            if ($amount == 1) {
                $condition = $condition . ' AND vouchers.amount <= 1000000';
            } elseif ($amount == 2) {
                $condition = $condition . ' AND vouchers.amount > 1000000 AND vouchers.amount <= 5000000';
            } elseif ($amount == 3) {
                $condition = $condition . ' AND vouchers.amount > 5000000 AND vouchers.amount <= 10000000';
            } elseif ($amount == 4) {
                $condition = $condition . ' AND vouchers.amount > 10000000 AND vouchers.amount <= 20000000';
            } elseif ($amount == 5) {
                $condition = $condition . ' AND vouchers.amount > 20000000';
            }
        }

        if ($condition == '') {
            $data = DB::table("vouchers")
                ->select(
                    'vouchers.*',
                    'users.name as user_name'
                )
                ->Join('users', 'users.id', 'vouchers.user_id')
                ->where('vouchers.drug_store_id', $drug_store_id)
                ->orderBy('vouchers.id', 'DESC')
                ->paginate(10);
        } else {
            $data = DB::table("vouchers")
                ->select(
                    'vouchers.*',
                    'users.name as user_name'
                )
                ->Join('users', 'users.id', 'vouchers.user_id')
                ->whereRaw($condition)
                ->where('vouchers.drug_store_id', $drug_store_id)
                ->orderBy('vouchers.id', 'DESC')
                ->paginate(10);
        }
        return $data;
    }

    public function getListSupplier($id, $form_date = null, $to_date = null, $type = null)
    {
        LogEx::methodName($this->className, 'getListSupplier');

        $query = DB::table("vouchers")
                ->select('vouchers.*');

        if ($type == null) {
            $query->where('supplier_id', $id);
        } else {
            $query->where('customer_id', $id);
        }

        if ($form_date <> null) {
            $query->wheredate('created_at', '>=', $form_date);
        }

        if ($to_date <> null) {
            $query->wheredate('created_at', '<=', $to_date);
        }

        return $query->get();;
    }


    public function getVoucherByMonth($dug_store_id)
    {
        LogEx::methodName($this->className, 'getVoucherByMonth');

        $data_reslust = array();

        // First date of current month
        $now = Carbon::now()->setTime(0, 0, 0, 0);;
        $firstDateOfCurMonth = (clone $now)->firstOfMonth();
        $firstDateOfLastMonth = (clone $now)->addMonth(-1)->firstOfMonth();

        $query = DB::table("vouchers")
                    ->where('drug_store_id', $dug_store_id)
                    ->where('type', 1);

        // Current/this month: First Date of Current Month <= create_at <= now
        $data_current_month = (clone $query)->whereDate('created_at', '>=', $firstDateOfCurMonth)
                                    ->whereDate('created_at', '<=', $now)
                                    ->get();

        // Last month: First Date of last Month <= create_at < First Date of current/this Month
        $data_last_month =(clone $query)->whereDate('created_at', '>=', $firstDateOfLastMonth)
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


    public function getVouchersByYear($year, $dug_store_id)
    {
        LogEx::methodName($this->className, 'getVouchersByYear');

        $data_result = array();
        $query = DB::table("vouchers")
                    ->select(DB::raw('SUM(amount) as total_amount'))
                    ->where('drug_store_id', $dug_store_id)
                    ->where('type', 1);

        for ($i = 1; $i <= 12; $i++) {
            $firstDateOfMonth = Carbon::create($year, $i, 1)->setTime(0, 0, 0, 0);
            $firstDateOfNextMonth = (clone $firstDateOfMonth)->addMonth(1);

            $data_result['month_' . $i] = (clone $query)->whereDate('created_at', '>=', $firstDateOfMonth)
                                                ->whereDate('created_at', '<', $firstDateOfNextMonth)
                                                ->first();
        }
        return $data_result;

    }

    public function getDetail($id, $type = null)
    {
        LogEx::methodName($this->className, 'getDetail');

        if ($type == null) {
            $data = DB::table("vouchers")
                ->select(
                    'vouchers.*',
                    'customer.name as customer_name'
                )
                ->leftjoin('customer', 'customer.id', 'vouchers.customer_id')
                ->where('vouchers.id', $id)
                ->first();
        } else {
            $data = DB::table("vouchers")
                ->select(
                    'vouchers.*',
                    'supplier.name as supplier_name'
                )
                ->leftjoin('supplier', 'supplier.id', 'vouchers.supplier_id')
                ->where('vouchers.id', $id)
                ->first();
        }
        return $data;
    }


}
