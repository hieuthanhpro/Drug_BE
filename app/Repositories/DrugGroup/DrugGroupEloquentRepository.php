<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:37 AM
 */


namespace App\Repositories\DrugGroup;

use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\DrugGroup;
use App\Repositories\AbstractBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DrugGroupEloquentRepository extends AbstractBaseRepository implements DrugGroupRepositoryInterface
{
    protected $className = "DrugGroupEloquentRepository";

    public function __construct(DrugGroup $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function countDrugById($id)
    {
        LogEx::methodName($this->className, 'countDrugById');

        $data = DB::table('drug')
            ->where('drug.drug_group_id', $id)
            ->count();
        return $data;
    }

    public function filter($drugGroupFilterInput, $drugStoreId, $limit = null)
    {
        LogEx::methodName($this->className, 'filter');
        $limit = $limit ?? $drugGroupFilterInput["limit"] ?? 10;
        $searchText = $drugGroupFilterInput["query"] ?? null;
        $isDrug = empty($drugGroupFilterInput["is_drug"]) ? null : $drugGroupFilterInput["is_drug"];

        $queryDB = '1 = 1';

        if (isset($searchText)) {
            $queryDB = $queryDB . " AND vn_unaccent(drug_group.name) ILIKE '%" . Utils::unaccent($searchText) . "%'";
        }
        if (isset($isDrug)) {
            $queryDB = $queryDB . " AND drug_group.is_drug = " . $isDrug . "::boolean";
        }

        return DB::table('drug_group')
            ->select(
                'drug_group.id',
                'drug_group.name',
                'drug_group.is_drug',
                DB::raw('COALESCE(d.total_drug, 0) as total_drug')
            )
            ->leftJoinSub("
                select drug.drug_group_id, 
                       count(*) as total_drug 
                from drug 
                where drug.active = 'yes' and drug.drug_store_id = $drugStoreId 
                group by drug.drug_group_id", "d", "d.drug_group_id", "drug_group.id"
            )
            ->where('drug_group.drug_store_id', $drugStoreId)
            ->whereRaw($queryDB)
            ->orderByDesc("id")
            ->paginate($limit);
    }

    public function copyDrugGroupByStoreId($storeId, $destStoreId)
    {
        $now = Carbon::now();
        DB::insert("
            insert into drug_group 
                (copy_id, drug_store_id, name, active, image, updated_at, created_at) select id, 
                " . $destStoreId . " , name, active, image, '" . $now . "', '" . $now . "' 
                from drug_group where drug_store_id = " . $storeId . " and active = 'yes'");
    }

    public function getList($drug_store_id, $isDrug) {
        LogEx::methodName($this->className, 'getList');
        if ($drug_store_id > 0){
            $data = DB::select('
                select g.*, d.total_drug 
                from drug_group g 
                    left outer join (select d.drug_group_id, count(*) as total_drug 
                    from drug d 
                    where d.active = \'yes\' and d.drug_store_id = ? 
                    group by d.drug_group_id) d on d.drug_group_id = g.id 
                where g.drug_store_id = ? and g.is_drug = ? ', [$drug_store_id, $drug_store_id, $isDrug]
            );
        } else {
            $data = DB::select('
                select g.*, d.total_drug 
                from drug_group g 
                    left outer join (select d.drug_group_id, count(*) as total_drug 
                from drug d 
                where d.active = \'yes\' 
                group by d.drug_group_id) d on d.drug_group_id = g.id 
                where g.is_drug = ? ', [$isDrug]
            );
        }

        return $data;
    }

}
