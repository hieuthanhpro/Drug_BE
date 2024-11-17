<?php

namespace App\Repositories\Cashbook;

use app\libextension\logex;
use App\LibExtension\Utils;
use App\Models\Cashbook;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CashbookEloquentRepository extends AbstractBaseRepository implements CashbookRepositoryInterface
{
    protected $className = "CashbookEloquentRepository";

    public function __construct(Cashbook $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    /**
     * Lọc sổ quỹ
     * @param $requestInput
     * @param $drugStoreId
     * @return array
     */
    public function filter($requestInput, $drugStoreId, $export = 0, $limit = 10): array
    {
        $page = $requestInput['page'] ?? 1;
        $perPage = $requestInput['per_page'] ?? 10;
        if ($limit > 10) $perPage = $limit;
        $offset = ($page - 1) * $perPage;
        if (isset($requestInput['type'])) {
            if ($requestInput['type'] == 'PT') {
                $requestInput['type'] = 'receipt';
            } else {
                $requestInput['type'] = 'pay_slip';
            }
        }

        $select = "select b.id,
				 b.drug_store_id,
				 b.code,
				 b.cash_type,
				 ct.name as cash_type_name,
				 ct.type as cash_type_type,
				 b.invoice_id,
				 b.name,
				 b.phone,
				 b.address,
				 b.reason,
				 b.amount,
				 b.evidence,
				 b.created_by,
				 case
						when 
							b.created_by = 0 then 'Hệ thống' 
						else 
							(select u.name from users u where u.id = b.created_by) 
						end as created_by_name,
				 b.status,
				 b.cash_date,
				 b.method,
				 b.payment_method,
				 b.created_at,
				 b.updated_at
                from	cashbook b";

        $selectOpeningBlance = "select sum(case when ct.type = 'receipt' then b.amount else 0 end) - sum(case when ct.type = 'pay_slip' then b.amount else 0 end) as opening_blance from cashbook b";

        $selectCalcCashbook = "select count(*) as total, sum(b.amount) as total_amount, sum(case when ct.type = 'receipt' then b.amount else 0 end) as total_amount_receipt, sum(case when ct.type = 'pay_slip' then b.amount else 0 end) as total_amount_pay_slip from cashbook b";

        $join = " inner join cash_type ct on ct.id = b.cash_type";

        //Where condition
        $where = " where b.drug_store_id = $drugStoreId";
        $whereOpeningBalance = $where;
        if (isset($requestInput['type'])) {
            $where = $where . " AND ct.type = '" . $requestInput['type'] . "'";
        }
        if (isset($requestInput['code'])) {
            $where = $where . " AND b.code ~* '" . $requestInput['code'] . "'";
        }
        if (isset($requestInput['cash_type'])) {
            $where = $where . " AND b.cash_type = " . $requestInput['cash_type'];
        }
        if (isset($requestInput['cash_type_pc'])) {
            $where = $where . " AND b.cash_type = " . $requestInput['cash_type_pc'];
        }
        if (isset($requestInput['cash_type_pt'])) {
            $where = $where . " AND b.cash_type = " . $requestInput['cash_type_pt'];
        }
        if (isset($requestInput['created_by'])) {
            $where = $where
                . " AND ((select u.name from users u where u.id = b.created_by) ~* '" . $requestInput['created_by']
                . "' or ('Hệ thống' ~* '" . $requestInput['created_by'] . "' and b.created_by = 0";
        }
        if (isset($requestInput['status'])) {
            $where = $where . " AND b.status ~* '" . $requestInput['status'] . "'";
        }
        if (isset($requestInput['name'])) {
            $where = $where . " AND b.name ~* '" . $requestInput['name'] . "'";
        }
        if (isset($requestInput['phone'])) {
            $where = $where . " AND b.phone ~* '" . $requestInput['phone'] . "'";
        }
        if (isset($requestInput['to_date'])) {
            $where = $where . " AND b.cash_date <= '" . $requestInput['to_date'] . "'";
        }
        if (isset($requestInput['payment_method'])) {
            $where = $where . " AND b.payment_method = '" . $requestInput['payment_method'] . "'";
        }
        if (isset($requestInput['from_date'])) {
            $whereOpeningBalance = $where . " AND b.cash_date < '" . $requestInput['from_date'] . "'";
            $where = $where . " AND b.cash_date >= '" . $requestInput['from_date'] . "'";
        }
        if (!empty($requestInput['query'])) {
            $keySearch = trim($requestInput['query']);
            $where = $where . " AND ((select u.name from users u where u.id = b.created_by) ~* '" . $keySearch
                . "' or b.code  ~* '" . $keySearch
                . "' or b.status  ~* '" . $keySearch
                . "' or ct.name  ~* '" . $keySearch
                . "' or b.phone  ~* '" . $keySearch
                ."')";
        }

        //Order condition
        if (isset($requestInput['is_overview']) && $requestInput['is_overview'] == true) {
            $orderBy = " order by b.cash_date asc, b.id asc";
        } else {
            $orderBy = " order by b.cash_date desc, b.id desc";
        }

        //Limit condition
        $limit = "";
        if (!isset($requestInput['is_export']) || $requestInput['is_export'] === false) {
            $limit = " limit $perPage offset $offset";
        }

        $data = DB::select($select . $join . $where . $orderBy . $limit);
        $openingBlance = 0;
        if(isset($requestInput['from_date'])){
            $dataOpeningBlance = DB::select($selectOpeningBlance . $join . $whereOpeningBalance);
            $openingBlance = $dataOpeningBlance[0]->opening_blance;
        }
        if($offset > 0){
            $selectReserveFund = "select sum(case when type = 'receipt' then amount else 0 end) - sum(case when type = 'pay_slip' then amount else 0 end) as total_amount_page from (select b.amount, ct.type from cashbook b $join $where limit $offset) as cashbook_page";
            $dataReserveFund = DB::select($selectReserveFund);
        }
        $dataCalcCashbook = DB::select($selectCalcCashbook . $join . $where);


        $data = new LengthAwarePaginator($data, $dataCalcCashbook[0]->total, $perPage, $page);
        $options = array(
            'total_amount' => $dataCalcCashbook[0]->total_amount,
            'total_amount_receipt' => $dataCalcCashbook[0]->total_amount_receipt,
            'total_amount_pay_slip' => $dataCalcCashbook[0]->total_amount_pay_slip,
            'total_opening_balance' => $openingBlance,
            'reserve_fund_page' => $offset > 0 && isset($dataReserveFund) ? $dataReserveFund[0]->total_amount_page : 0
        );
        return array_merge($data->toArray(), $options);
    }
}
