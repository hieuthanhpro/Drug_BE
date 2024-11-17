<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 10:43 AM
 */

namespace App\Repositories\VouchersCheck;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\VouchersCheck;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;
use App\LibExtension\Utils;

class VouchersCheckEloquentRepository extends AbstractBaseRepository implements VouchersCheckRepositoryInterface
{
    protected $className = "VouchersCheckEloquentRepository";

    public function __construct(VouchersCheck $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }
    public function getList($drug_store_id, $searchStr, $from_date, $to_date, $status)
    {
        LogEx::methodName($this->className, 'getList');

        $data = DB::table("vouchers_check")
            ->select('vouchers_check.*')
            ->selectRaw('case when users.full_name is not null and users.full_name <> \'\' then users.full_name else users.name end as creator')
            ->leftJoin('users', 'users.id', 'vouchers_check.created_by');

        if (!empty($searchStr)) {
            //$data = $data
            //    ->join('check_detail','check_detail.vouchers_check_id', 'vouchers_check.id')
            //    ->join('drug_autosearch','drug_autosearch.drug_id', 'check_detail.drug_id')
            //    ->where('drug_autosearch.drug_store_id', $drug_store_id)
            //    ->whereRaw(Utils::build_query_AutoCom_search($searchStr, 'drug_autosearch.name_pharma_properties'));
            $keySearch = trim($searchStr);
            $where = "1=1 AND (vouchers_check.code ~* '" . $keySearch
                . "' or vouchers_check.note  ~* '" . $keySearch
                . "' or users.name  ~* '" . $keySearch
                . "' or users.full_name  ~* '" . $keySearch
                ."')";
            $data = $data->whereRaw($where);
        }
        if (!empty($from_date)) {
            $data = $data->whereRaw('vouchers_check.created_at::date >= \'' . $from_date . '\'');
        }
        if (!empty($from_date)) {
            $data = $data->whereRaw('vouchers_check.created_at::date >= \'' . $from_date . '\'');
        }
        if (!empty($status)) {
            $data = $data->where('vouchers_check.check_status', '=', $status);
        }
        $data = $data
            ->where('vouchers_check.drug_store_id', $drug_store_id)
            ->orderBy('vouchers_check.id', 'DESC');
            //->paginate(10);

        return $data;
    }

    public function getDetailList($drug_store_id, $from_date, $to_date)
    {
        LogEx::methodName($this->className, 'getList');

        $data = DB::table("vouchers_check")
            ->select(
                'vouchers_check.id',
                'vouchers_check.drug_store_id',
                'vouchers_check.code',
                'vouchers_check.status',
                'vouchers_check.created_at',
                'check_detail.drug_id',
                'check_detail.drug_code',
                'drug.name as drug_name',
                'check_detail.number',
                'check_detail.expiry_date',
                'check_detail.unit_id',
                'unit.name as unit_name',
                'check_detail.amount',
                'check_detail.current_amount',
                'check_detail.diff_amount',
                'check_detail.main_cost',
                'check_detail.diff_value'
            )
            ->selectRaw('case when users.full_name is not null and users.full_name <> \'\' then users.full_name else users.name end as creator')
            ->leftJoin('users', 'users.id', 'vouchers_check.created_by')
            ->join('check_detail', 'check_detail.vouchers_check_id', 'vouchers_check.id')
            ->join('drug', 'drug.id', 'check_detail.drug_id')
            ->join('unit', 'unit.id', 'check_detail.unit_id')
            ->whereRaw('vouchers_check.created_at::date >= \'' . $from_date . '\'')
            ->whereRaw('vouchers_check.created_at::date <= \'' . $to_date . '\'')
            ->where('vouchers_check.drug_store_id', $drug_store_id)
            ->orderByRaw('vouchers_check.id desc, check_detail.id desc')
            ->get();
        return $data;
    }
}
