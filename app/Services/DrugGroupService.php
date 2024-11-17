<?php

namespace App\Services;

use App\Http\Requests\Drug\GroupCategoryFilterRequest;
use App\Http\Requests\Drug\GroupCategorySaveRequest;
use App\LibExtension\LogEx;
use App\Repositories\DrugGroup\DrugGroupRepositoryInterface;
use Illuminate\Support\Facades\DB;


/**
 * Class DrugGroupService
 * @package App\Services
 */
class DrugGroupService
{
    protected $className = "DrugGroupService";
    protected $drugGroup;
    protected $priceRule;

    public function __construct(DrugGroupRepositoryInterface $drugGroup)
    {
        LogEx::constructName($this->className, '__construct');
        $this->drugGroup = $drugGroup;
    }

    public function createOrUpdate(GroupCategorySaveRequest $request)
    {
        LogEx::methodName($this->className, 'createOrUpdate');
        $userInfo = $request->userInfo;
        $requestInput = $request->input();
        DB::beginTransaction();
        try {
            if (isset($requestInput["id"])) {
                $this->drugGroup->updateOneById($requestInput["id"], ["name" => $requestInput["name"]]);
                $group = $this->drugGroup->findOneById($requestInput["id"]);
            } else {
                $group = $this->drugGroup->create(array_merge($requestInput, ["drug_store_id" => $userInfo->drug_store_id]));
            }
            DB::commit();
            LogEx::info($group);
            return $group;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return null;
        }
    }

    public function export(GroupCategoryFilterRequest $groupCategoryFilterRequest)
    {
        LogEx::methodName($this->className, 'export');
        $userInfo = $groupCategoryFilterRequest->userInfo;
        $requestInput = $groupCategoryFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->drugGroup->filter(["is_drug" => $requestInput["is_drug"]], $userInfo->drug_store_id, 35000);
                    break;
                case "current_page":
                    $data = $this->drugGroup->filter($groupCategoryFilterRequest, $userInfo->drug_store_id);
                    break;
                case "current_search":
                    $groupCategoryFilterRequest->request->remove("page");
                    $data = $this->drugGroup->filter($groupCategoryFilterRequest, $userInfo->drug_store_id, 35000);
                    break;
            }
        } else {
            $data = $this->drugGroup->filter($groupCategoryFilterRequest, $userInfo->drug_store_id);
        }
        return $data;
    }
}