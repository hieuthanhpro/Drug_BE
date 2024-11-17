<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:43 AM
 */


namespace App\Repositories\DrugCategory;

use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\DrugCategory;
use App\Repositories\AbstractBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DrugCategoryEloquentRepository extends AbstractBaseRepository implements DrugCategoryRepositoryInterface
{
    protected $className = "DrugCategoryEloquentRepository";

    public function __construct(DrugCategory $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function countDrugById($drug_category_id)
    {
        LogEx::methodName($this->className, 'countDrugById');

        return DB::table('drug')
            ->where('drug.drug_category_id', $drug_category_id)
            ->count();
    }

    public function getList($drug_store_id, $isDrug, $searchText = null) {
        LogEx::methodName($this->className, 'getList');
        if($drug_store_id > 0) {
            $query = 'select c.*, d.total_drug from drug_category c left outer join (select d.drug_category_id, count(*) as total_drug from drug d where d.active = \'yes\' and d.drug_store_id = ' . $drug_store_id . ' group by d.drug_category_id) d on d.drug_category_id = c.id where c.drug_store_id = ' . $drug_store_id;

            if($isDrug !== null){
                $query = $query . ' and c.is_drug = ' . $isDrug . '::boolean';
            }
            if(isset($searchText)){
                $query = $query . ' and c.name ~* \'' . $searchText . '\'';
            }
        }else{
            $query = 'select c.*, d.total_drug from drug_category c left outer join (select d.drug_category_id, count(*) as total_drug from drug d where d.active = \'yes\' group by d.drug_category_id) d on d.drug_category_id = c.id';
            if($isDrug !== null && isset($searchText)){
                $query = $query . ' where c.is_drug = ' . $isDrug . '::boolean and c.name ~* \'' . $searchText . '\'';
            } else if($isDrug !== null){
                $query = $query . ' where c.is_drug = ' . $isDrug . '::boolean';
            } else if(isset($searchText)){
                $query = $query . ' where c.name ~* \'' . $searchText . '\'';
            }
        }
        return DB::select($query);
    }

    public function filter($drugCategoryFilterInput, $drugStoreId, $limit = null)
    {
        LogEx::methodName($this->className, 'filter');
        $limit = $limit ?? $drugFilterInput["limit"] ?? 10;
        $searchText = $drugCategoryFilterInput["query"] ?? null;
        $isDrug = empty($drugCategoryFilterInput["is_drug"]) ? null : $drugCategoryFilterInput["is_drug"];

        $queryDB = '1 = 1';

        if (isset($searchText)) {
            $queryDB = $queryDB . " AND vn_unaccent(drug_category.name) ILIKE '%" . Utils::unaccent($searchText) . "%'";
        }
        if (isset($isDrug)) {
            $queryDB = $queryDB . " AND drug_category.is_drug = " . $isDrug . "::boolean";
        }

        return DB::table('drug_category')
            ->select(
                'drug_category.id',
                'drug_category.name',
                'drug_category.is_drug',
                DB::raw('COALESCE(d.total_drug, 0) as total_drug')
            )
            ->leftJoinSub("select drug.drug_category_id, count(*) as total_drug from drug where drug.active = 'yes' and drug.drug_store_id = $drugStoreId group by drug.drug_category_id", "d", "d.drug_category_id", "drug_category.id")
            ->where('drug_category.drug_store_id', $drugStoreId)
            ->whereRaw($queryDB)
            ->orderByDesc("id")
            ->paginate($limit);
    }

    public function copyDrugCategoryByStoreId($storeId, $destStoreId)
    {
        $now = Carbon::now();
        DB::insert("insert into drug_category (copy_id, drug_store_id, name, active, image, updated_at, created_at) select id, " . $destStoreId . " , name, active, image, '" . $now . "', '" . $now . "' from drug_category where drug_store_id = " . $storeId . " and active = 'yes'");
    }
}
