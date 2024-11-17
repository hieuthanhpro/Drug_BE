<?php

namespace App\Repositories\Warehouse;

use app\libextension\logex;
use App\LibExtension\Utils;
use App\Models\Warehouse;
use App\Repositories\AbstractBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Request;

class WarehouseEloquentRepository extends AbstractBaseRepository implements WarehouseRepositoryInterface
{
    protected $className = "WarehouseEloquentRepository";

    public function __construct(Warehouse $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function updateAmount($drug_id, $quantity, $type = null, $number, $isCreateInvoice = false)
    {
        LogEx::methodName($this->className, 'updateAmount');

        $unit_drug = $this->findManyByCredentials(['drug_id' => $drug_id, 'number' => $number]);

        // Fix TH làm tròn không bán về 0 được
        // Solution: So sánh quantity vs quantity ở đơn vị cơ bản
        $basicUnit = null;
        foreach ($unit_drug as $item) {
            if ($item->is_basic == 'yes') {
                $basicUnit = $item;
            }
        }
        // End fix TH làm tròn không bán về 0 được

        $flag = false; // Dùng để check TH bán quá
        foreach ($unit_drug as $item) {
            $quantity_update = 0;
            if ($item->is_basic == 'yes') {
                if ($type == null) {
                    if ($isCreateInvoice) { // Chỉ check TH bán để ko ảnh hưởng nơi khác
                        if ($item->quantity < $quantity) {
                            $flag = true;
                            break;
                        }
                    }
                    $quantity_update = $item->quantity - $quantity;
                } else {
                    $quantity_update = $item->quantity + $quantity;
                }

                $this->updateOneById($item->id, ['quantity' => $quantity_update]);
            } else {

                $quantity_exchange = $quantity / $item->exchange;

                if ($type == null) {
                    if ($isCreateInvoice) { // Chỉ check TH bán để ko ảnh hưởng nơi khác
                        // Fix TH làm tròn không bán về 0 được
                        if (!empty($basicUnit)) {
                            if ($basicUnit->quantity < $quantity) {
                                $flag = true;
                                break;
                            }
                            // End fix TH làm tròn không bán về 0 được
                        } else {
                            if ($item->quantity < $quantity_exchange) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                    // Fix TH làm tròn không bán về 0 được
                    if ($basicUnit->quantity == $quantity) {
                        $quantity_update = 0;
                    } elseif ($item->quantity < $quantity_exchange) {
                        $quantity_update = 0;
                    } else {
                        $quantity_update = $item->quantity - $quantity_exchange;
                    }
                    // End fix TH làm tròn không bán về 0 được
                    // $quantity_update = $item->quantity - $quantity_exchange;
                } else {
                    $quantity_update = $item->quantity + $quantity_exchange;
                }
                $this->updateOneById($item->id, ['quantity' => $quantity_update]);
            }

            // Ghi log
            $log = new \App\Models\NewWarehouseLog;
            $ref = \Request::getRequestUri();
            $action = 'updateAmount';
            $oldVal = $item->quantity;
            $newVal = $quantity_update;
            $desc = "Thay đổi quantity từ {$oldVal} sang {$newVal} (type: {$type})";
            $log->pushQuantityLog($item->id, $item->drug_store_id, $ref, $action, $oldVal, $newVal, $desc);
        }
        if ($flag) {
            return false; // Nếu có bán quá thì return false
        }
        return true;
    }

    public function updateInvoiceAmount($drug_id, $number, $quantityBasicUnit, $decrease_flag = false, $ignore_negative = false)
    {
        LogEx::methodName($this->className, 'updateInvoiceAmount');
        // Warehouse by drug ID and batch number ???
        $listWarehouse = $this->findManyByCredentials(['drug_id' => $drug_id, 'number' => $number]);

        // Đánh dấu tăng hoặc giảm số lượng
        $sign = 1;
        if ($decrease_flag == true) {
            $sign = -1;
        }

        $quantityNotEnoughFlag = false;
        foreach ($listWarehouse as $item) {
            $quantity_update = 0;
            if ($item->is_basic == 'yes') {
                $quantity_update = $item->quantity + $sign * $quantityBasicUnit;
                if ($quantity_update < 0 && $ignore_negative !== true) {
                    $quantityNotEnoughFlag = true;
                    break;
                }
            } else {
                $quantity_update = $item->quantity + $sign * $quantityBasicUnit / $item->exchange;
                if ($quantity_update < 0) {
                    $quantity_update = 0;
                }
            }
            $this->updateOneById($item->id, ['quantity' => $quantity_update]);

            // Ghi log
            $log = new \App\Models\NewWarehouseLog;
            $ref = \Request::getRequestUri();
            $action = 'updateAmount';
            $oldVal = $item->quantity;
            $newVal = $quantity_update;
            $desc = "Thay đổi quantity từ {$oldVal} sang {$newVal}";
            $log->pushQuantityLog($item->id, $item->drug_store_id, $ref, $action, $oldVal, $newVal, $desc);
        }
        if ($quantityNotEnoughFlag) {
            return false;
        }
        return true;
    }

    public function updateCosts($drug_id, $pre_cost_basic, $main_cost_basic, $current_cost_basic)
    {
        LogEx::methodName($this->className, 'updateCosts');

        $now = Carbon::now()->format('Y-m-d H:i:s');
        // update basic pre_cost, main_cost
        // DB::statement('update warehouse set pre_cost = round(' . $pre_cost_basic . ' * exchange), main_cost = round(' . $main_cost_basic . ' * exchange), updated_at = \'' . $now . '\' where drug_id = ? and is_check = true', [$drug_id]);

        DB::statement('update warehouse set pre_cost = round(' . $pre_cost_basic . ' * exchange), main_cost = round(' . $main_cost_basic . ' * exchange), current_cost = round(' . $current_cost_basic . ' * exchange), updated_at = \'' . $now . '\' where drug_id = ? ', [$drug_id]);

        return true;
    }

    public function creareByNumber($data, $drug_store_id)
    {
        LogEx::methodName($this->className, 'creareByNumber');

        $drug_unit = $this->findManyByCredentials(['drug_id' => $data['drug_id'], 'is_check' => 1]);
        // Log::info("danh sách unit thuốc: " . json_encode($drug_unit));
        $data_unit = $this->findOneByCredentials(['drug_id' => $data['drug_id'], 'is_check' => 1, 'unit_id' => $data['unit_id']]);
        // Log::info("dữ liệu truyền vào: " . json_encode($data_unit));

        $current_cost = str_replace(',', '', $data['current_cost']);
        $main_cost = str_replace(',', '', $data['main_cost']);

        if (!empty($data_unit->is_basic) || !empty($data_unit->exchange)) {

            if ($data_unit->is_basic == 'yes') {
                    $quantity = $data['quantity'];
                    $cost = $current_cost;
                    $main_cost = $main_cost;
            } else {
                $quantity = $data['quantity'] * $data_unit->exchange;
                $cost = $current_cost / $data_unit->exchange;
                $main_cost = $main_cost / $data_unit->exchange;
            }

            /*update curent cost all number*/
            // $drug_all = $this->findManyByCredentials(['drug_id' => $data['drug_id']]);

            // foreach ($drug_all as $value) {
            //     if ($value->is_basic == 'yes') {
            //         $update = $cost;
            //     } else {
            //         $update = $cost * $value->exchange;
            //     }
            //     $this->updateOneById($value->id, ['current_cost' => $update]);
            // }

            foreach ($drug_unit as $item) {
                if ($item->is_basic == 'yes') {
                    $data['is_check'] = 0;
                    $data['is_basic'] = 'yes';
                    $data['exchange'] = $item->exchange;
                    $data['unit_id'] = $item->unit_id;
                    $data['quantity'] = $quantity;
                    $data['drug_store_id'] = $drug_store_id;
                    $data['current_cost'] = $cost;
                    $data['main_cost'] = $main_cost;
                    // $this->updateOneById($item->id, ['current_cost' => $cost]);

                    $temp = $this->create($data);
                } else {
                    $data['quantity'] = $quantity / $item->exchange;
                    $data['is_check'] = 0;
                    $data['is_basic'] = 'no';
                    $data['exchange'] = $item->exchange;
                    $data['unit_id'] = $item->unit_id;
                    $data['drug_store_id'] = $drug_store_id;
                    $data['current_cost'] = $cost * $item->exchange;
                    $data['main_cost'] = $main_cost * $item->exchange;
                    // $this->updateOneById($item->id, ['current_cost' => $cost * $item->exchange]);

                    $temp = $this->create($data);
                }
            }

            return true;
        }

        return false;
    }

    public function updateAmountByUnit($data)
    {
        LogEx::methodName($this->className, 'updateAmountByUnit');

        $drug_unit = $this->findOneByCredentials(['drug_id' => $data['drug_id'], 'unit_id' => $data['unit_id']]);
        if ($drug_unit->is_basic == 'yes') {
            $quality = $data['amount'];
        } else {
            $quality = $data['amount'] * $drug_unit->exchange;
        }

        $warehoure = $this->findManyByCredentials(['drug_id' => $data['drug_id'], 'number' => $data['number']]);
        foreach ($warehoure as $value) {
            if ($value->is_basic == 'yes') {
                $this->updateOneById($value->id, ['quantity' => $quality]);
            } else {
                $this->updateOneById($value->id, ['quantity' => round($quality / $value->exchange, 1)]);
            }
        }
        return true;
    }

    public function countQuantityByDrug($drug_store_id, $drug_id, $unit_id)
    {
        LogEx::methodName($this->className, 'countQuantityByDrug');

        $count = DB::table('warehouse')
            ->select('drug_id', 'unit_id', DB::raw('sum(quantity) as total'))
            ->groupBy('drug_id')
            ->groupBy('unit_id')
            ->where('drug_store_id', $drug_store_id)
            ->where('drug_id', $drug_id)
            ->where('unit_id', $unit_id)
            ->first();
        return $count;
    }

    // Get list of basic packages for drug (import page)
    public function getListPackages(string $drug_store_id, string $drug_id)
    {
        LogEx::methodName($this->className, 'getListPackages');

        $data = DB::table('warehouse')
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY warehouse.id) as row_number'),
                'warehouse.drug_id',
                'warehouse.main_cost',
                'warehouse.is_basic',
                'warehouse.exchange',
                'warehouse.main_cost',
                'warehouse.current_cost',
                'warehouse.unit_id',
                'unit.name as unit_name'
            )
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('warehouse.is_check', 1)
            // ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->where('warehouse.drug_id', $drug_id)
            ->get();

        return $data;
    }

    // Autocomplete data in sale drug page
    public function getAutoListWithPacks4Sale(string $drug_store_id, $modeSearch, string $inputText)
    {
        LogEx::methodName($this->className, 'getAutoListWithPacks4Sale');

        $orderByStr = Utils::createOrderByString($inputText, 'drug.name') . ', warehouse.expiry_date, warehouse.quantity desc';

        $searchCondition = '';
        if ($modeSearch == 0) {
            $searchCondition = Utils::build_query_AutoCom_search($inputText, 'drug_autosearch.full_search');
        } else {
            $searchCondition = Utils::build_query_AutoCom_search($inputText, 'drug_autosearch.name_pharma_properties');
        }
        if (strpos($inputText, ' ') === false) {
            $m = null;
            preg_match('/^((DQG|DRUG|SP)[0-9]+)(_([^ \r\n\t]+))?$/', $inputText, $m);
            LogEx::info($m);
            if (isset($m) && count($m) > 0) {
                $tmp = $searchCondition;
                $searchCondition = " ( ";
                if (count($m) < 5) {
                    $searchCondition .= " (" . $tmp . " ) ";
                } else {
                    $searchCondition .= " drug.drug_code = '$m[1]' ";
                    $searchCondition .= " and warehouse.number = '$m[4]' ";
                }
                $searchCondition .= " ) ";
                $orderByStr = " case when drug.drug_code = '$m[1]' then 0 when left(drug.drug_code, length('$m[1]')) = '$m[1]' then length(drug.drug_code) else 999999 end, " . $orderByStr;
            } else {
                if ($searchCondition && $searchCondition != '') {
                    $searchCondition = " ( (" . $searchCondition . " ) ";
                    $searchCondition .= " or warehouse.number = '" . $inputText . "'";
                } else {
                    $searchCondition .= "( warehouse.number = '" . $inputText . "'";
                }
                if (is_numeric($inputText)) {
                    $searchCondition .= " or warehouse.id = '" . $inputText . "'";
                }
                $searchCondition .= " ) ";
            }
        }

        $query = DB::table('drug')
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY ' . $orderByStr . ') as row_number'),
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
                'unit.name as unit_name'
            )
            ->join('drug_autosearch', function ($join) {
                $join->on('drug_autosearch.drug_id', '=', 'drug.id');
                // Performance conditioner
                //$join->on('drug_autosearch.drug_store_id', '=', $drug_store_id);
            })
            ->join('warehouse', 'warehouse.drug_id', 'drug.id')
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('drug_autosearch.drug_store_id', $drug_store_id)
            ->where('drug.drug_store_id', $drug_store_id)
            ->where('drug.active', 'yes')
            // ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.is_check', 'no')
            ->where('warehouse.quantity', '>=', 1);

        $query = $query->whereRaw($searchCondition);

        // ->orderBy('drug.is_favorite', 'desc')
        $data = $query->orderByRaw($orderByStr)->limit(100)->get();

        return $data;
    }

    // Get favorite drug in sale drug page
    public function getAutoListWithPacks4SaleFavorite(string $drug_store_id, $request)
    {
        LogEx::methodName($this->className, 'getAutoListWithPacks4SaleFavorite');

        $search = Utils::coalesce($request->input(), 'query', null);
        $data = DB::table('drug')
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
                'unit.name as unit_name'
            )
            ->join('warehouse', 'warehouse.drug_id', 'drug.id')
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('drug.drug_store_id', $drug_store_id)
            ->where('drug.active', 'yes')
            // ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.is_check', 'no')
            ->where('warehouse.quantity', '>=', 1)
            ->where('warehouse.is_basic', 'yes')
            //->where('drug.is_favorite', true)
            ->when($search, function ($query) use ($search) {
                $name = trim($search);
                $whereRaw = "1 = 1 AND (drug.name ~* '" . $name . "')";
                $query->whereRaw($whereRaw);
            })
            ->orderBy('drug.name')
            ->limit(100)
            ->get();

        return $data;
    }

    public function getListPackages4Sale(string $drug_store_id, string $drug_id)
    {
        LogEx::methodName($this->className, 'getListPackages4Sale');

        $data = DB::table('warehouse')
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY warehouse.expiry_date, warehouse.exchange) as row_number'),
                'warehouse.drug_id',
                'warehouse.number',
                'warehouse.quantity',
                'warehouse.main_cost',
                'warehouse.current_cost',
                'warehouse.is_basic',
                'warehouse.exchange',
                'warehouse.unit_id',
                'warehouse.expiry_date',
                'unit.name as unit_name'
            )
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('warehouse.is_check', 'no')
            // ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->where('warehouse.drug_id', $drug_id)
            ->where('warehouse.quantity', '>=', 1)
            ->orderByRaw('warehouse.expiry_date, warehouse.exchange')
            ->get();

        return $data;
    }

    public function getListPackages4SaleByDrugIds($drug_store_id, $drug_ids)
    {
        LogEx::methodName($this->className, 'getListPackages4SaleByDrugIds');

        $data = DB::table('warehouse')
            ->select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY warehouse.expiry_date, warehouse.exchange) as row_number'),
                'warehouse.drug_id',
                'warehouse.number',
                'warehouse.quantity',
                'warehouse.main_cost',
                'warehouse.current_cost',
                'warehouse.is_basic',
                'warehouse.exchange',
                'warehouse.unit_id',
                'warehouse.expiry_date',
                'unit.name as unit_name'
            )
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('warehouse.is_check', 'no')
            // ->where('warehouse.is_basic', 'yes')
            ->where('warehouse.drug_store_id', $drug_store_id)
            ->whereIn('warehouse.drug_id', $drug_ids)
            ->where('warehouse.quantity', '>=', 1)
            ->orderByRaw('warehouse.expiry_date, warehouse.exchange')
            ->get();

        return $data;
    }

    public function getStockList(Request $request)
    {
        LogEx::methodName($this->className, 'getStockList');
        $requestInput = $request->input();
        Utils::createTempTableFromRequestInput($requestInput);
        $data = Utils::executeRawQuery('select * from f_warehouse_stock(?)', [$request->userInfo->drug_store_id], $request->url(), $requestInput);

        return Utils::getSumData($data, $requestInput, 'select count(distinct t.drug_code) as count, sum(t.total_buy) as total_buy, sum(t.total_sell) as total_sell from tmp_output t');
    }

    /**
     * api v3
     * from getStockList, f_warehouse_stock on v1
    */
    public function getStockListV3(Request $request, $export = 0, $limit = 10)
    {
        LogEx::methodName($this->className, 'getStockList');

        $p_store_id = $request->userInfo->drug_store_id;
        $search = $request->input('query');
        $query = DB::table(DB::raw('warehouse w'))
            ->select(
                DB::raw('row_number() over (order by d.name, w.expiry_date) as row_number'),
                'd.drug_code',
                'd.name as drug_name',
                'w.number',
                'w.expiry_date as expiry_date',
                'w.unit_id',
                'u.name as unit_name',
                'w.quantity',
                'w.main_cost',
                'w.current_cost',
                'd.vat',
                'd.package_form',
                DB::raw('w.main_cost * w.quantity as total_buy'),
                DB::raw('w.current_cost * w.quantity as total_sell')
            )
            ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
            ->join(DB::raw('drug d'), function($join) {
                $join->on('d.id','=','w.drug_id')
                    ->where('d.active','=','yes');
            })
            ->where('d.drug_store_id', '=', $p_store_id)
            ->where('w.drug_store_id', '=', $p_store_id)
            ->whereRaw('w.is_check = false')
            ->where('w.is_basic','=','yes')
            ->where('w.quantity','>=',1)
            ->when(!empty($search), function ($query) use ($search) {
                $name = trim($search);
                $whereRaw = "1 = 1 AND (d.drug_code ~* '" . $name
                    . "' or w.number  ~* '" . $name
                    . "' or d.name  ~* '" . $name
                    . "' or d.package_form  ~* '" . $name
                    ."')";
                $query->whereRaw($whereRaw)
                    ->when(is_numeric(gettype($search)), function ($query) use ($search) {
                        $query->orWhere((DB::raw('w.current_cost')),
                            '=',
                            trim($search));
                    });
            })
            ->orderBy('d.name','ASC')
            ->orderBy('w.expiry_date','ASC');

        $countProduct = DB::table(DB::raw('warehouse w'))
            ->select('d.drug_code')
            ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
            ->join(DB::raw('drug d'), function($join) {
                $join->on('d.id','=','w.drug_id')
                    ->where('d.active','=','yes');
            })
            ->where('d.drug_store_id', '=', $p_store_id)
            ->where('w.drug_store_id', '=', $p_store_id)
            ->whereRaw('w.is_check = false')
            ->where('w.is_basic','=','yes')
            ->where('w.quantity','>=',1)
            ->when(!empty($search), function ($query) use ($search) {
                $name = trim($search);
                $whereRaw = "1 = 1 AND (d.drug_code ~* '" . $name
                    . "' or w.number  ~* '" . $name
                    . "' or d.name  ~* '" . $name
                    . "' or d.package_form  ~* '" . $name
                    ."')";
                $query->whereRaw($whereRaw)
                    ->when(is_numeric(gettype($search)), function ($query) use ($search) {
                        $query->orWhere((DB::raw('w.current_cost')),
                            '=',
                            trim($search));
                    });
            })
            ->groupBy('d.drug_code')
            ->get()
            ->count();
        $queries = $query;
        $query_sum = $query
            ->get()
            ->toArray();
        $data = Utils::executeRawQueryV3(
            $queries,
            $request->url(),
            $request->input(),
            $export,
            $limit
        );
        $sum_data = [
            'count' => $countProduct,
            'total_buy' => array_sum(array_column($query_sum, 'total_buy')),
            'total_sell' => array_sum(array_column($query_sum, 'total_sell'))
        ];

        return Utils::getSumDataV3($data, $request->input(), $sum_data);
    }

    public function getWarehouseInOut(Request $request)
    {
        LogEx::methodName($this->className, 'getWarehouseInOut');

        $drugStoreId = $request->userInfo->drug_store_id;
        $input = $request->input();
        $fromDate = isset($input['fromDate']) ? $input['fromDate'] : (isset($input['from_date']) ? $input['from_date'] : null);
        $toDate = isset($input['toDate']) ? $input['toDate'] : (isset($input['to_date']) ? $input['to_date'] : null);
        $searchStr = isset($input['search']) ? $input['search'] : null;

        $data = Utils::executeRawQuery('select * from f_stockym_get_data(?, ?, ?, null, ?)', [$drugStoreId, $fromDate, $toDate, $searchStr], $request->url(), $input);
        return Utils::getSumData($data, $input, 'select sum(t.startamount) as startamount, sum(t.inamount) as inamount, sum(t.outamount) as outamount, sum(t.endamount) as endamount from tmp_output t');
    }

    public function getDashboardChartData($drugStoreId)
    {
        LogEx::methodName($this->className, 'getDashboardChartData');

        $data = DB::select('select * from f_dashboard_get_chart_data(?)', [$drugStoreId]);
        return json_decode(str_replace(['{', '}'], ['[', ']'], json_decode(json_encode($data[0]), true)['f_dashboard_get_chart_data']));
    }

    public function getWarningQuantityItems($drugStoreId)
    {
        LogEx::methodName($this->className, 'getWarningQuantityItems');

        $data = DB::select('select * from f_dashboard_get_warning_quantity_drug(?)', [$drugStoreId]);
        return $data;
    }

    public function getWarningDateItems($drugStoreId)
    {
        LogEx::methodName($this->className, 'getWarningDateItems');

        $data = DB::select('select * from f_dashboard_get_warning_date_drug(?)', [$drugStoreId]);
        return $data;
    }

    public function copyWarehouseByStoreId($storeId, $destStoreId)
    {
        $now = Carbon::now();
        DB::statement("insert into warehouse (drug_store_id, drug_id, unit_id, is_basic, exchange, quantity, main_cost, pre_cost, is_check, number, expiry_date, current_cost, warning_quantity, manufacturing_date, updated_at, created_at) select " . $destStoreId . ", d.id, w.unit_id, w.is_basic, w.exchange, w.quantity, w.main_cost, w.pre_cost, w.is_check, w.number, w.expiry_date, w.current_cost, w.warning_quantity, w.manufacturing_date, '" . $now . "', '" . $now . "' from    warehouse w
        inner join drug d
            on  d.copy_id   = w.drug_id
    where   w.drug_store_id = " . $storeId . "
    and     is_check        = true
    and     d.drug_store_id = " . $destStoreId);
    }

    public function countWarehouseByStoreId($storeId)
    {
        $count = DB::select("select count(distinct drug_id) from warehouse where drug_store_id = " . $storeId . " and is_check = false and is_basic = 'yes'");
        return $count[0]->count;
    }

    public function filter($warehouseFilterRequest, $drugStoreId, $limit = null)
    {
        LogEx::methodName($this->className, 'filter');
        $limit = $limit ?? $warehouseFilterRequest["limit"] ?? 10;
        $searchText = $warehouseFilterRequest["query"] ?? null;
        $queryDB = '1 = 1';

        if (isset($searchText)) {
            $queryDB = $queryDB . " AND (vn_unaccent(drug.name) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(drug.drug_code) ILIKE '%" . Utils::unaccent($searchText) . "%'";
            $queryDB = $queryDB . "OR vn_unaccent(warehouse.number) ILIKE '%" . Utils::unaccent($searchText) . "%')";
        }

        return DB::table("warehouse")
            ->select(
                'warehouse.id',
                'drug.drug_code',
                DB::raw('drug.name as drug_name'),
                'warehouse.number',
                'warehouse.expiry_date',
                'warehouse.unit_id',
                DB::raw('unit.name as unit_name'),
                'warehouse.quantity',
                'warehouse.main_cost',
                'warehouse.current_cost',
                'drug.package_form',
                DB::raw("warehouse.main_cost * warehouse.quantity as total_buy"),
                DB::raw("warehouse.current_cost * warehouse.quantity as total_sell")
            )
            ->join('drug', function ($join) {
                $join->on('drug.id', '=', 'warehouse.drug_id')
                    ->where('drug.active', '=', 'yes');
            })
            ->join("unit", "unit.id", "warehouse.unit_id")
            ->where('warehouse.drug_store_id', $drugStoreId)
            ->where('drug.drug_store_id', $drugStoreId)
            ->where("warehouse.is_check", "=", false)
            ->where("warehouse.is_basic", "=", "yes")
            ->where("warehouse.quantity", ">=", 1)
            ->whereRaw($queryDB)
            ->orderBy("drug.name")
            ->orderBy("warehouse.expiry_date")
            ->paginate($limit);
    }

    public function getUnits($drugId, $drugStoreId)
    {
        LogEx::methodName($this->className, 'getUnits');
        return DB::table("warehouse")
            ->select(
                'warehouse.is_basic',
                'warehouse.exchange',
                'warehouse.current_cost',
                'warehouse.main_cost',
                'warehouse.mfg_date',
                'unit.id as unit_id',
                'unit.name as unit_name'
            )
            ->join('unit', 'unit.id', 'warehouse.unit_id')
            ->where('warehouse.drug_store_id', $drugStoreId)
            ->where('warehouse.drug_id', $drugId)
            ->whereRaw('warehouse.is_check = true')
            ->orderBy("warehouse.exchange")->get();
    }

    public function getNumbers($drugId, $drugStoreId)
    {
        LogEx::methodName($this->className, 'getNumbers');
        return DB::table("warehouse")
            ->select(
                'warehouse.number',
                'warehouse.expiry_date',
                'warehouse.quantity'
            )
            ->where('warehouse.drug_store_id', $drugStoreId)
            ->where('warehouse.drug_id', $drugId)
            ->where('warehouse.is_basic', "=", "yes")
            ->where('warehouse.is_check', "=", false)
            ->where('warehouse.quantity', ">=", 1)->get();
    }
}
