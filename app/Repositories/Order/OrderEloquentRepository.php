<?php

namespace App\Repositories\Order;

use App\Models\Order;
use App\Repositories\AbstractBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use app\libextension\logex;

class OrderEloquentRepository extends AbstractBaseRepository implements OrderRepositoryInterface
{
    protected $className = "OrderEloquentRepository";

    public function __construct(Order $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getOrderByCodition($drug_store_id, $form_date = null, $to_date = null, $order_code = null, $addition = [])
    {
        LogEx::methodName($this->className, 'getOrderByCodition');

        $data = null;
        $query = Order::select(
            'order.*'
        );

        if (!empty($addition['tax_number']) || !empty($addition['number_phone'])) {
            $query = $query->leftJoin('supplier', 'supplier.id', '=', 'order.supplier_id');

            if (!empty($addition['tax_number'])) {
                $tax_number = trim($addition['tax_number']);
                $query = $query->where('supplier.tax_number', 'like', '%' . $tax_number . '%');
            }

            if (!empty($addition['number_phone'])) {
                $number_phone = trim($addition['number_phone']);
                $query = $query->where('supplier.number_phone', 'like', '%' . $number_phone . '%');
            }
        }

        if ($form_date != null && $to_date != null) {
            $query = $query->where('order.created_at', '>=', $form_date)
                ->where('order.created_at', '<=', $to_date);
        }

        if (!empty($order_code)) {
            $query = $query->where('order.order_code', 'like', '%' .  $order_code . '%');
        }

        if (!empty($addition['drug_name'])) {
            $query = $query->join('order_detail', 'order_detail.order_id', '=', 'order.id');

            if (!empty($addition['drug_name'])) {
                $name = trim($addition['drug_name']);
                $query = $query->join('drug', 'drug.id', '=', 'order_detail.drug_id')
                    ->where('drug.name', 'ilike', '%' . $name . '%');
            }

            $query = $query->distinct('order.id');
        }

        $data = $query->where('order.drug_store_id', $drug_store_id)
            ->orderBy('order.id', 'DESC')
            ->paginate(10);
        return $data;
    }

    public function getDetailById($id)
    {
        LogEx::methodName($this->className, 'getDetailById');

        $data_result = array();
        $data_order = DB::table("order")
            ->select(
                'order.*',
                'supplier.name as supplier_name'
            )
            ->leftjoin('supplier', 'supplier.id', 'order.supplier_id')
            ->where('order.id', $id)
            ->get()->toArray();

        if (count($data_order) == 0) {
            return $data_result;
        }

        $data_result['order'] = $data_order[0];

        $data_detail = DB::table("order_detail")
            ->select(
                'order_detail.*',
                'unit.name as unit_name',
                'drug.name as drug_name',
                'drug.image as image',
                'drug.drug_code',
                'drug.image'
            )
            ->join('unit', 'unit.id', 'order_detail.unit_id')
            ->join('drug', 'drug.id', 'order_detail.drug_id')
            ->where('order_id', $id)
            ->get();

        // Case admin confirmed => amount, vat_amount, pay_amount write again by admin => need calc again
        if ($data_result['order']->status == 'done' || $data_result['order']->status == 'confirm') {
            $amount = 0;
            foreach ($data_detail as $detail) {
                $amount += $detail->quantity * $detail->cost;
            }
            $data_result['order']->amount = $amount;
            $data_result['order']->vat_amount = 0;
            $data_result['order']->pay_amount = $amount;
        }
        $data_result['order_detail'] = $data_detail;

        return $data_result;
    }

    public function getDetailByIdFromAdmin($id)
    {
        LogEx::methodName($this->className, 'getDetailByIdFromAdmin');

        $data_result = array();
        $data_order = DB::table("order")
            ->select('order.*')
            ->where('order.id', $id)
            ->get()->toArray();

        if (count($data_order) == 0) {
            return $data_result;
        }

        $data_result['order'] = $data_order[0];

        $data_detail = DB::table("order_detail_admin")
            ->select(
                'order_detail_admin.*',
                'unit.name as unit_name',
                'drug.name as drug_name',
                'drug.image as image',
                'drug.drug_code',
                'drug.image'
            )
            ->join('unit', 'unit.id', 'order_detail_admin.unit_id')
            ->join('drug', 'drug.id', 'order_detail_admin.drug_id')
            ->where('order_id', $id)
            ->get();
        $data_result['order_detail_admin'] = $data_detail;
        return $data_result;
    }

    public function cancelOrder($id)
    {
        LogEx::methodName($this->className, 'cancelOrder');

        $data = $this->updateOneById($id, ['status' => 'cancel']);
        return $data;
    }

    public function getOrdersForAdmin()
    {
        LogEx::methodName($this->className, 'getOrdersForAdmin');

        return DB::table("order")
            ->select(
                'order.*',
                'drugstores.name as drugstore_name',
                'drugstores.address as drugstore_address',
                'drugstores.phone as drugstore_phone',
                'supplier.name as supplier_name'
            )
            ->join('drugstores', 'drugstores.id', 'order.drug_store_id')
            ->leftJoin('supplier', 'supplier.id', 'order.supplier_id')
            ->where('order.status', 'ordering')
            ->orderBy('order.created_at', 'desc')
            ->get();
    }

    public function getOrdersReturned()
    {
        LogEx::methodName($this->className, 'getOrdersReturned');

        return DB::table("order")
            ->select(
                'order.*',
                'drugstores.name as drugstore_name',
                'drugstores.address as drugstore_address',
                'drugstores.phone as drugstore_phone',
                'supplier.name as supplier_name'
            )
            ->join('drugstores', 'drugstores.id', 'order.drug_store_id')
            ->leftJoin('supplier', 'supplier.id', 'order.supplier_id')
            ->whereIn('order.status', ['done', 'confirm'])
            ->orderBy('order.return_date', 'desc')
            ->get();
    }
}
