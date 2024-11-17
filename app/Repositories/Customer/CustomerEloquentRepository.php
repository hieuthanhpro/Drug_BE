<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/18/2018
 * Time: 9:17 AM
 */


namespace App\Repositories\Customer;

use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\Customer;
use App\Models\Invoice;
use App\Repositories\AbstractBaseRepository;
use Composer\DependencyResolver\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class CustomerEloquentRepository extends AbstractBaseRepository implements CustomerRepositoryInterface
{
    protected $className = "CustomerEloquentRepository";

    public function __construct(Customer $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function filter($requestInput, $drugStoreId)
    {
        $page = $requestInput['page'] ?? 1;
        $perPage = $requestInput['per_page'] ?? 10;
        $offset = ($page - 1) * $perPage;
        $select = "select c.id,
                c.name,
                c.number_phone,
                c.email,
                c.gender,
                c.birthday,
                c.company,
                c.address,
                c.tax_number,
                c.status,
                sum(i.amount + i.vat_amount - i.discount) as total_amount, sum(i.pay_amount) as total_payment, sum(i.amount + i.vat_amount - i.discount - i.pay_amount) as amount
                from customer c";
        $selectCount = "select count(*) as total from customer c";
        $join = " inner join invoice i on i.customer_id = c.id and i.invoice_type = 'IV1' and i.status = 'done'";
        $where = " where c.drug_store_id = $drugStoreId";
        if (isset($name)) {
            $where = $where . " and c.name ~* '" . $name . "'";
        }
        if (isset($phone)) {
            $where = $where . " and c.number_phone ~* '" . $phone . "'";
        }
        if (isset($address)) {
            $where = $where . " and c.address ~* '" . $address . "'";
        }

        $orderBy = " order by c.id desc";
        $limit = " limit $perPage offset $offset";
        $groupBy = " group by c.id, c.name, c.number_phone, c.email, c.gender, c.birthday, c.company, c.address, c.tax_number, c.status";

        $data = DB::select($select . $join . $where . $groupBy . $orderBy . $limit);
        $dataCount = DB::select($selectCount . $where);
        return new LengthAwarePaginator($data, $dataCount[0]->total, $perPage, $page);
    }

    public function filterV3($customerFilterInput, $drugStoreId, $limit = null)
    {
        $limit = $limit ?? $customerFilterInput["limit"] ?? 10;

        $queryDB = "1 = 1";
        if (isset($name)) {
            $queryDB = $queryDB . " and customer.name ~* '" . $name . "'";
        }
        if (isset($phone)) {
            $queryDB = $queryDB . " and customer.number_phone ~* '" . $phone . "'";
        }
        if (isset($address)) {
            $queryDB = $queryDB . " and customer.address ~* '" . $address . "'";
        }

        return DB::table('customer')
            ->select(
                'customer.id',
                'customer.name',
                'customer.number_phone',
                'customer.email',
                'customer.gender',
                'customer.birthday',
                'customer.company',
                'customer.address',
                'customer.tax_number',
                'customer.status',
                DB::raw('sum(invoice.amount + invoice.vat_amount - invoice.discount) as total_amount'),
                DB::raw('sum(invoice.pay_amount) as total_payment'),
                DB::raw('sum(invoice.amount + invoice.vat_amount - invoice.discount - invoice.pay_amount) as amount')
            )
            ->join('invoice', function ($join) {
                $join->on('invoice.customer_id', '=', 'customer.id')
                    ->where('invoice.invoice_type', '=', 'IV1')
                    ->where('invoice.status', '=', 'done');
            })
            ->where('customer.drug_store_id', $drugStoreId)
            ->whereRaw($queryDB)
            ->orderByDesc("id")
            ->groupBy(['customer.id', 'customer.name', 'customer.number_phone', 'customer.email', 'customer.gender', 'customer.birthday', 'customer.company', 'customer.address', 'customer.tax_number', 'customer.status'])
            ->paginate($limit);
    }

    /**
     * Lấy ra danh sách khách hàng
     * @param $drugStoreId
     * @return array
     */
    public function getCustomersByStoreId($drugStoreId): array
    {
        return DB::select("select * from customer where drug_store_id = $drugStoreId or refer_id is not null");
    }

    /**
     * api v3
     * from f_customer_list on v1
     */
    public function customerListV3($request)
    {
        LogEx::methodName($this->className, 'customerListV3');

        $user = $request->userInfo;
        $p_store_id = $user->drug_store_id;
        $requestInput = $request->input();
        $search = $requestInput['query'] ?? null;
        $name = $requestInput['name'] ?? null;
        $phone = $requestInput['number_phone'] ?? null;
        $address = $requestInput['address'] ?? null;

        return DB::table(DB::raw('customer s'))
            ->select(
                's.id',
                's.name',
                's.number_phone',
                's.tax_number',
                's.email',
                's.website',
                's.gender',
                's.birthday',
                's.company',
                's.address',
                's.status',
                DB::raw('sum(case when i.invoice_type = \'IV1\' then 1 else -1 end *
                (i.amount + coalesce(i.vat_amount, 0) - i.discount)) as total_amount'),
                DB::raw('sum(case when i.invoice_type = \'IV1\' then 1 else -1 end *
                i.pay_amount) as total_payment'),
                DB::raw('sum(case when i.invoice_type = \'IV1\' then 1 else -1 end *
                (i.amount + coalesce(i.vat_amount, 0) - i.discount - i.pay_amount)) as amount')
            )
            ->leftJoin(DB::raw('invoice i'), function ($join) {
                $join->on('i.customer_id', '=', 's.id')
                    ->whereIn('i.invoice_type', ['IV1', 'IV3'])
                    ->where('i.status', '=', 'done');
            })
            ->where('s.drug_store_id', '=', $p_store_id)
            ->when(!empty($search), function ($query) use ($search) {
                $keySearch = trim($search);
                $queryDB = "1 = 1 AND ( s.name ~* '" . $keySearch
                    . "' or s.address  ~* '" . $keySearch
                    . "' or s.number_phone  ~* '" . $keySearch
                    . "' )";
                $query->whereRaw($queryDB);
            })
            ->groupBy([
                's.id', 's.name', 's.number_phone', 's.tax_number', 's.email', 's.gender',
                's.birthday', 's.company', 's.address', 's.status'
            ])
            ->orderBy('s.id', 'DESC');
    }

    /**
     * api v3
     * from f_customer_history on v3
     */
    public function customerHistoryV3($request)
    {
        LogEx::methodName($this->className, 'customerHistoryV3');

        $user = $request->userInfo;
        $p_drug_store_id = $user->drug_store_id;
        $requestInput = $request->input();
        $p_customer_id = $requestInput['customer_id'] ?? null;
        $p_from_date = $requestInput['from_date'] ?? null;
        $p_to_date = $requestInput['to_date'] ?? null;
        $p_search_string = $requestInput['search'] ?? null;

        return DB::table(DB::raw('invoice i'))
            ->select(
                'i.id as invoice_id',
                'i.invoice_code',
                'i.receipt_date as created_at',
                'id.drug_id',
                'd.drug_code',
                'd.name as drug_name',
                'id.number',
                'id.expiry_date as expiry_date',
                'id.unit_id',
                'u.name as unit_name',
                'id.exchange',
                'id.quantity',
                'id.cost',
                'id.vat'
            )
            ->join(DB::raw('invoice_detail id'), 'id.invoice_id', '=', 'i.id')
            ->join(DB::raw('drug d'), 'd.id', '=', 'id.drug_id')
            ->join(DB::raw('unit u'), 'u.id', '=', 'id.unit_id')
            ->whereRaw("i.drug_store_id = {$p_drug_store_id}")
            ->whereRaw("i.customer_id = {$p_customer_id}")
            ->where('i.invoice_type', '=', 'IV1')
            ->where('i.status', '=', 'done')
            ->when(!empty($p_from_date), function ($query) use ($p_from_date) {
                $query->where('i.receipt_date', '>=', $p_from_date);
            })
            ->when(!empty($p_to_date), function ($query) use ($p_to_date) {
                $query->where('i.receipt_date', '<=', $p_to_date);
            })
            ->when(!empty($p_search_string), function ($query) use ($p_search_string) {
                $attributes = ['i.invoice_code', 'd.name', 'u.name'];
                $searchTerm = $p_search_string;
                $query->where(function ($query) use ($attributes, $searchTerm) {
                    foreach (array_wrap($attributes) as $attribute) {
                        $query->orWhere(function ($query) use ($attribute, $searchTerm) {
                            foreach (explode(' ', $searchTerm) as $item) {
                                $query->where(
                                    (DB::raw("lower(vn_unaccent({$attribute}))")),
                                    'ILIKE',
                                    '%' . strtolower(Utils::unaccent($item)) . '%');
                            }
                        });
                    }
                });;
            })
            ->orderBy('i.id', 'DESC');
    }

    public function getDetail($id, $drugStoreId)
    {
        LogEx::methodName($this->className, 'getCustomerById');
        $data = DB::table("customer")
            ->select(
                'customer.id as customer_id',
                'customer.name as customer_name',
                'customer.number_phone',
                'customer.tax_number',
                'customer.email',
                'customer.gender',
                'customer.birthday',
                'customer.company',
                'customer.address',
                'customer.status',
                'customer.website'
            )
            ->where('customer.drug_store_id', $drugStoreId)
            ->where('customer.id', $id)->get();
        if ($data->isNotEmpty()) return $data;
        return null;
    }
}
