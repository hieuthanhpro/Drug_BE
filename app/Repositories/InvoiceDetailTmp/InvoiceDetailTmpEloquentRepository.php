<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/12/2018
 * Time: 11:09 AM
 */

namespace App\Repositories\InvoiceDetailTmp;
use App\LibExtension\Utils;
use App\Models\InvoiceDetailTmp;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use App\Repositories\AbstractBaseRepository;
use App\LibExtension\LogEx;
class InvoiceDetailTmpEloquentRepository extends AbstractBaseRepository implements InvoiceDetailTmpRepositoryInterface
{
    protected $className = "InvoiceDetailTmpEloquentRepository";
    public function __construct(InvoiceDetailTmp $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    /**
     * api v3
     * from f_invoice_tmp_list on v3
     */
    public function invoiceTmpListV3($params)
    {
        LogEx::methodName($this->className, 'invoiceTmpListV3');

        $p_store_id                 =  $params->userInfo->drug_store_id;
        $requestInput               =  $params->input();
        $v_invoice_type             =  Utils::coalesce($requestInput, 'invoice_type', 'IV2');
        $v_supplier_invoice_code    =  Utils::coalesce($requestInput, 'supplier_invoice_code', null);
        $v_supplier_id              =  Utils::coalesce($requestInput, 'supplier_id', null);
        $v_from_date                =  Utils::coalesce($requestInput, 'from_date', null);
        $v_to_date                  =  Utils::coalesce($requestInput, 'to_date', null);
        $v_customer                 =  Utils::coalesce($requestInput, 'customer_id', -1);
        $search_text                =  Utils::coalesce($requestInput, 'query', null);

        return DB::table(DB::raw('invoice_tmp i'))
            ->distinct()
            ->select(
                'i.id',
                'i.drug_store_id',
                'i.supplier_invoice_code',
                'i.invoice_code',
                'i.invoice_type',
                'i.warehouse_action_id',
                'i.refer_id',
                'i.customer_id',
                'i.amount',
                'i.vat_amount',
                'i.pay_amount',
                'i.discount',
                'i.created_by',
                'i.description',
                'i.status',
                'i.payment_status',
                'i.receipt_date',
                'i.created_at',
                'i.updated_at',
                'i.method',
                'i.payment_method',
                'c.name as customer_name',
                'c.number_phone as customer_phone',
                's.name as supplier_name',
                's.number_phone as supplier_phone',
                's.id as supplier_id',
                DB::raw('case
                when i.invoice_type in (\'IV2\', \'IV7\') then s.tax_number
                else c.tax_number end as tax_number'))
            ->leftJoin(DB::raw('customer c'), function($join) use ($v_invoice_type) {
                if (in_array($v_invoice_type, array("IV1", "IV2", "IV3"))) {
                    $join->on('c.id', '=', 'i.customer_id');
                } else {
                    $join->where("c.id", "=", null);
                }
            })
            ->leftJoin(DB::raw('supplier s'), function($join) use ($v_invoice_type) {
                if (in_array($v_invoice_type, array("IV1", "IV2", "IV3"))) {
                    $join->on('s.id', '=', 'i.customer_id');
                } else {
                    $join->where("s.id", "=", null);
                }
            })
            ->leftJoin(DB::raw('prescription p'), function($join) use ($v_invoice_type) {
                if (in_array($v_invoice_type, array("IV1"))) {
                    $join->on('p.invoice_id', '=', 'i.id');
                } else {
                    $join->where("p.invoice_id", "=", null);
                }

            })
            ->where('i.drug_store_id', '=', $p_store_id)
            ->where('i.invoice_type', '=', $v_invoice_type)
            ->when($v_customer > 0, function ($query) use ($v_customer) {
                $v_customer < 0 ?
                $query->whereRaw('i.customer_id is null') :
                $query->where('i.customer_id', '=', $v_customer);
            })
            ->when(!empty($search_text), function ($query) use ($search_text) {
                $name = trim($search_text);
                $queryFultextDB = '1 = 1 ';
                $queryFultextDB = $queryFultextDB . " AND (i.invoice_code = '" . trim($search_text) . "'";
                $queryFultextDB = $queryFultextDB . " OR (s.name ~* '" . $name
                    . "' or i.warehouse_action_id  ~* '" . $name
                    . "' or s.number_phone  ~* '" . $name
                    . "' or s.tax_number  ~* '" . $name
                    ."'))";
                $query->whereRaw($queryFultextDB);
            })
            ->when(!empty($v_from_date), function ($query) use ($v_from_date) {
                $query->where('i.created_at', '>=', $v_from_date);
            })
            ->when(!empty($v_to_date), function ($query) use ($v_to_date) {
                $query->where('i.created_at', '<=', $v_to_date);
            })
            ->when(!empty($v_supplier_invoice_code), function ($query) use ($v_supplier_invoice_code) {
                $query->where('i.supplier_invoice_code', '=', $v_supplier_invoice_code);
            })
            ->when(!empty($v_supplier_id), function ($query) use ($v_supplier_id) {
                $query->where('s.id', '=', $v_supplier_id);
            })
            ->orderBy('created_at', 'DESC');
    }

    /**
     * api v3
     * from f_invoice_tmp_detail on v3
    */
    public function invoiceTmpDetailV3($id, $params)
    {
        LogEx::methodName($this->className, 'invoiceDetailV3');

        $v_return_data = [];
        $p_drug_store_id = $params->userInfo->drug_store_id;
        $p_invoice_id = $id;

        $v_invoice_data = DB::table(DB::raw('invoice_tmp i'))
            ->select(
                'i.*',
                'u.name              as user_fullname',
                'u.username          as user_username',
                's.id                as supplier_id',
                's.name              as supplier_name',
                's.number_phone      as supplier_phone',
                's.email             as supplier_email',
                's.address           as supplier_address',
                's.website           as supplier_website'
            )
            ->leftJoin(DB::raw('users u'),'u.id','=','i.created_by')
            ->leftJoin(DB::raw('supplier s'), 's.id','=','i.customer_id')
            ->where('i.drug_store_id', '=', $p_drug_store_id)
            ->when(
                $p_invoice_id,
                function ($query, $p_invoice_id) {
                    $query->where('i.id', '=', $p_invoice_id);
                }
            )
            ->first();

        if ($v_invoice_data) {
            $v_invoice_id = $v_invoice_data->id;
            $v_return_data['invoice'] = $v_invoice_data;

            $tmp_invoice_detail = DB::table(DB::raw('invoice_detail_tmp id'))
                ->select('id.drug_id', DB::raw('to_jsonb(id.*) || jsonb_build_object(
                                    \'current_cost\', id.cost,
                                    \'drug_code\', d.drug_code,
                                    \'drug_name\', d.name,
                                    \'image\', d.image,
                                    \'unit_name\', u.name,
                                    \'drug\', case when d.id > 0 then to_jsonb(d.*) else null end
                                )       as invoice_detail'))
                ->leftJoin(DB::raw('drug d'),'d.id','=','id.drug_id')
                ->leftJoin(DB::raw('unit u'),'u.id','=','id.unit_id')
                ->where('id.invoice_id', '=', $v_invoice_id)
                ->where('id.drug_id', '>', 0);

            $tmp_drug_units = DB::table(DB::raw('warehouse w'))
                ->select(
                    'w.drug_id',
                    DB::raw(
                        'jsonb_agg(jsonb_build_object(
                                \'unit_id\',u.id,
                                \'unit_name\',u.name,
                                \'exchange\',w.exchange,
                                \'is_basic\',w.is_basic,
                                \'pre_cost\',w.pre_cost,
                                \'main_cost\',w.main_cost,
                                \'current_cost\',w.current_cost)
                            ) as units'
                    )
                )
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
                ->select(
                    'w.drug_id',
                    DB::raw(
                        'jsonb_agg(jsonb_build_object(
                                \'number\',w.number,
                                \'expiry_date\',w.expiry_date,
                                \'quantity\',w.quantity)
                            ) as numbers'
                    )
                )
                ->where('w.is_basic','=','yes')
                ->where('w.is_check', '=', false)
                ->joinSub(
                    $tmp_invoice_detail,
                    't',
                    function ($join) {
                        $join->on('t.drug_id', '=', 'w.drug_id');
                    }
                )
                ->groupBy('w.drug_id');

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

            //$v_return_data['invoice_detail'] = [];

            foreach ($joinSub as $key => $item) {
                $joinSub[$key]->invoice_detail = json_decode($item->invoice_detail);
                //$v_return_data['invoice_detail'][] = $joinSub[$key]->invoice_detail;
            }

            $v_return_data['invoice_detail'] = $joinSub;
        }

        return $v_return_data;
    }

    /**
     * api v3
     * from f_invoice_tmp_detail on v3
     */
    public function invoiceTmpDetailV3New ($id, $params)
    {
        LogEx::methodName($this->className, 'invoiceTmpDetailV3New');

        $p_drug_store_id = $params->userInfo->drug_store_id;
        $p_invoice_id = $id;

        $v_invoice_data = DB::table(DB::raw('invoice_tmp i'))
            ->select(
                'i.*',
                'u.name              as user_fullname',
                'u.username          as user_username',
                's.id                as supplier_id',
                's.name              as supplier_name',
                's.number_phone      as supplier_phone',
                's.email             as supplier_email',
                's.address           as supplier_address',
                's.website           as supplier_website'
            )
            ->leftJoin(DB::raw('users u'),'u.id','=','i.created_by')
            ->leftJoin(DB::raw('supplier s'), 's.id','=','i.customer_id')
            ->where('i.drug_store_id', '=', $p_drug_store_id)
            ->when(
                $p_invoice_id,
                function ($query, $p_invoice_id) {
                    $query->where('i.id', '=', $p_invoice_id);
                }
            )
            ->first();

        if ($v_invoice_data) {
            $v_invoice_id = $v_invoice_data->id;

            $tmp_invoice_detail = DB::table(DB::raw('invoice_detail_tmp id'))
                ->select('id.drug_id', DB::raw('to_jsonb(id.*) || jsonb_build_object(
                                    \'current_cost\', id.cost,
                                    \'drug_code\', d.drug_code,
                                    \'drug_name\', d.name,
                                    \'image\', d.image,
                                    \'unit_name\', u.name,
                                    \'drug\', case when d.id > 0 then to_jsonb(d.*) else null end
                                )       as invoice_detail'))
                ->leftJoin(DB::raw('drug d'),'d.id','=','id.drug_id')
                ->leftJoin(DB::raw('unit u'),'u.id','=','id.unit_id')
                ->where('id.invoice_id', '=', $v_invoice_id)
                ->where('id.drug_id', '>', 0);

            $tmp_drug_units = DB::table(DB::raw('warehouse w'))
                ->select(
                    'w.drug_id',
                    DB::raw(
                        'jsonb_agg(jsonb_build_object(
                                \'unit_id\',u.id,
                                \'unit_name\',u.name,
                                \'exchange\',w.exchange,
                                \'is_basic\',w.is_basic,
                                \'pre_cost\',w.pre_cost,
                                \'main_cost\',w.main_cost,
                                \'current_cost\',w.current_cost)
                            ) as units'
                    )
                )
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
                ->select(
                    'w.drug_id',
                    DB::raw(
                        'jsonb_agg(jsonb_build_object(
                                \'number\',w.number,
                                \'expiry_date\',w.expiry_date,
                                \'quantity\',w.quantity)
                            ) as numbers'
                    )
                )
                ->where('w.is_basic','=','yes')
                ->where('w.is_check', '=', false)
                ->joinSub(
                    $tmp_invoice_detail,
                    't',
                    function ($join) {
                        $join->on('t.drug_id', '=', 'w.drug_id');
                    }
                )
                ->groupBy('w.drug_id');

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

            $dataDetails = [];

            foreach ($joinSub as $key => $item) {
                $joinSub[$key]->invoice_detail = json_decode($item->invoice_detail);
                $dataDetails[] = $joinSub[$key]->invoice_detail;
            }
        }

        return [
            'invoice' => $v_invoice_data,
            'invoice_detail' => $dataDetails
        ];
    }
}
