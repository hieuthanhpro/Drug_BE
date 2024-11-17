<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\TOrder;
use App\Models\Warehouse;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\OrderDetail\OrderDetailRepositoryInterface;
use App\Repositories\OrderDetailAdmin\OrderDetailAdminRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CommonConstant;
use Illuminate\Support\Facades\Log;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;

/**
 * Class OrderService
 * @package App\Services
 */
class OrderService
{
    protected $className = "OrderService";

    protected $order;
    protected $order_detail;
    protected $order_detail_admin;


    public function __construct(
        OrderRepositoryInterface $order,
        OrderDetailRepositoryInterface $order_detail,
        OrderDetailAdminRepositoryInterface $order_detail_admin
    ) {
        LogEx::constructName($this->className, '__construct');

        $this->order = $order;
        $this->order_detail = $order_detail;
        $this->order_detail_admin = $order_detail_admin;
    }

    public function createOrder($data, $user, $drug_store_info = null)
    {
        LogEx::methodName($this->className, 'createOrder');

        $code = 'DDH' . Utils::getSequenceDB('DDH');
        $order_insert = array(
            'drug_store_id' => $user->drug_store_id,
            'order_code' => $code,
            'vat_amount' => isset($data['vat_amount']) ? $data['vat_amount'] : null,
            'receipt_date' => isset($data['receipt_date']) ? $data['receipt_date'] : null,
            'delivery_date' => isset($data['delivery_date']) ? $data['delivery_date'] : null,
            'status' => isset($data['status']) ? $data['status'] : 'ordering',
            'description' => isset($data['description']) ? $data['description'] : '',
            'supplier_id' => isset($data['supplier_id']) ? $data['supplier_id'] : null,
            'created_by' => $user->id,
            'amount' => $data['amount'],
            'pay_amount' => $data['pay_amount'],
            'supplier_order_code' => isset($data['supplier_order_code']) ? $data['supplier_order_code'] : '',
            'created_at' => Carbon::now(),
        );

        $detail_order = $data['order_detail'];dd($detail_order);

        DB::beginTransaction();
        try {
            $insert = $this->order->create($order_insert);
            $last_id_order = $insert->id;
            foreach ($detail_order as $value) {
                $item_order = array(
                    'order_id' => $last_id_order,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'cost' => isset($value['cost']) ? $value['cost'] : 0,
                    'package_form' => isset($value['package_form']) ? $value['package_form'] : '',
                    'concentration' => isset($value['concentration']) ? $value['concentration'] : '',
                    'manufacturer' => isset($value['manufacturer']) ? $value['manufacturer'] : '',
                );

                $this->order_detail->create($item_order);
            }
            DB::commit();
            return $last_id_order;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    public function updateOrder($id, $data)
    {
        LogEx::methodName($this->className, 'updateOrder');

        $order_insert = array(
            'vat_amount' => isset($data['vat_amount']) ? $data['vat_amount'] : null,
            'receipt_date' => isset($data['receipt_date']) ? $data['receipt_date'] : null,
            'delivery_date' => isset($data['delivery_date']) ? $data['delivery_date'] : null,
            'status' => isset($data['status']) ? $data['status'] : 'ordering',
            'description' => isset($data['description']) ? $data['description'] : '',
            'supplier_id' => isset($data['supplier_id']) ? $data['supplier_id'] : null,
            'amount' => $data['amount'],
            'pay_amount' => $data['pay_amount'],
            'supplier_order_code' => isset($data['supplier_order_code']) ? $data['supplier_order_code'] : '',
            'created_at' => isset($data['created_at']) ? $data['created_at'] : null
        );

        $detail_order = $data['detail_order'];

        DB::beginTransaction();
        try {
            $this->order->updateOneById($id, $order_insert);
            $this->order_detail->deleteAllByCredentials(['order_id' => $id]);
            foreach ($detail_order as $value) {
                $item_order = array(
                    'order_id' => $id,
                    'drug_id' => $value['drug_id'],
                    'unit_id' => $value['unit_id'],
                    'quantity' => $value['quantity'],
                    'cost' => isset($value['cost']) ? $value['cost'] : 0,
                    'package_form' => isset($value['package_form']) ? $value['package_form'] : '',
                    'concentration' => isset($value['concentration']) ? $value['concentration'] : '',
                    'manufacturer' => isset($value['manufacturer']) ? $value['manufacturer'] : '',
                );

                $this->order_detail->create($item_order);
            }
            DB::commit();
            return $id;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return false;
        }
    }

    /**
     * api v3
     * from f_order_list on V3 and export
    */
    public function exportOrderListV3(Request $request)
    {
        LogEx::methodName($this->className, 'exportOrderListV3');

        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = Utils::executeRawQueryV3(
            $this->orderListV3(
                $request->input(),
                $request->userInfo->drug_store_id,
                $request->userInfo->id
            ),
            $request->url(),
            $request->input(),
            1
        );
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQueryV3(
                        $this->orderListV3(
                            $request->input(),
                            $request->userInfo->drug_store_id,
                            $request->userInfo->id
                        ),
                        $request->url(),
                        $request->input(),
                        1,
                        3500
                    );
                    break;
                case "current_page":
                    $data = Utils::executeRawQueryV3(
                        $this->orderListV3(
                            $request->input(),
                            $request->userInfo->drug_store_id,
                            $request->userInfo->id
                        ),
                        $request->url(),
                        $request->input(),
                        1
                    );
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = Utils::executeRawQueryV3(
                        $this->orderListV3(
                            $request->input(),
                            $request->userInfo->drug_store_id,
                            $request->userInfo->id
                        ),
                        $request->url(),
                        $request->input(),
                        1,
                        3500
                    );
                    break;
            }
        }

        return $data;
    }

    public function orderListV3($requestInput, $drugStoreId, $userId)
    {
        LogEx::methodName($this->className, 'orderListV3');

        $p_drug_store_id = $drugStoreId;
        $p_is_admin = $requestInput['is_admin'];
        $p_from_date = Utils::coalesce($requestInput, 'from_date', "");
        $p_to_date = Utils::coalesce($requestInput, 'to_date', "");
        $p_received_date = Utils::coalesce($requestInput, 'received_date', "");
        $p_is_temp = Utils::coalesce($requestInput, 'is_temp', true);
        $keySearch = Utils::coalesce($requestInput, 'query', null);

        return DB::table(DB::raw('t_order o'))
                ->distinct()
                ->select(
                    DB::raw('on (o.id) o.id'),
                    'o.order_code',
                    'o.drug_store_id',
                    'd.name as drug_store_name',
                    'd.phone as drug_store_phone',
                    'd.address as drug_store_address',
                    'o.supplier_id',
                    's.name as gdp_name',
                    's.phone as gdp_phone',
                    's.address as gdp_address',
                    'o.order_date',
                    'o.received_date',
                    'o.in_invoice_id',
                    'o.out_invoice_id',
                    'o.gdp_id',
                    't.id as temp_invoice_id',
                    'o.amount',
                    'o.vat_amount',
                    'o.discount',
                    'o.pay_amount',
                    'o.status',
                    'o.note',
                    'o.history',
                    'o.created_by',
                    'c.name as creator',
                    'o.updated_by',
                    'e.name as updator',
                    'o.created_at',
                    'o.updated_at'
                )
                ->leftJoin(DB::raw('drugstores d'),'d.id','=','o.drug_store_id')
                ->leftJoin(DB::raw('drugstores s'),'s.id','=','o.gdp_id')
                ->leftJoin(DB::raw('users c'),'c.id','=','o.created_by')
                ->leftJoin(DB::raw('users e'),'e.id','=','o.updated_by')
                ->leftJoin(DB::raw('invoice_tmp t'),'t.refer_id','=','o.id')
                ->where( function ($query) use ($p_is_admin, $p_drug_store_id) {
                    if ($p_is_admin == 'true') {
                        $query->where('o.gdp_id', '=', $p_drug_store_id);
                    } else {
                        $query->where('o.drug_store_id', '=', $p_drug_store_id);
                    }
                })
                ->when($p_from_date, function ($query) use ($p_from_date) {
                    $query->where(DB::raw('o.order_date'), '>=', $p_from_date);
                })
                ->when($p_to_date, function ($query) use ($p_to_date) {
                    $query->where(DB::raw('o.order_date'), '<=', $p_to_date);
                })
                ->when($p_received_date, function ($query) use ($p_received_date) {
                    $query->where(DB::raw('o.received_date'), '=', $p_received_date);
                })

                ->when($keySearch, function ($query) use ($keySearch) {
                    $keySearch = trim($keySearch);
                    $queryDB = "1 = 1 AND ( o.order_code ~* '" . $keySearch
                        . "' or o.status  ~* '" . $keySearch
                        . "' or d.name  ~* '" . $keySearch
                        . "' or s.name  ~* '" . $keySearch
                        ."' )";
                    $query->whereRaw($queryDB);
                })
                ->when($p_is_temp == 'true', function ($query) {
                    $query->where(DB::raw('o.status'), '=', 'temp');
                })
                ->when($p_is_temp == 'false', function ($query) {
                    $query->where(DB::raw('o.status'), '<>', 'temp');
                })
                ->orderByRaw('o.id DESC');
    }

    /**
     * api v3
     * from f_order_detail on v3
    */
    public function orderDetailV3($requestInput, $drugStoreId)
    {
        LogEx::methodName($this->className, 'orderDetailV3');

        $p_id = Utils::coalesce($requestInput, 'id', null);
        $p_drug_store_id = $drugStoreId;
        $v_result = [];
        $t_order = DB::table(DB::raw('t_order o'))
            ->select(
                'o.*',
                DB::raw('jsonb_build_object(
                        \'id\', s.id,
                        \'name\', s.name,
                        \'address\', s.address,
                        \'status\', s.status,
                        \'pharmacist\', s.pharmacist,
                        \'warning_date\', s.warning_date,
                        \'phone\', s.phone
                    ) as drug_store'),
                DB::raw('jsonb_build_object(
                   \'drug_store\', jsonb_build_object(
                        \'id\', s.id,
                        \'name\', s.name,
                        \'address\', s.address,
                        \'status\', s.status,
                        \'pharmacist\', s.pharmacist,
                        \'warning_date\', s.warning_date
                    ),
                   \'creator\', coalesce(nullif(trim(c.full_name), \'\'), c.name),
                   \'creator_phone\', c.number_phone,
                   \'updater\', e.full_name,
                   \'gdp_name\', ds.name,
                   \'gdp_phone\', ds.phone
               ) as old_data'),
                'c.name as creator',
                'c.number_phone as creator_phone',
                'e.full_name as updater',
                'ds.name as gdp_name',
                'ds.phone as gdp_phone'
            )
            ->join(DB::raw('drugstores s'),'s.id','=','o.drug_store_id')
            ->join(DB::raw('drugstores ds'),'ds.id','=','o.gdp_id')
            ->leftJoin(DB::raw('users c'),'c.id','=','o.created_by')
            ->leftJoin(DB::raw('users e'),'e.id','=','o.updated_by')
            ->where('o.id', '=', $p_id)
            ->where(
                function ($query) use ($p_drug_store_id) {
                    $query->where('o.drug_store_id', '=', $p_drug_store_id)
                        ->orWhere('o.gdp_id', '=', $p_drug_store_id);
                })
            ->first();
        $v_result['order'] = $t_order;

        if (empty($t_order->id)) return $t_order;

        $tmp_order_detail = DB::table(DB::raw('t_order_detail od'))
            ->select('od.drug_id','od.out_drug_id',DB::raw('to_jsonb(od.*)
               || jsonb_build_object(
                   \'unit_name\', iu.name,
                   \'out_unit_name\', ou.name,
                   \'drug\', to_jsonb(i.*),
                   \'out_drug\', to_jsonb(o.*)
               ) as order_detail'))
            ->join(DB::raw('unit iu'),'iu.id','=','od.unit_id')
            ->leftJoin(DB::raw('unit ou'),'ou.id','=','od.out_unit_id')
            ->join(DB::raw('drug i'),'i.id','=','od.drug_id')
            ->leftJoin(DB::raw('drug o'),'o.id','=','od.out_drug_id')
            ->where('od.order_id', '=', $p_id)
            ->where('od.drug_id', '>', 0);

        $tmp_drug_units = DB::table(DB::raw('warehouse w'))
            ->distinct()
            ->select(
                't.drug_id',
                DB::raw('jsonb_agg(jsonb_build_object(\'unit_id\',u.id,\'unit_name\',u.name,\'is_basic\',
                w.is_basic,\'exchange\',w.exchange,\'pre_cost\',w.pre_cost,\'main_cost\',w.main_cost,
                \'current_cost\',w.current_cost)) as units')
            )
            ->joinSub($tmp_order_detail, 't', function ($join) {
                $join->on('w.drug_id', '=', 't.drug_id');
            })
            ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
            ->groupBy('t.drug_id')
            ->get();

        $tmp_drug_numbers = DB::table(DB::raw('warehouse w'))
            ->distinct()
            ->select(
                't.drug_id',
                DB::raw('jsonb_agg(jsonb_build_object(\'number\',w.number,\'expiry_date\',w.expiry_date,\'mfg_date\',
                w.mfg_date,\'manufacturing_date\',w.manufacturing_date,\'quantity\',w.quantity)) as number')
            )
            ->joinSub($tmp_order_detail, 't', function ($join) {
                $join->on('w.drug_id', '=', 't.drug_id');
            })
            ->whereRaw('w.is_check = false')
            ->where('w.is_basic','=','yes')
            ->groupBy('t.drug_id')
            ->get();

        $tmp_order_detail_lists = $tmp_order_detail->get();
        foreach ($tmp_order_detail_lists as $key => $tmp_order_detail_list) {
            $conver = json_decode($tmp_order_detail_list->order_detail);
            foreach ($tmp_drug_units as $tmp_drug_unit) {
                if ($tmp_order_detail_list->drug_id == $tmp_drug_unit->drug_id) {
                    $conver->drug->units = json_decode($tmp_drug_unit->units);
                }
            }
            foreach ($tmp_drug_numbers as $tmp_drug_number) {
                if ($tmp_order_detail_list->drug_id == $tmp_drug_number->drug_id) {
                    $conver->drug->numbers = json_decode($tmp_drug_number->number);
                }
            }
            $tmp_order_detail_lists[$key]->order_detail = $conver;
        }
        $v_result['order_detail'] = $tmp_order_detail_lists;

        return $v_result;
    }

    public function orderDetailV3New($requestInput, $drugStoreId)
    {
        LogEx::methodName($this->className, 'orderDetailV3');

        $p_id = Utils::coalesce($requestInput, 'id', null);
        $p_drug_store_id = $drugStoreId;

        $t_order = DB::table(DB::raw('t_order o'))
            ->select(
                'o.*',
                DB::raw('jsonb_build_object(
                        \'id\', s.id,
                        \'name\', s.name,
                        \'address\', s.address,
                        \'status\', s.status,
                        \'pharmacist\', s.pharmacist,
                        \'warning_date\', s.warning_date
                    ) as drug_store'),
               'c.name as creator',
               'c.number_phone as creator_phone',
               'e.full_name as updater',
               'ds.name as gdp_name',
               'ds.phone as gdp_phone'
            )
            ->join(DB::raw('drugstores s'),'s.id','=','o.drug_store_id')
            ->join(DB::raw('drugstores ds'),'ds.id','=','o.gdp_id')
            ->leftJoin(DB::raw('users c'),'c.id','=','o.created_by')
            ->leftJoin(DB::raw('users e'),'e.id','=','o.updated_by')
            ->where('o.id', '=', $p_id)
            ->where(
                function ($query) use ($p_drug_store_id) {
                    $query->where('o.drug_store_id', '=', $p_drug_store_id)
                        ->orWhere('o.gdp_id', '=', $p_drug_store_id);
                })
            ->first();

        $t_order->history = json_decode($t_order->history);
        $t_order->drug_store = json_decode($t_order->drug_store);

        if (empty($t_order->id)) return $t_order;

        $tmp_order_detail = DB::table(DB::raw('t_order_detail od'))
            ->select('od.drug_id','od.out_drug_id',DB::raw('to_jsonb(od.*)
               || jsonb_build_object(
                   \'unit_name\', iu.name,
                   \'out_unit_name\', ou.name,
                   \'drug\', to_jsonb(i.*),
                   \'out_drug\', to_jsonb(o.*)
               ) as order_detail'))
            ->join(DB::raw('unit iu'),'iu.id','=','od.unit_id')
            ->leftJoin(DB::raw('unit ou'),'ou.id','=','od.out_unit_id')
            ->join(DB::raw('drug i'),'i.id','=','od.drug_id')
            ->leftJoin(DB::raw('drug o'),'o.id','=','od.out_drug_id')
            ->where('od.order_id', '=', $p_id);

        $tmp_drug_units = DB::table(DB::raw('warehouse w'))
            ->distinct()
            ->select(
                't.drug_id',
                DB::raw('jsonb_agg(jsonb_build_object(\'unit_id\',u.id,\'unit_name\',u.name,\'is_basic\',
                w.is_basic,\'exchange\',w.exchange,\'pre_cost\',w.pre_cost,\'main_cost\',w.main_cost,
                \'current_cost\',w.current_cost)) as units')
            )
            ->joinSub($tmp_order_detail, 't', function ($join) {
                $join->on('w.drug_id', '=', 't.drug_id');
            })
            ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
            ->groupBy('t.drug_id');

        $tmp_drug_numbers = DB::table(DB::raw('warehouse w'))
            ->distinct()
            ->select(
                't.drug_id',
                DB::raw('jsonb_agg(jsonb_build_object(\'number\',w.number,\'expiry_date\',w.expiry_date,\'mfg_date\',
                w.mfg_date,\'manufacturing_date\',w.manufacturing_date,\'quantity\',w.quantity)) as numbers')
            )
            ->joinSub($tmp_order_detail, 't', function ($join) {
                $join->on('w.drug_id', '=', 't.drug_id');
            })
            ->whereRaw('w.is_check = false')
            ->where('w.is_basic','=','yes')
            ->groupBy('t.drug_id');

        $query_order_detail = str_replace_array('?', $tmp_order_detail->getBindings(), $tmp_order_detail->toSql());

        $joinSub = DB::table(DB::raw("({$query_order_detail}) id"))
            ->select(DB::raw('id.order_detail || 
            jsonb_build_object(\'drug\', id.order_detail->\'drug\' || 
            jsonb_build_object(\'units\', tdu.units, \'numbers\', tdn.numbers)) as order_detail'))
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

        $dataDetails = [];

        foreach ($joinSub as $key => $item) {
            $joinSub[$key]->order_detail = json_decode($item->order_detail);
            $dataDetails[] = $joinSub[$key]->order_detail;
        }

        return [
            'order' => $t_order,
            'order_detail' => $dataDetails
        ];
    }

    /**
     * api v3
     * from f_order_save on v3
    */
//    public function orderSaveV3($request)
//    {
//        LogEx::methodName($this->className, 'orderSaveV3');
//
//        $params = $request->input();
//        $p_id               =   Utils::coalesce($params, 'id', null);
//        $p_user_id          =   Utils::coalesce($params, 'user_id', null);
//        $p_drug_store_id    =   !empty($params['store_id']) ? $params['store_id'] : !empty($params['drug_store_id']) ? $params['drug_store_id'] : null;
//        $p_received_date    =   Utils::coalesce($params, 'received_date', null);
//        $p_amount           =   Utils::coalesce($params, 'amount', null);
//        $p_vat_amount       =   Utils::coalesce($params, 'vat_amount', null);
//        $p_discount         =   Utils::coalesce($params, 'discount', null);
//        $p_pay_amount       =   Utils::coalesce($params, 'pay_amount', null);
//        $p_status           =   Utils::coalesce($params, 'status', null);
//        $p_pay_method       =   Utils::coalesce($params, 'pay_method', null);
//        $p_note             =   Utils::coalesce($params, 'note', null);
//		$p_out_invoice_i    =   Utils::coalesce($params, 'out_invoice_id', null);
//		$p_in_invoice_id    =   Utils::coalesce($params, 'in_invoice_id', null);
//		$p_gdp_id    	    =   Utils::coalesce($params, 'gdp_id', null);
//        $v_current_time     =   Carbon::now()->format('y-m-d');
//
//        if ($p_id) {
//            $v_count = DB::table('t_order')
//                ->where('id', '=', $p_id)
//                ->count();
//            if ($v_count < 0) {
//                return 'Đơn đặt hàng không hợp lệ';
//            }
//        } else {
//            if (!empty($params['order_detail'])) {
//                $order = $this->createOrder($params, $request->userInfo);
//            } else {
//                return 'Chưa chọn sản phẩm đặt hàng';
//            }
//
//        }
//        return 1;
//    }

    /**
     * api v3
     * from f_order_list_drug on v3
     */
    public function orderListDrugV3($requestInput, $drugStoreId)
    {
        LogEx::methodName($this->className, 'orderListDrugV3');

        $p_drug_store_id    =   $drugStoreId;
        $p_user_id          =   Utils::coalesce($requestInput, 'user_id', null);
        $p_from_date        =   Utils::coalesce($requestInput, 'from_date', null);
        $p_to_date          =   Utils::coalesce($requestInput, 'to_date', null);
        $keySearch      =   Utils::coalesce($requestInput, 'query', null);
        $p_sort_by          =   Utils::coalesce($requestInput, 'sort_by', 'drug_name_asc');
        $p_limit            =   Utils::coalesce($requestInput, 'per_page', '100');
        $p_page             =   Utils::coalesce($requestInput, 'page', '1');
        $p_offset           =   ($p_page - 1) * $p_limit;;

        $tOder = TOrder::select(
                'od.drug_id as id',
                'd.drug_code',
                'd.name as drug_name',
                'd.warning_quantity_min',
                'd.warning_unit',
                'od.unit_id',
                'u.name AS unit_name',
                'od.exchange',
                DB::raw('SUM(od.quantity) as order_quantity')
            )->where('gdp_id', '=', $p_drug_store_id)
            ->when($p_from_date, function ($query) use ($p_from_date) {
                $query->where('order_date', '>=', $p_from_date);
            })
            ->when($p_to_date, function ($query) use ($p_to_date) {
                $query->where('order_date', '<=', $p_to_date);
            })
            ->whereIn('status',['sent', 'checked', 'prepared', 'confirm'])
            ->join(DB::raw('t_order_detail od'), DB::raw('t_order.id'),'=','od.order_id')
            ->join(DB::raw('unit u'),'u.id','=','od.unit_id')
            ->join(DB::raw('drug d'),'d.id','=','od.drug_id')
            ->groupBy([
                'od.unit_id','d.id','od.drug_id','od.exchange','u.name',
                'd.drug_code','d.name','d.warning_quantity_min','d.warning_unit'
            ])
            ->orderBy('d.name','DESC');

        $tmp_drug_order = TOrder::query()->select(
                'od.drug_id as id',
                'd.drug_code',
                'd.name as drug_name',
                'd.warning_quantity_min',
                'd.warning_unit',
                'od.unit_id',
                'u.name AS unit_name',
                'od.exchange',
                DB::raw('SUM(od.quantity)/3 as order_quantity')
            )
            //->mergeBindings($tOder->getQuery())
            ->joinSub(
                DB::table('t_order')
                    ->where('t_order.gdp_id', '=', $p_drug_store_id)
                    ->when($p_from_date, function ($query) use ($p_from_date) {
                        $query->where('t_order.order_date', '>=', $p_from_date);
                    })
                    ->when($p_to_date, function ($query) use ($p_to_date) {
                        $query->where('t_order.order_date', '<=', $p_to_date);
                    })
                    ->whereIn('t_order.status',['sent', 'checked', 'prepared', 'confirm'])
                    ->orderBy('t_order.id','DESC'),
                'o',
                function ($join) {
                    $join->on('t_order.gdp_id', '=', 'o.gdp_id');
                }
            )
            //->from(DB::raw("({$tOder->toSql()}) as o"))
            ->join(DB::raw('t_order_detail od'),'o.id','=','od.order_id')
            ->join(DB::raw('unit u'),'u.id','=','od.unit_id')
            ->join(DB::raw('drug d'),'d.id','=','od.drug_id')
            ->groupBy([
                'od.unit_id','d.id','od.drug_id','od.exchange','u.name',
                'd.drug_code','d.name','d.warning_quantity_min','d.warning_unit'
            ])
            ->orderBy('d.name','DESC');

        $warehouse = Warehouse::query()->select(
                'drug_id',
                'unit_id',
                DB::raw('SUM(quantity) AS quantity')
            )
            ->where('drug_store_id', '=', $p_drug_store_id)
            ->whereRaw('is_check = FALSE')
            ->where('is_basic','=','yes')
            ->groupBy(['drug_id', 'unit_id']);

        return DB::table(DB::raw("({$tmp_drug_order->toSql()}) as td"))
            ->select(
                'td.id',
                'td.drug_code',
                'td.drug_name',
                DB::raw('td.warning_quantity_min * COALESCE(
                (SELECT wh.exchange FROM warehouse wh where wh.drug_id = td.id and wh.unit_id = td.warning_unit limit 1), 0
                ) / td.exchange as warning_quantity_min'),
                'td.warning_unit',
                'td.unit_id',
                'td.unit_name',
                'td.exchange',
                'td.order_quantity',
                DB::raw('COALESCE(q.quantity, 0)/td.exchange AS quantity'),
                DB::raw(
                    'CASE
						WHEN
							COALESCE(q.quantity, 0)/td.exchange - td.order_quantity >= coalesce(td.warning_quantity_min, 0)
						THEN 0
						ELSE td.order_quantity - COALESCE(q.quantity, 0)/td.exchange + coalesce(td.warning_quantity_min, 0) END
					AS in_quantity')
            )
            ->mergeBindings($tmp_drug_order->getQuery())
            //->mergeBindings($tOder->getQuery())
            //->mergeBindings($warehouse->getQuery())
            ->leftJoinSub(
                DB::table(DB::raw('warehouse w'))
                    ->select(
                        'w.drug_id',
                        'w.unit_id',
                        DB::raw('SUM(w.quantity) AS quantity'))
                    ->where('w.drug_store_id', '=', $p_drug_store_id)
                    ->whereRaw('w.is_check = FALSE')
                    ->where('w.is_basic','=','yes')
                    ->groupBy(['w.drug_id', 'w.unit_id']),
                'q',
                function ($join) {
                    $join->on('td.id', '=', 'q.drug_id')
                        ->whereRaw('td.unit_id = q.unit_id');
                }
            )
            //->from(DB::raw("({$warehouse->toSql()}) as q"))
            ->when(!empty($keySearch), function ($query) use ($keySearch) {
                $query->where(
                    DB::raw('lower(vn_unaccent(td.drug_name))'),
                    'ILIKE',
                    '%' . strtolower(Utils::unaccent($keySearch)) . '%')
                    ->orWhere(
                        DB::raw('lower(vn_unaccent(td.drug_code))'),
                        'ILIKE',
                        '%' . strtolower(Utils::unaccent($keySearch)) . '%');
            })
            ->when($p_sort_by, function ($query) use ($p_sort_by) {
                    $orderBys = [
                        'drug_name_asc' => ['td.drug_name', 'ASC'],
                        'drug_name_desc' => ['td.drug_name', 'DESC'],
                        'drug_code_asc' => ['td.drug_code', 'ASC'],
                        'drug_code_desc' => ['td.drug_code', 'DESC'],
                        'warning_quantity_asc' => ['td.warning_quantity', 'ASC'],
                        'warning_quantity_desc' => ['td.warning_quantity', 'DESC'],
                        'quantity_asc' => ['td.quantity', 'ASC'],
                        'quantity_desc' => ['td.quantity', 'DESC'],
                        'order_quantity_asc' => ['td.order_quantity', 'ASC'],
                        'order_quantity_desc' => ['td.order_quantity', 'DESC'],
                        'in_quantity_asc' => ['td.quantity', 'DESC'],
                    ][$p_sort_by];
                    $query->orderBy($orderBys[0], $orderBys[1]);
            });
    }

    public function orderExportListByDrugV3(Request $request)
    {
        LogEx::methodName($this->className, 'orderExportListByDrugV3');

        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $query = $this->orderListDrugV3($request->input(), $request->userInfo->drug_store_id);
        $data = Utils::executeRawQueryV3(
            $query,
            $request->url(),
            $request->input(),
            1
        );

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQueryV3(
                        $query,
                        $request->url(),
                        $request->input(),
                        1,
                        3500
                    );
                    break;
                case "current_page":
                    $data = Utils::executeRawQueryV3(
                        $query,
                        $request->url(),
                        $request->input(),
                        1
                    );
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = Utils::executeRawQueryV3(
                        $query,
                        $request->url(),
                        $request->input(),
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
     * from f_order_reserve on v3
     */
    public function orderReserveV3($request)
    {
        LogEx::methodName($this->className, 'orderReserveV3');

        $p_store_id = $request->userInfo->drug_store_id;
        $p_user_id = $request->userInfo->id;
        $v_gdp_supplier = Supplier::where('refer_id', '=', $p_store_id)->get();

        if ($v_gdp_supplier->isEmpty()) return 'Không phải nhà phân phối';

        $v_gdp_supplier_id = $v_gdp_supplier->id;

        $tmp_order_reserve = DB::table(DB::raw('t_order od'))
            ->select(
                'tod.out_drug_id as drug_id',
                DB::raw('sum(tod.out_quantity * w.exchange) as quantity'),
                'tod.order_id as order_id',
                'od.order_code as order_code'
            )
            ->join(DB::raw('t_order_detail tod'), function($join) use ($v_gdp_supplier_id) {
                $join->on('od.id','=','tod.order_id')
                    ->where('od.status','=','po')
                    ->where('od.supplier_id','=',$v_gdp_supplier_id)
                    ->whereNotNull('tod.out_quantity');
            })
            ->join(DB::raw('warehouse w'), function($join) use ($p_store_id) {
                $join->on('w.drug_id','=','tod.out_drug_id')
                    ->on('w.unit_id','=','tod.out_unit_id')
                    ->whereRaw('w.is_check = true')
                    ->where('w.drug_store_id','=',$p_store_id);
            })
            ->groupBy(['od.drug_store_id','out_drug_id','tod.order_id','od.order_code']);

        $tmp_warehouse = DB::table(DB::raw('(select distinct drug_id from tmp_order_reserve) t'))
            ->select(
                't.drug_id',
                DB::raw('sum(coalesce(w.quantity,0)) as quantity')
            )
            ->leftJoin(DB::raw('warehouse w'), function($join) use ($p_store_id) {
                $join->on('w.drug_id','=','t.drug_id')
                    ->where('w.drug_store_id', '=', $p_store_id)
                    ->whereRaw('w.is_check = false')
                    ->where('w.is_basic','=','yes');
            })
            ->groupBy('t.drug_id');

        return DB::table(DB::raw('drug d'))
            ->select(
                'd.id',
                'd.drug_store_id',
                'd.drug_category_id',
                'd.drug_group_id',
                'd.name',
                'd.drug_code',
                'd.barcode',
                'd.short_name',
                'd.substances',
                'd.concentration',
                'd.country',
                'd.company',
                'd.registry_number',
                'd.package_form',
                'd.image',
                'd.warning_unit',
                'u.name as warning_unit_name',
                DB::raw('coalesce(d.warning_quantity_min, 0) * w.exchange as warning_quantity_min'),
                'd.warning_quantity_max','tw.quantity','tmp.quantity as po_quantity',
                DB::raw('greatest(tmp.quantity - tw.quantity +
                    (coalesce(d.warning_quantity_min, 0) * coalesce(w.exchange, 0))
                    ,0) as order_quantity'),
                'tmp.order_id as  order_id','tmp.order_code as order_code')
            ->joinSub(
                $tmp_order_reserve,
                'tm',
                function ($join) {
                    $join->on('tmp.drug_id','=','d.id');
                }
            )
            ->leftJoin(DB::raw('warehouse w'), function($join) {
                $join->on('w.drug_id','=','d.id')
                    ->on('w.unit_id','=','d.warning_unit')
                    ->whereRaw('w.is_check = true');
            })
            ->join(DB::raw('warehouse b'), function($join) {
                $join->on('b.drug_id','=','d.id')
                    ->whereRaw('b.is_check = true')
                    ->where('b.is_basic','=','yes');
            })
            ->join(DB::raw('unit u'),'u.id','=','b.unit_id')
            ->joinSub(
                $tmp_warehouse,
                'tw',
                function ($join) {
                    $join->on('tw.drug_id','=','d.id');
                }
            )
            ->orderBy('d.drug_code','ASC');
    }
}
