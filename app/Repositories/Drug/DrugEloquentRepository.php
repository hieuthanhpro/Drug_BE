<?php


namespace App\Repositories\Drug;

use App\Http\Requests\Drug\DrugMasterFilterRequest;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\Drug;
use App\Models\DrugStore;
use App\Models\InvoiceDetail;
use App\Repositories\AbstractBaseRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrugEloquentRepository extends AbstractBaseRepository implements DrugRepositoryInterface
{
    protected $className = "DrugEloquentRepository";

    public function __construct(Drug $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function filter($drugFilterInput, $drugStoreId, $limit = null)
    {
        LogEx::methodName($this->className, 'filter');
        $limit = $limit ?? $drugFilterInput["per_page"] ?? 10;
        $searchText = $drugFilterInput["query"] ?? null;
        $group = $drugFilterInput["group"] ?? null;
        $category = $drugFilterInput["category"] ?? null;
        $isDrug = empty($drugFilterInput["is_drug"]) ? null : $drugFilterInput["is_drug"];
        $active = $drugFilterInput["active"] ?? null;
        $sortBy = $drugFilterInput["sort_by"] ?? "id";
        $ids = $drugFilterInput["ids"] ?? null;

        $queryDB = '1 = 1';

        if (isset($searchText)) {
            $queryDB = $queryDB . " AND (vn_unaccent(drug.name) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(drug.drug_code) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(drug.barcode) ILIKE '%" . Utils::unaccent($searchText) . "%')";
        }
        if (isset($group)) {
            $queryDB = $queryDB . " AND drug.drug_group_id =" . $group;
        }
        if (isset($category)) {
            $queryDB = $queryDB . " AND drug.drug_category_id =" . $category;
        }
        if (isset($isDrug)) {
            if ($isDrug === 'true') {
                $queryDB = $queryDB . " AND left(drug.drug_code, 2) <> 'SP'";
            } else {
                $queryDB = $queryDB . " AND left(drug.drug_code, 2) = 'SP'";
            }
        }

        if (isset($ids) && sizeof($ids) > 0) {
            $queryDB = $queryDB . " AND drug.id in(" . collect($ids)->implode(',') . ")";
        }
        if (isset($active)) {
            $queryDB = $queryDB . " AND drug.active ='" . $active . "'";
        }

        return DB::table('drug')
            ->select(
                'drug.*',
                //'drug.name',
                //'drug.short_name',
                //'drug.drug_code',
                //'drug.barcode',
                //'drug.company',
                //'drug.country',
                //'drug.concentration',
                //'drug.substances',
                //'drug.image',
                //'drug.package_form',
                //'drug.registry_number',
                //'drug.warning_days',
                //'drug.warning_quantity_min',
                //'drug.warning_quantity_max',
                //'drug.warning_unit',
                //'drug.active',
                'drug_category.name as category_name',
                'drug_group.name as group_name',
                'warehouse.main_cost',
                'warehouse.pre_cost',
                'warehouse.current_cost',
                'warehouse.unit_id',
                'unit.name as unit_name',
                DB::raw('coalesce(q.quantity, 0) as quantity'),
                DB::raw('(
                   SELECT json_agg(json_build_object(
                           \'drug_store_id\', t.drug_store_id,
                           \'drug_id\', t.drug_id,
                           \'unit_id\', t.unit_id,
                           \'exchange\', t.exchange,
                           \'out_price\', t.out_price,
                           \'name\', ut.name))::jsonb
                   FROM t_drug_unit t
                            left join unit ut on t.unit_id = ut.id
                   where t.drug_id = drug.id
                     and t.is_basis <> \'yes\'
               )                       as other_units')
            )
            ->leftJoin('drug_group', 'drug_group.id', 'drug.drug_group_id')
            ->leftJoin('drug_category', 'drug_category.id', 'drug.drug_category_id')
            ->join('warehouse', function ($join) {
                $join->on('drug.id', '=', 'warehouse.drug_id')
                    ->where('warehouse.is_check', '=', true)
                    ->where('warehouse.is_basic', '=', 'yes');
            })
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->leftJoinSub("select w.drug_id,
                   w.unit_id,
                   sum(w.quantity) as quantity
            from warehouse w
            where w.drug_store_id = $drugStoreId
              and w.is_check = false
              and w.is_basic = 'yes'
            group by w.drug_id, w.unit_id", "q", "q.drug_id", "drug.id")
            ->where('drug.drug_store_id', $drugStoreId)
            ->whereRaw($queryDB)
            ->orderByRaw("(case when '$sortBy' = 'id' then drug.id end) desc,
							 (case when '$sortBy' = 'drug_name_asc' then drug.name end) asc,
							 (case when '$sortBy' = 'drug_name_desc' then drug.name end) desc,
							 (case when '$sortBy' = 'drug_code_asc' then drug.drug_code end) asc,
							 (case when '$sortBy' = 'drug_code_desc' then drug.drug_code end) desc,
							 (case when '$sortBy' = 'bar_code_asc' then drug.barcode end) asc,
							 (case when '$sortBy' = 'bar_code_desc' then drug.barcode end) desc,
							 (case when '$sortBy' = 'unit_name_asc' then unit.name end) asc,
							 (case when '$sortBy' = 'unit_name_desc' then unit.name end) desc,
							 (case when '$sortBy' = 'out_price_asc' then warehouse.current_cost end) asc,
							 (case when '$sortBy' = 'out_price_desc' then warehouse.current_cost end) desc,
							 (case when '$sortBy' = 'quantity_asc' then coalesce(q.quantity, 0) end) asc,
							 (case when '$sortBy' = 'quantity_desc' then coalesce(q.quantity, 0) end) desc")
            ->paginate($limit);
    }

    public function filterDrugMaster(DrugMasterFilterRequest $drugMasterFilterRequest)
    {
        LogEx::methodName($this->className, 'filterDrugMaster');
        $requestInput = $drugMasterFilterRequest->input();
        $limit = $requestInput["limit"] ?? 10;
        $searchText = $requestInput["query"] ?? null;

        $queryDB = '1 = 1';

        if (isset($searchText)) {
            $queryDB = $queryDB . " AND (vn_unaccent(drug_master_data.name) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(drug_master_data.drug_code) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(drug_master_data.barcode) ILIKE '%" . Utils::unaccent($searchText) . "%')";
        }

        return DB::table('drug_master_data')
            ->select('drug_master_data.*')
            ->whereRaw($queryDB)
            ->orderByDesc("id")
            ->paginate($limit);
    }

    public function detail($id, $drugStoreId)
    {
        LogEx::methodName($this->className, 'detail');
        return DB::table("drug")
            ->select(
                'drug.*',
                'drug_group.id as drug_group_id',
                'drug_group.name as drug_group_name',
                'drug_category.id as drug_category_id',
                'drug_category.name as drug_category_name'
            )
            ->leftJoin('drug_group', 'drug_group.id', 'drug.drug_group_id')
            ->leftJoin('drug_category', 'drug_category.id', 'drug.drug_category_id')
            ->where('drug.drug_store_id', $drugStoreId)
            ->where('drug.id', $id)->get();
    }

    public function filterForSale($drugFilterInput, $drugStoreId)
    {
        LogEx::methodName($this->className, 'filter');
        $limit = $limit ?? $drugFilterInput["limit"] ?? 50;
        $searchText = $drugFilterInput["query"] ?? null;

        $queryDB = '1 = 1';
        if (isset($searchText)) {
            $queryDB = $queryDB . " AND (vn_unaccent(drug.name) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(drug.drug_code) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(drug.barcode) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(warehouse.number) ILIKE '%" . Utils::unaccent($searchText) . "%')";
        }

        return DB::table('drug')
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY drug.name) as row_number'),
                'drug.id',
                'drug.name',
                'drug.short_name',
                'drug.package_form',
                'drug.company',
                'drug.drug_code',
                'drug.country',
                'drug.image',
                'drug.registry_number',
                'drug.vat',
                'drug.drug_category_id',
                'drug.drug_group_id',
                'warehouse.quantity',
                'warehouse.number',
                'warehouse.main_cost',
                'warehouse.current_cost',
                'warehouse.expiry_date',
                'warehouse.unit_id',
                'unit.name as unit_name',
                DB::raw("(select json_agg(json_build_object(
                                    'number', wh.number,
                                    'expiry_date', wh.expiry_date,
                                    'quantity', wh.quantity,
                                    'main_cost', wh.main_cost,
                                    'current_cost', wh.current_cost,
                                    'is_basic', wh.is_basic,
                                    'exchange', wh.exchange,
                                    'unit_id', wh.unit_id,
                                    'unit_name', u.name
                                ))::jsonb from warehouse wh join unit u on u.id = wh.unit_id where wh.is_check = 'no'
                                and wh.drug_store_id = $drugStoreId and wh.drug_id = drug.id 
                                and wh.quantity >= 1) as units"),
                DB::raw("(select json_agg(json_build_object(
                                    'number', warehouse.number,
                                    'expiry_date', warehouse.expiry_date,
                                    'quantity', warehouse.quantity
                                    ))::jsonb from warehouse where warehouse.drug_store_id = $drugStoreId and warehouse.drug_id = drug.id 
                                and warehouse.is_basic = 'yes' and warehouse.is_check = false and warehouse.quantity >= 1) as numbers")
            )
            ->join('warehouse', 'warehouse.drug_id', 'drug.id')
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('drug.drug_store_id', $drugStoreId)
            ->where('drug.active', '=', 'yes')
            ->where('warehouse.is_check', 'no')
            ->where('warehouse.quantity', '>=', 1)
            ->where('warehouse.expiry_date', '>', Carbon::now())
            ->whereRaw($queryDB)
            ->orderByDesc('drug.name')
            ->paginate($limit);
    }

    //===================================================//
    public function getUnitByDrug($drug_store_id, $drug_id)
    {
        LogEx::methodName($this->className, 'getUnitByDrug');

        $data = DB::table('drug')
            ->select(
                'unit.id',
                'unit.name'
            )
            ->join('warehouse', 'warehouse.drug_id', 'drug.id')
            ->join('unit', 'warehouse.unit_id', 'unit.id')
            ->where('drug.drug_store_id', $drug_store_id)
            ->where('drug.id', $drug_id)
            ->get();
        return $data;
    }

    public function countDrugByGroup($drug_store_id)
    {
        LogEx::methodName($this->className, 'countDrugByGroup');

        $data_result = array();
        $data = DB::table('drug')
            ->select(DB::raw('count(*) as total'), 'drug.drug_group_id')
            ->join('drug_group', 'drug.drug_group_id', 'drug_group.id')
            ->groupBy(['drug.drug_group_id'])
            ->where('drug.drug_store_id', $drug_store_id)
            ->get();

        foreach ($data as $key => $item) {
            $name = DB::table('drug_group')
                ->select('drug_group.name')
                ->where('id', $item->drug_group_id)
                ->first();
            $data_result[$key]['name'] = $name->name;
            $data_result[$key]['total'] = $item->total;
        }

        return $data_result;
    }

    public function getListDrugWaring($drug_store_id)
    {
        LogEx::methodName($this->className, '###### getListDrugWaring ######');

        $data_result = array();
        // minduc: will fix
        // warehouse - drug (drug_group) - unit
        // $data = DB::table('warehouse')
        //     ->select(
        //         'drug.name',
        //         'drug.image',
        //         'drug.drug_code',
        //         'drug.id as drug_id',
        //         'drug_group.name as group_name',
        //         'warehouse.quantity',
        //         'warehouse.expiry_date',
        //         'warehouse.number',
        //         'unit.name as unit_name'
        //     )
        //     ->join('drug', 'drug.id', 'warehouse.drug_id')
        //     ->leftjoin('drug_group', 'drug_group.id', 'drug.drug_group_id')
        //     ->leftjoin('unit', 'warehouse.unit_id', 'unit.id')
        //     ->where('warehouse.is_basic', 'yes')
        //     // Master record is_check = 0, Nhập kho thì is_check = 1
        //     ->where('warehouse.is_check', 0)
        //         // ->where('warehouse.quantity','>', 0)
        //     ->where('warehouse.drug_store_id', $drug_store_id)
        //     ->get();

        // foreach ($data as $value) {
        //     $warring = DB::table('warehouse')
        //         ->select(
        //             'warehouse.warning_quantity'
        //         )
        //         ->where('warehouse.is_basic', 'yes')
        //         ->where('warehouse.is_check', 1)
        //         ->where('warehouse.drug_id', $value->drug_id)
        //         ->first();
        //     if ($value->quantity <= $warring->warning_quantity && $value->quantity > 0) {
        //         $data_result[] = $value;
        //     }
        // }
        return $data_result;
    }

    public function getListDrugWaringCombineUnits($drug_store_id)
    {
        LogEx::methodName($this->className, 'getListDrugWaringCombineUnits');

        $data_result = array();
        $data_result_tmp = array();

        $data = DB::table('warehouse')
            ->select(
                'warehouse.*',
                'unit.name as name'
            )
            ->join('drug', 'drug.id', 'warehouse.drug_id')
            ->leftjoin('drug_group', 'drug_group.id', 'drug.drug_group_id')
            ->leftjoin('unit', 'warehouse.unit_id', 'unit.id')
            ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.is_check', 0)
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->get();


        $drug_arr = array();
        foreach ($data as $value) {
            $warring = DB::table('warehouse')
                ->select(
                    'warehouse.warning_quantity'
                )
                ->where('warehouse.is_basic', 'yes')
                ->where('warehouse.is_check', 1)
                ->where('warehouse.drug_id', $value->drug_id)
                ->first();
            if ($value->quantity <= $warring->warning_quantity && $value->quantity > 0) {
                if (empty($data_result_tmp[$value->drug_id])) {
                    $data_result_tmp[$value->drug_id] = array();
                }
                $drug_arr[] = $value->drug_id;
                $data_result_tmp[$value->drug_id][] = $value;
            }
        }

        $info_drug_arr = DB::table('drug')->whereIn('id', $drug_arr)->get();
        foreach ($info_drug_arr as $info_drug) {
            $info_drug->units = $data_result_tmp[$info_drug->id];
            $data_result[] = $info_drug;
        }
        return $data_result;
    }

    public function getDrugExpired($drug_store_id)
    {
        LogEx::methodName($this->className, 'getDrugExpired');

        $data_result = array();
        $drug_store = DrugStore::Select('*')
            ->where('id', $drug_store_id)
            ->first();
        $data_result['thuoc-het-han'] = array();
        $data_result['thuoc-sap-het-han'] = array();
        $data = DB::table('warehouse')
            ->select(
                'drug.name',
                'drug.image',
                'drug.drug_code',
                'drug.id as drug_id',
                'drug_group.name as group_name',
                'warehouse.quantity',
                'warehouse.number',
                'warehouse.expiry_date',
                'unit.name as unit_name'
            )
            ->join('drug', 'drug.id', 'warehouse.drug_id')
            ->leftjoin('drug_group', 'drug_group.id', 'drug.drug_group_id')
            ->leftjoin('unit', 'warehouse.unit_id', 'unit.id')
            ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.is_check', 0)
            ->where('warehouse.quantity', '>=', 1)
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->orderBy('warehouse.expiry_date')
            ->get();

        foreach ($data as $value) {
            $differenceFormat = '%a';
            $expired_date = date_create($value->expiry_date)->format('Y-m-d');
            $current_date = date("Y-m-d");
            if (strtotime($current_date) < strtotime($expired_date)) {
                $diff = date_diff(date_create($current_date), date_create($expired_date));
                if ($diff->format($differenceFormat) < $drug_store->warning_date) {
                    $data_result['thuoc-sap-het-han'][] = $value;
                }
            } else {
                $data_result['thuoc-het-han'][] = $value;
            }
        }
        return $data_result;
    }

    public function getDrugInventory($drug_store_id)
    {
        LogEx::methodName($this->className, '###### getDrugInventory ######');

        $count = 0;
        // minduc: will fix
        // $list_drug = $this->findManyBy('drug_store_id', $drug_store_id);
        // foreach ($list_drug as $value) {
        //     $quality = 0;
        //     $ware_house = DB::table('warehouse')
        //         ->select(
        //             'warehouse.quantity',
        //             'warehouse.number'
        //         )
        //         ->where('warehouse.is_basic', 'yes')
        //         ->where('warehouse.is_check', 0)
        //         ->where('warehouse.drug_id', $value->id)
        //         ->get();
        //     if (!empty($ware_house)) {
        //         foreach ($ware_house as $item) {
        //             $quality = $quality + $item->quantity;
        //         }
        //     }
        //     if ($quality != 0) {
        //         $count = $count + 1;
        //     }
        // }
        return $count;
    }

    public function isRemainInStock($id, $drug_store_id)
    {
        $count = DB::table('warehouse')
            ->where('warehouse.drug_id', $id)
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->where('warehouse.quantity', '>=', 1)
            ->count();
        return $count > 0;
    }

    public function getDetailDrug($id, $drug_store_id)
    {
        LogEx::methodName($this->className, 'getDetailDrug');

        $data = DB::table('drug')
            ->select(
                'drug.*',
                'drug_group.name as group_name',
                'drug_category.name as category_name'
            )
            ->leftjoin('drug_group', 'drug_group.id', 'drug.drug_group_id')
            ->leftjoin('drug_category', 'drug_category.id', 'drug.drug_category_id')
            ->where('drug.id', $id)
            ->where('drug.drug_store_id', $drug_store_id)
            ->get();
        // Query master data
        $data['unit'] = DB::table('warehouse')
            ->select(
                'warehouse.quantity',
                'warehouse.is_basic',
                'warehouse.exchange',
                'warehouse.current_cost',
                'warehouse.main_cost',
                'warehouse.quantity',
                'warehouse.warning_quantity',
                'warehouse.manufacturing_date',
                'unit.name as unit_name',
                'unit.id as unit_id'
            )
            ->join('unit', 'warehouse.unit_id', 'unit.id')
            ->where('warehouse.drug_id', $id)
            ->where('warehouse.drug_store_id', $drug_store_id)
            // Master data
            ->where('warehouse.is_check', true)
            // Quan start add 20100410
            ->orderBy('warehouse.exchange', 'asc')
            // Quan end add 20100410
            ->get();

        // Lấy danh sách lô còn trong kho của thuốc
        $data['list_numbers'] = DB::table('warehouse')
            ->select('number')
            ->where('warehouse.drug_id', $id)
            ->where('warehouse.number', '!=', '')
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->pluck('number')
            ->toArray();


        $data['numbers'] = DB::table('warehouse')
            ->select('number', 'expiry_date', 'quantity')
            ->where('warehouse.drug_id', $id)
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.is_check', false)
            ->where('warehouse.quantity', '>=', 1)
            ->get();

        // Edit Drug page
        $data['isRemainInStock'] = $this->isRemainInStock($id, $drug_store_id);

        return $data;
    }

    public function getAllDrugByStore($drug_store_id)
    {
        LogEx::methodName($this->className, 'getAllDrugByStore');

        $data = DB::table('drug')
            ->select(
                'drug.id',
                'drug.name',
                'drug.barcode',
                'drug.country',
                'drug.active',
                'drug.image',
                'drug.description',
                'drug.registry_number',
                'drug_group.name as group_name',
                'drug_category.name as category_name',
                'warehouse.main_cost',
                'warehouse.pre_cost',
                'warehouse.quantity',
                'warehouse.number',
                'warehouse.current_cost',
                'unit.name as unit_name'
            )
            ->join('drug_group', 'drug.drug_group_id', 'drug_group.id')
            ->leftjoin('drug_category', 'drug.drug_category_id', 'drug_category.id')
            ->join('warehouse', 'warehouse.drug_id', 'drug.id')
            ->join('unit', 'warehouse.unit_id', 'unit.id')
            ->where('drug.drug_store_id', $drug_store_id)
            ->where('warehouse.is_check', 0)
            ->where('warehouse.is_basic', 'yes')
            ->get();

        return $data;
    }

    public function getListMasterByName($name, $per_page = null)
    {
        LogEx::methodName($this->className, 'getListMasterByName');

        if ($per_page == null) {
            $page_size = 10;
        } else {
            $page_size = $per_page;
        }
        $data = DB::table('drug')
            ->select(
                'drug.*',
                'unit.id as unit_id',
                'unit.name as unit_name'
            )
            ->join('unit', 'drug.unit_id', 'unit.id')
            ->where('drug.name', 'ilike', $name . '%')
            ->where('drug.drug_store_id', null)
            ->where('drug.is_master_data', 1)
            ->paginate($page_size);
        return $data;
    }

    public function getListDrugWithUnit($drug_store_id)
    {
        LogEx::methodName($this->className, 'getListDrugWithUnit');


        // Get master data: warehouse + unit
        $master_warehouse = DB::table('warehouse')
            ->select(
                'warehouse.drug_id',
                'warehouse.unit_id',
                'warehouse.id',
                'warehouse.is_basic',
                'warehouse.exchange',
                'warehouse.quantity',
                'warehouse.number',
                'warehouse.expiry_date',
                'warehouse.current_cost',
                'warehouse.is_check',
                'warehouse.main_cost',
                'unit.id as unit_id',
                'unit.name'
            )
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('drug_store_id', $drug_store_id)
            ->where('is_check', 0)
            ->orderBy('warehouse.drug_id')
            ->get();
        $drugId_master_warehouse = array();
        foreach ($master_warehouse as $item) {
            $drugId_master_warehouse[$item->drug_id][] = $item;
        }

        // minduc: ??? encode -> decode
        $drugId_master_warehouse = json_decode(json_encode($drugId_master_warehouse), true);
        // $data_result['current_page'] = $data['current_page'];
        // $data_result['first_page_url'] = $data['first_page_url'];
        // $data_result['from'] = $data['from'];
        // $data_result['last_page'] = $data['last_page'];
        // $data_result['to'] = $data['to'];
        // $data_result['total'] = $data['total'];
        // $data_result['path'] = $data['path'];
        // $data_result['last_page_url'] = $data['last_page_url'];
        // $data_result['next_page_url'] = $data['next_page_url'];
        // LogEx::printDebug($drugId_master_warehouse);
        $drugInfo = $dug_info = DB::table('drug')
            ->select(
                'drug.*'
            )
            ->where('drug_store_id', $drug_store_id)
            ->orderBy('id')
            ->get();

        $data_result = null;
        foreach ($drugInfo as $drugItem) {
            $dug_info = json_decode(json_encode($drugItem), true);
            if (array_key_exists($drugItem->id, $drugId_master_warehouse)) {
                foreach ($drugId_master_warehouse[$drugItem->id] as $wareItem) {
                    $dug_info['units'][] = $wareItem;
                    $data_result['data'][] = $dug_info;
                }
            }
        }

        return $data_result;


        /*$dug_info = DB::table('warehouse')
            ->select(
                'drug.*'
            )
            ->join('drug', 'drug.id', 'warehouse.drug_id')
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->where('warehouse.is_check', 0)
            ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.number', '!=', '')
            ->paginate(50);



        $dug_info = json_decode(json_encode($dug_info), true);
        $current_page = $dug_info['current_page'];
        $first_page_url = $dug_info['first_page_url'];
        $from = $dug_info['from'];
        $last_page = $dug_info['last_page'];
        $to = $dug_info['to'];
        $total = $dug_info['total'];
        $path = $dug_info['path'];
        $last_page_url = $dug_info['last_page_url'];
        $next_page_url = $dug_info['next_page_url'];
        $dug_info = $this->unique_multidim_array($dug_info['data'], 'id');


        foreach ($dug_info as $key => $value) {
            $value = json_decode(json_encode($value), true);
//            $check = Warehouse::select('warehouse.*')
//                ->where('drug_id', $value['id'])
//                ->where('is_check', 0)
//                ->get();
//            if (!empty($check)) {
            $number = array();
            $info_unit = DB::table('warehouse')
                ->select(
                    'warehouse.drug_id',
                    'warehouse.unit_id',
                    'warehouse.id',
                    'warehouse.is_basic',
                    'warehouse.exchange',
                    'warehouse.quantity',
                    'warehouse.number',
                    'warehouse.expiry_date',
                    'warehouse.current_cost',
                    'warehouse.is_check',
                    'warehouse.main_cost',
                    'unit.id as unit_id',
                    'unit.name'
                )
                ->join('unit', 'unit.id', 'warehouse.unit_id')
                ->where('warehouse.drug_store_id', $drug_store_id)
                ->where('warehouse.drug_id', $value['id'])
                ->where('warehouse.is_check', 0)
                ->where('warehouse.quantity', '>',0)
                ->get()->toArray();

            foreach ($info_unit as $item) {
                $number[] = $item->number;
            }
            $number = array_unique($number);
            foreach ($number as $i => $k) {
                $temp = array();
                foreach ($info_unit as $item) {
                    if ($item->number == $k) {
                        $temp[] = $item;
                    }
                }
                $value['units'] = $temp;
                $data_result['data'][] = $value;
            }


//                return $data_result;
//            }
        }
        $data_result['current_page'] = $current_page;
        $data_result['first_page_url'] = $first_page_url;
        $data_result['from'] = $from;
        $data_result['last_page'] = $last_page;
        $data_result['to'] = $to;
        $data_result['total'] = $total;
        $data_result['path'] = $path;
        $data_result['last_page_url'] = $last_page_url;
        $data_result['next_page_url'] = $next_page_url;

        return $data_result;*/
    }

    public function getDrugBase($drug_store_id, $name, $perpage)
    {
        LogEx::methodName($this->className, 'getDrugBase');

        $data_result = null;
        if ($perpage == '') {
            $size = 300;
        } else {
            $size = $perpage;
        }

        $data_result = Drug::with(['units' => function ($q) {
            $q->join('unit', 'unit.id', 'warehouse.unit_id');
            $q->where('is_check', 1);
        }])->select('drug.*')
            ->where('drug_store_id', $drug_store_id)
            ->where('name', 'like', "%$name%")
            ->orderBy('drug.id', 'desc')
            ->paginate($size);


        /*$list_drug = $this->findManyBy('drug_store_id',$drug_store_id);
        if (!empty($list_drug)) {
            foreach ($list_drug as $value) {
                $info_unit = DB::table('warehouse')
                    ->select(
                        'warehouse.unit_id',
                        'warehouse.is_basic',
                        'warehouse.exchange',
                        'warehouse.quantity',
                        'warehouse.main_cost',
                        'warehouse.expiry_date',
                        'unit.id as unit_id',
                        'unit.name'
                    )
                    ->join('unit', 'unit.id', 'warehouse.unit_id')
                    ->where('warehouse.drug_store_id', $drug_store_id)
                    ->where('warehouse.drug_id', $value->id)
                    ->where('warehouse.is_check', 1)
                    ->get();

                $value->units = $info_unit;
                $data_result[] = $value;
            }
        }*/
        // LogEx::printDebug($data_result);
        return $data_result;
    }

    public function getDrugByNumber($drug_id, $number)
    {
        LogEx::methodName($this->className, 'getDrugByNumber');

        $drug = $this->findOneById($drug_id);
        $info_unit = DB::table('warehouse')
            ->select(
                'warehouse.*',
                'unit.id as unit_id',
                'unit.name'
            )
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('warehouse.drug_id', $drug_id)
            ->where('warehouse.number', $number)
            ->get()->toArray();

        $drug['units'] = $info_unit;
        return $drug;
    }

    public function getListNumber($id)
    {
        LogEx::methodName($this->className, 'getListNumber');

        $data_result = array();
        $data = DB::table('warehouse')
            ->select(
                'warehouse.*'
            )
            ->where('drug_id', $id)
            ->where('is_check', 0)
            ->get()->toArray();
        if (!empty($data)) {
            foreach ($data as $value) {
                $data_result[] = $value->number;
            }
            $data_result = array_unique($data_result);
        }

        return $data_result;
    }

    /*
     * type = 1: orderby quantity
     * type = 2: orderby $
     * time = 1: today
     * time = 2: lastday
     * time = 3: last 7 days
     * time = 4: current month
     * time = 5: last month
     * drug_store_id
     * */
    public function getTopDrug($drug_store_id, $time, $type)
    {
        LogEx::methodName($this->className, 'getTopDrug');

        $query = InvoiceDetail::join('drug', 'drug.id', '=', 'invoice_detail.drug_id')
            ->join('drugstores as dt', 'dt.id', '=', 'drug.drug_store_id')
            ->join('warehouse', 'warehouse.drug_id', '=', 'invoice_detail.drug_id')
            ->join('invoice', 'invoice.id', '=', 'invoice_detail.invoice_id');
        if ($type == 1) {
            $query = $query->select('drug.name', \DB::raw('SUM(invoice_detail.quantity*warehouse.exchange) as quantity'));
        } else {
            $query = $query->select('drug.name', \DB::raw('SUM(invoice_detail.quantity*warehouse.exchange*warehouse.current_cost) as quantity'));
        }
        $query = $query->where('invoice.drug_store_id', $drug_store_id)
            ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.is_check', true)
            ->where('invoice.invoice_type', 'IV1');
        if ($time == 1) {
            $today = Carbon::tomorrow()->format('Y-m-d');
            $yesterday = Carbon::yesterday()->format('Y-m-d');
            $query = $query->where('invoice_detail.created_at', '>', $yesterday)
                ->where('invoice_detail.created_at', '<', $today);
        } elseif ($time == 2) {
            $date = Carbon::tomorrow()->format('Y-m-d');
            $query = $query->where('invoice_detail.created_at', $date);
        } elseif ($time == 3) {
            $start = Carbon::now()->subDay(7)->format('Y-m-d');
            $end = Carbon::now()->format('Y-m-d');
            $query = $query->where('invoice.created_at', '>=', $start)->where('invoice.created_at', '<=', $end);
        } elseif ($time == 4) {
            $start = Carbon::now()->startOfMonth()->format('Y-m-d');
            $end = Carbon::now()->format('Y-m-d');
            $query = $query->where('invoice_detail.created_at', '>=', $start)->where('invoice_detail.created_at', '<=', $end);
        } elseif ($time == 5) {
            $start = Carbon::now()->startOfMonth()->subMonth()->format('Y-m-d');
            $end = Carbon::now()->endOfMonth()->subMonth()->format('Y-m-d');
            $query = $query->where('invoice_detail.created_at', '>=', $start)->where('invoice_detail.created_at', '<=', $end);
        }
        $query = $query->groupBy('drug.name')->orderBy('quantity', 'DESC')->take(10)->get();
        return $query;
    }

    public function getListDrugStore($drug_store_id)
    {
        LogEx::methodName($this->className, 'getListDrugStore');

        $data = $this->findManyBy('drug_store_id', $drug_store_id);
        return $data;
    }

    function unique_multidim_array($array, $key)
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

    public function getDrugForDose($drug_id, $unit_id)
    {
        LogEx::methodName($this->className, 'getDrugForDose');

        $data = DB::table('warehouse')
            ->select(
                'warehouse.drug_id',
                'warehouse.unit_id',
                'warehouse.id',
                'warehouse.is_basic',
                'warehouse.exchange',
                'warehouse.quantity',
                'warehouse.number',
                'warehouse.expiry_date',
                'warehouse.current_cost',
                'warehouse.is_check',
                'warehouse.main_cost',
                'unit.id as unit_id',
                'unit.name',
                'drug.name as drug_name'
            )
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->join('drug', 'drug.id', 'warehouse.drug_id')
            ->where('warehouse.drug_id', $drug_id)
            ->where('warehouse.unit_id', $unit_id)
            ->where('warehouse.is_check', 0)
            ->where('warehouse.quantity', '>=', 1)
            ->get();
        return $data;
    }

    public function getDrugBaseById($drug_id)
    {
        LogEx::methodName($this->className, 'getDrugBaseById');

        $data_result = Drug::with(['units' => function ($q) {
            $q->join('unit', 'unit.id', 'warehouse.unit_id');
            $q->where('is_check', 1);
        }])->select('drug.*')
            ->where('id', $drug_id)
            ->first();

        return $data_result;
    }

    // Autocomplete data in import drug page
    public function getAutoListWithPacks(string $drug_store_id, int $modeSearch, string $inputText)
    {
        LogEx::methodName($this->className, 'getAutoListWithPacks');

        $orderByStr = Utils::createOrderByString($inputText, 'drug.name');
        $query = DB::table('drug')
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY ' . $orderByStr . ') as row_number'),
                'drug.id',
                'drug.name',
                'drug.short_name',
                'drug.package_form',
                'drug.company',
                'drug.drug_code',
                // 'drug_autosearch.main_cost',
                // 'drug_autosearch.current_cost',
                'drug.country',
                'drug.active',
                'drug.image',
                'drug.description',
                'drug.registry_number'
            )
            ->join('drug_autosearch', function ($join) {
                $join->on('drug_autosearch.drug_id', '=', 'drug.id');
                // Performance conditioner
                //$join->on('drug_autosearch.drug_store_id', '=', $drug_store_id);
            })
            ->where('drug_autosearch.drug_store_id', $drug_store_id)
            ->where('drug.drug_store_id', $drug_store_id)
            ->where('drug.active', 'yes');

        // ->where('drug.name', 'ilike', '%' . $inputText . '%')
        if ($modeSearch == 0) {
            $query = $query->whereRaw(Utils::build_query_AutoCom_search($inputText, 'drug_autosearch.full_search'));
        } else {
            $query = $query->whereRaw(Utils::build_query_AutoCom_search($inputText, 'drug_autosearch.name_pharma_properties'));
        }

        $data = $query->orderByRaw($orderByStr)->limit(100)->get();

        return $data;
    }

    // Autocomplete favorite data in import drug page
    public function getAutoListFavorite(string $drug_store_id, string $inputText)
    {
        LogEx::methodName($this->className, 'getAutoListFavorite');

        $query = DB::table('drug')
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY drug.name) as row_number'),
                'drug.id',
                'drug.name',
                'drug.short_name',
                'drug.package_form',
                'drug.company',
                'drug.drug_code',
                'drug.country',
                'drug.active',
                'drug.image',
                'drug.description',
                'drug.registry_number'
            )
            ->where('drug.drug_store_id', $drug_store_id)
            ->where('drug.active', 'yes')
            ->where('drug.is_favorite', true)
            ->orderBy('drug.name');

        $data = $query->limit(100)->get();

        return $data;
    }

    public function addDrug(Request $request)
    {
        LogEx::methodName($this->className, 'addDrug');

        $userInfo = $request->userInfo;
        $inputData = $request->input();
        $data = 0;
        DB::beginTransaction();
        try {
            $img_base64 = isset($inputData['image']) ? $inputData['image'] : '';
            if (!empty($img_base64) && preg_match('/^data:image/', $img_base64)) {
                $img = Utils::generateImageFromBase64($img_base64);
                $inputData['image'] = url("upload/images/" . $img);
            }

            Utils::createTempTableFromRequestInput($inputData);
            $data = DB::select('select f_drug_add(?) as result', [$userInfo->drug_store_id]);
            $data = $data[0]->result;
            if ($data < 0) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }

    public function editDrug($drug_id, Request $request)
    {
        LogEx::methodName($this->className, 'editDrug');

        $userInfo = $request->userInfo;
        $inputData = $request->input();
        $data = 0;
        DB::beginTransaction();
        try {
            $img_base64 = isset($inputData['image']) ? $inputData['image'] : '';
            if (!empty($img_base64) && preg_match('/^data:image/', $img_base64)) {
                $img = Utils::generateImageFromBase64($img_base64);
                $inputData['image'] = url("upload/images/" . $img);
            }

            Utils::createTempTableFromRequestInput($inputData);
            $data = DB::select('select f_drug_edit(?, ?) as result', [$drug_id, $userInfo->drug_store_id]);
            $data = $data[0]->result;
            if ($data < 0) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }

    public function updateUnit($drug_id, Request $request)
    {
        LogEx::methodName($this->className, 'updateUnit');

        $userInfo = $request->userInfo;
        $inputData = $request->input();
        $data = 0;
        DB::beginTransaction();
        try {
            Utils::createTempTable(
                array('unit', 'exchange', 'price', 'is_basic'),
                $inputData['units']
            );
            $data = DB::select('select f_drug_update_unit(?, ?) as result', [$drug_id, $userInfo->drug_store_id]);
            $data = $data[0]->result;
            if ($data < 0) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }

    public function updateWarning($drug_id, Request $request)
    {
        LogEx::methodName($this->className, 'updateWarning');

        $userInfo = $request->userInfo;
        $inputData = $request->input();
        $data = 0;
        DB::beginTransaction();
        try {
            $data = DB::select('select f_drug_update_warning(?, ?, ?, ?, ?, ?) as result', [$drug_id, $userInfo->drug_store_id, $inputData['warning_unit'], $inputData['warning_quantity_min'], $inputData['warning_quantity_max'], $inputData['warning_days'],]);
            $data = $data[0]->result;
            if ($data < 0) {
                DB::rollBack();
                return $data;
            }
            DB::commit();
        } catch (\Exception $e) {
            LogEx::error($e);
            DB::rollback();
        }
        return $data;
    }

    public function drugsInGroup($id, Request $request)
    {
        LogEx::methodName($this->className, 'drugsInGroup');
        return Utils::executeRawQuery('select * from f_drug_in_group(?)', [Utils::getParams($request->input(), array('group_id' => $id))], $request->url(), $request->input());
    }

    /**
     * api v3
     * from f_drug_in_group on v1
    */
    public function drugsInGroupV3($id, Request $request)
    {
        LogEx::methodName($this->className, 'drugsInGroup');
        return Utils::executeRawQuery('select * from f_drug_in_group(?)', [Utils::getParams($request->input(), array('group_id' => $id))], $request->url(), $request->input());
    }

    public function drugsInCategory($id, Request $request)
    {
        LogEx::methodName($this->className, 'drugsInCategory');

	    $p_store_id = $request->userInfo->drug_store_id;
		$p_drug_group_id = $id;
		$p_drug_name = Utils::coalesce($request->input(), 'drug_name', null);

        $query = DB::table(DB::raw('drug d'))
            ->select(
                'd.id',
                'd.name',
                'd.drug_code',
                'c.name as category_name',
                'w.unit_id',
                'u.name as unit_name',
                DB::raw('sum(q.quantity) as quantity')
            )
            ->join(DB::raw('warehouse w'), function($join) {
                $join->on('w.drug_id','=','d.id')
                    ->whereRaw('w.is_check = true')
                    ->where('w.is_basic','=','yes');
            })
            ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
            ->leftJoin(DB::raw('warehouse q'), function($join) {
                $join->on('q.drug_id','=','d.id')
                    ->whereRaw('q.is_check = false')
                    ->where('q.is_basic','=','yes');
            })
            ->leftJoin(DB::raw('drug_category c'), function($join) use ($p_store_id) {
                $join->on('c.id', '=', 'd.drug_category_id')
                    ->where('c.drug_store_id', '=', $p_store_id);
            })
            ->where('d.drug_store_id', '=', $p_store_id)
            ->where('d.drug_group_id', '=', $p_drug_group_id)
            ->where('d.active','=','yes')
            ->when(!empty($p_drug_name), function ($query) use ($p_drug_name) {
                $query->where(
                    (DB::raw('lower(vn_unaccent(d.name))')),
                    'ILIKE',
                    '%' . strtolower(Utils::unaccent($p_drug_name)) . '%');
            })
            ->groupBy(['d.id','d.name','d.drug_code','c.name','w.unit_id','u.name']);

        return Utils::executeRawQueryV3(
            $query,
            $request->url(),
            $request->input()
        );
    }

    public function drugList(Request $request)
    {
        LogEx::methodName($this->className, 'drugList');
        $requestInput = $request->input();
        Utils::createTempTableFromRequestInput($requestInput);
        return Utils::executeRawQuery('select * from f_drug_list(?)', [$request->userInfo->drug_store_id], $request->url(), $requestInput);
    }

    public function updateStatus($drugId, $status, $userInfo)
    {
        $now = Carbon::now();
        LogEx::methodName($this->className, 'updateStatus');
        DB::select('update drug set active = ?, updated_at = ? where id = ? and drug_store_id = ? and ? in (\'yes\', \'no\')', [$status, $now, $drugId, $userInfo->drug_store_id, $status]);
    }

    public function getDrugListES($param = '')
    {
        $url = env('ES_URL') . 'drugs/search?' . $param;
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $url);
        $data = json_decode($res->getBody(), true);
        return $data['body'];
    }

    public function copyDrugByStoreId($storeId, $destStoreId)
    {
        $now = Carbon::now();
        DB::insert("insert into drug (copy_id, drug_store_id, drug_category_id, usage, unit_id, is_master_data, drug_group_id, drug_code, barcode, name, short_name, substances, concentration, country, company, registry_number, package_form, expiry_date, description, image, vat, active, updated_at, created_at, is_favorite) select d.id, " . $destStoreId . ", c.id, d.usage, d.unit_id, d.is_master_data, g.id, d.drug_code, d.barcode, d.name, d.short_name, d.substances, d.concentration, d.country, d.company, d.registry_number, d.package_form, d.expiry_date, d.description, d.image, d.vat, d.active, '" . $now . "', '" . $now . "', d.is_favorite from drug d
        left outer join drug_category c
            on  c.copy_id    = d.drug_category_id
            and c.drug_store_id = " . $destStoreId . "
        left outer join drug_group g
            on  g.copy_id    = d.drug_group_id
            and g.drug_store_id = " . $destStoreId . "
    where   d.drug_store_id = " . $storeId);
    }

    public function countDrugByStoreId($storeId)
    {
        $count = DB::select("select count(*) from drug where drug_store_id = " . $storeId);
        return $count[0]->count;
    }
}
