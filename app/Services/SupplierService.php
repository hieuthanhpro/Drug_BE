<?php
/**
 * Created by PhpStorm.
 * User: hieu
 * Date: 5/3/2019
 * Time: 1:37 PM
 */

namespace App\Services;

use App\LibExtension\Utils;
use Config;
use App\Services\CommonConstant;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;

/**
 * Class Base chứa hàm xử lý common cho API trạm và sensor
 * VD : login, header, footer của một file XML
 *
 * @package App\Services
 */
class SupplierService
{
    protected $className = "BuyDoseService";

    protected $drug;
    protected $warehouse;

    public function __construct(
        DrugRepositoryInterface $drug,
        WarehouseRepositoryInterface $warehouse
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->drug = $drug;
        $this->warehouse = $warehouse;
    }

    /**
     * api v3
     * from f_supplier_invoice_list on v1
    */
    public function supplierInvoiceList($params)
    {
        LogEx::methodName($this->className, 'supplierInvoiceList');

        return DB::table(DB::raw('supplier s'))
            ->select(
                's.id',
                's.name',
                's.number_phone',
                's.tax_number',
                's.email',
                's.website',
                's.address',
                's.status',
                DB::raw('sum(case when i.invoice_type = \'IV2\' then 1 else -1 end *
                (i.amount + coalesce(i.vat_amount, 0) - i.discount)) as total_amount'),
                DB::raw('sum(case when i.invoice_type = \'IV2\' then 1 else -1 end *
                i.pay_amount) as total_payment'),
                DB::raw('sum(case when i.invoice_type = \'IV2\' then 1 else -1 end *
                (i.amount + coalesce(i.vat_amount, 0) - i.discount - i.pay_amount)) as amount'))
            ->leftJoin(DB::raw('invoice i'), function($join) {
                $join->on('i.customer_id','=','s.id')
                    ->whereIn('i.invoice_type',['IV2', 'IV4'])
                    ->where('i.status','=','done');
            })
            ->where('s.drug_store_id', '=', $params['drug_store_id'])
            ->when(!empty($params['search']), function ($query) use ($params) {
                $keySearch = trim($params['search']);
                $queryDB = "1 = 1 AND ( s.name ~* '" . $keySearch
                    . "' or s.address  ~* '" . $keySearch
                    . "' or s.number_phone  ~* '" . $keySearch
                    ."' )";
                $query->whereRaw($queryDB);
            })
            ->groupBy(['s.id','s.name','s.number_phone','s.tax_number','s.email','s.website','s.address','s.status'])
            ->orderBy('s.id','DESC');
    }

    /**
     * api v3
     * from f_supplier_history on v3
     */
    public function supplierHistory($params)
    {
        LogEx::methodName($this->className, 'supplierHistory');

        $p_drug_store_id = $params->userInfo->drug_store_id;
        $requestInput = $params->input();
        $tmp_warning = DB::table(DB::raw('invoice i'))
            ->select(
                'i.id as invoice_id',
                'i.invoice_code',
                'i.created_at::date as created_at',
                'id.drug_id',
                'd.drug_code',
                'd.name as drug_name',
                'id.number',
                'id.expiry_date::date as expiry_date',
                'id.unit_id',
                'u.name as unit_name',
                'id.exchange',
                'id.quantity',
                'id.cost',
                'id.vat'
            )
            ->join(DB::raw('invoice_detail id'),'id.invoice_id','=','i.id')
            ->join(DB::raw('drug d'),'d.id','=','id.drug_id')
            ->join(DB::raw('unit u'),'u.id','=','id.unit_id')
            //->leftJoin(DB::raw('tmp_drug_autosearch ds'),'ds.drug_id','=','d.id')
            ->where('i.drug_store_id', '=', $p_drug_store_id)
            ->when(!empty($requestInput['supplier_id']), function ($query) use ($requestInput) {
                $query->where('i.customer_id', '=', $requestInput['supplier_id']);
            })
            ->whereIn('i.invoice_type',['IV2', 'IV7'])
            ->where('i.status','=','done')
            ->where(function ($query) { $query->whereNull('p_from_date')
                ->orWhereRaw('length(trim(p_from_date)) = ? ', [0])
                ->orWhereRaw('i.receipt_date >= p_from_date::date');})
            ->where(function ($query) { $query->whereNull('p_to_date')
                ->orWhereRaw('length(trim(p_to_date)) = ? ', [0])
                ->orWhereRaw('i.receipt_date <= p_to_date::date');})
            ->where(function ($query) { $query->whereRaw('coalesce(p_search_string,\'\') = ? ', [''])
                ->orWhereNotNull('ds.drug_id');})
            ->get();
        return 1;
    }

}
