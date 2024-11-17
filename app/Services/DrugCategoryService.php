<?php

namespace App\Services;

use App\Http\Requests\Drug\GroupCategoryFilterRequest;
use App\Http\Requests\Drug\GroupCategorySaveRequest;
use App\LibExtension\LogEx;
use App\Repositories\DrugCategory\DrugCategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;


/**
 * Class DrugCategoryService
 * @package App\Services
 */
class DrugCategoryService
{
    protected $className = "DrugCategoryService";
    protected $drugCategory;
    protected $priceRule;

    public function __construct(DrugCategoryRepositoryInterface $drugCategory)
    {
        LogEx::constructName($this->className, '__construct');
        $this->drugCategory = $drugCategory;
    }

    public function createOrUpdate(GroupCategorySaveRequest $request)
    {
        LogEx::methodName($this->className, 'createOrUpdate');
        $userInfo = $request->userInfo;
        $requestInput = $request->input();
        DB::beginTransaction();
        try {
            if (isset($requestInput["id"])) {
                $this->drugCategory->updateOneById($requestInput["id"], ["name" => $requestInput["name"]]);
                $category = $this->drugCategory->findOneById($requestInput["id"]);
            } else {
                $category = $this->drugCategory->create(array_merge($requestInput, ["drug_store_id" => $userInfo->drug_store_id]));
            }
            DB::commit();
            LogEx::info($category);
            return $category;
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
                    $data = $this->drugCategory->filter(["is_drug" => $requestInput["is_drug"]], $userInfo->drug_store_id, 35000);
                    break;
                case "current_page":
                    $data = $this->drugCategory->filter($groupCategoryFilterRequest, $userInfo->drug_store_id);
                    break;
                case "current_search":
                    $groupCategoryFilterRequest->request->remove("page");
                    $data = $this->drugCategory->filter($groupCategoryFilterRequest, $userInfo->drug_store_id, 35000);
                    break;
            }
        } else {
            $data = $this->drugCategory->filter($groupCategoryFilterRequest, $userInfo->drug_store_id);
        }
        return $data;
    }
}