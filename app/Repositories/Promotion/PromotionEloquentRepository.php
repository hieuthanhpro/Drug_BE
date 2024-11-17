<?php

namespace App\Repositories\Promotion;

use App\Models\Promotion;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PromotionEloquentRepository extends AbstractBaseRepository implements PromotionRepositoryInterface
{
    protected $className = "PromotionEloquentRepository";

    public function __construct(Promotion $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function filter($requestInput, $userInfo)
    {
        $page = $requestInput['page'] ?? 1;
        $per_page = $requestInput['per_page'] ?? 10;
        $offset = ($page - 1) * $per_page;

        $select = "SELECT p.*, (select json_agg(json_build_object(
                                    'id', pr.id,
                                    'type', pr.type,
                                    'prerequisite_selection', pr.prerequisite_selection,
                                    'value', pr.value,
                                    'target_selection', pr.target_selection,
                                    'entitled_product_ids', pr.entitled_product_ids,
                                    'entitled_category_ids', pr.entitled_category_ids,
                                    'entitled_group_ids', pr.entitled_group_ids
                                ))::jsonb from price_rule pr where pr.promotion_id = p.id) as price_rules from promotion p";

        $selectCount = "SELECT count(p.*) as total FROM promotion p";
        $join = " INNER JOIN users u on u.id = p.created_by";
        $where = " WHERE p.drug_store_id = $userInfo->drug_store_id";
        if (isset($requestInput["search"])) {
            $where = $where . " and p.name ~* '" . $requestInput["search"] . "'";
        }
        if (isset($requestInput["created_by"])) {
            $where = $where . " and p.created_by = " . $requestInput["created_by"];
        }
        if (isset($requestInput["status"])) {
            $where = $where . " and p.status = '" . $requestInput["status"] . "'";
        }
        if (isset($requestInput["from_date"])) {
            $where = $where . " and p.start_date >= '" . $requestInput["from_date"] . " 00:00:00.000000'";
        }
        if (isset($requestInput["end_date"])) {
            $where = $where . " and p.end_date <= '" . $requestInput["end_date"] . " 23:59:59.999999'";
        }
        $orderLimit = " order by p.id desc limit " . $per_page . " offset " . $offset;
        $data = DB::select($select . $join . $where . $orderLimit);
        $dataCount = DB::select($selectCount . $join . $where);
        return new LengthAwarePaginator($data, $dataCount[0]->total, $per_page, $page);
    }

    public function detail($id, $userInfo)
    {
        $select = "SELECT p.*, (select json_agg(json_build_object(
                                    'id', pr.id,
                                    'type', pr.type,
                                    'prerequisite_selection', pr.prerequisite_selection,
                                    'value', pr.value,
                                    'target_selection', pr.target_selection,
                                    'entitled_product_ids', pr.entitled_product_ids,
                                    'entitled_category_ids', pr.entitled_category_ids,
                                    'entitled_group_ids', pr.entitled_group_ids
                                ))::jsonb from price_rule pr where pr.promotion_id = p.id) as price_rules from promotion p";
        $where = " WHERE p.drug_store_id = " . $userInfo->drug_store_id . " and p.id = " . $id;
        return DB::select($select . $where);
    }

    public function getPromotionCondition($storeId, $promotionIds = [])
    {
        $select = "SELECT p.*, (select json_agg(json_build_object(
                                    'id', pr.id,
                                    'type', pr.type,
                                    'prerequisite_selection', pr.prerequisite_selection,
                                    'value', pr.value,
                                    'target_selection', pr.target_selection,
                                    'entitled_product_ids', pr.entitled_product_ids,
                                    'entitled_category_ids', pr.entitled_category_ids,
                                    'entitled_group_ids', pr.entitled_group_ids
                                ))::jsonb from price_rule pr where pr.promotion_id = p.id) as price_rules from promotion p";

        $where = " WHERE p.drug_store_id = " . $storeId;
        $where = $where . " and p.status = 'running'";
        if(sizeof($promotionIds) > 0){
            $where = $where . " and p.id in (" . implode(",", $promotionIds) . ")";
        }
        return DB::select($select . $where);
    }
}
