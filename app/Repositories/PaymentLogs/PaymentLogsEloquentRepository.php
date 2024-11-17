<?php

namespace App\Repositories\PaymentLogs;

use app\libextension\logex;
use App\Models\PaymentLogs;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentLogsEloquentRepository extends AbstractBaseRepository implements PaymentLogsRepositoryInterface
{
    protected $className = "PaymentLogsEloquentRepository";

    public function __construct(PaymentLogs $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function filter($type, $requestInput, $userInfo, $export = 0, $limit = 10)
    {
        $page = $requestInput['page'] ?? 1;
        $per_page = ($limit > 10) ? $limit : ($requestInput['per_page'] ?? 10);
        $offset = ($page - 1) * $per_page;

        $select = "SELECT pl.*, i.invoice_code, c.name as customer_name,
                                c.address,
                                c.email,
                                c.number_phone,								
								CASE WHEN i.is_order = true THEN ds.name ELSE s.name END as supplier_name,
								CASE WHEN i.is_order = true THEN ds.phone ELSE s.number_phone END as supplier_phone,
								CASE WHEN i.is_order = true THEN ds.address ELSE s.address END as supplier_address,
                                s.email as supplier_email,
                                s.website as supplier_website,
								i.is_order FROM payment_logs pl";

        $selectCount = "SELECT count(pl.*) as total FROM payment_logs pl";
        $join = " inner join invoice i on pl.invoice_id = i.id left outer join customer c
                  on  c.id = i.customer_id
                  and i.invoice_type  in ('IV1', 'IV3')
                  left outer join supplier s
                  on  s.id = i.customer_id
                  and i.invoice_type  in ('IV2', 'IV4', 'IV7')
				  left outer join drugstores ds
			      on ds.id = i.customer_id";
        $where = " WHERE pl.drug_store_id = " . $userInfo->drug_store_id . " AND pl.invoice_type = '" . $type . "'";
        if (isset($requestInput["status"])) {
            $where = $where . " and pl.status = '" . $requestInput["status"] . "'";
        }
        if (isset($requestInput["query"])) {
            //$where = $where . " and i.invoice_code ~* '" . $requestInput["query"] . "'";
            $where = $where . " and ( i.invoice_code ~* '" . $requestInput["query"]
                . "' or s.name  ~* '" . $requestInput["query"]
                . "' or s.number_phone  ~* '" . $requestInput["query"]
                ."' )";
        }
        if (isset($requestInput["from_date"])) {
            $where = $where . " and pl.cash_date >= '" . $requestInput["from_date"] . " 23:59:59.999999'";
        }
        if (isset($requestInput["end_date"])) {
            $where = $where . " and pl.cash_date <= '" . $requestInput["end_date"] . " 23:59:59.999999'";
        }
        $orderLimit = " order by pl.id desc limit " . $per_page . " offset " . $offset;
        $data = DB::select($select . $join . $where . $orderLimit);
        $dataCount = DB::select($selectCount . $join . $where);
        return new LengthAwarePaginator($data, $dataCount[0]->total, $per_page, $page);
    }
}
