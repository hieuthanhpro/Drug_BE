<?php

namespace App\Services;

use App\Http\Requests\Customer\CustomerFilterRequest;
use App\LibExtension\Utils;
use App\Repositories\Customer\CustomerRepositoryInterface;
use App\LibExtension\LogEx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class CustomerService
 * @package App\Services
 */
class CustomerService
{
    protected $className = "CustomerService";
    protected $customer;

    public function __construct(CustomerRepositoryInterface $customer)
    {
        LogEx::constructName($this->className, '__construct');

        $this->customer = $customer;
    }

    public function export(CustomerFilterRequest $customerFilterRequest)
    {
        LogEx::methodName($this->className, 'export');
        $userInfo = $customerFilterRequest->userInfo;
        $requestInput = $customerFilterRequest->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;
        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->customer->filterV3(null, $userInfo->drug_store_id, 35000);
                    break;
                case "current_page":
                    $data = $this->customer->filterV3($customerFilterRequest, $userInfo->drug_store_id);
                    break;
                case "current_search":
                    $customerFilterRequest->request->remove("page");
                    $data = $this->customer->filterV3($customerFilterRequest, $userInfo->drug_store_id, 35000);
                    break;
            }
        } else {
            $data = $this->customer->filterV3($customerFilterRequest, $userInfo->drug_store_id);
        }
        return $data;
    }

    public function createOrUpdate($requestInput, $drugStoreId)
    {
        DB::beginTransaction();
        try {
            if (isset($requestInput["id"])) {
                $this->customer->updateOneById($requestInput["id"], $requestInput);
                $customer = $this->customer->findOneByCredentials(['id' => $requestInput["id"], 'drug_store_id' => $drugStoreId]);
            } else {
                $customer = $this->customer->create($requestInput);
            }
            DB::commit();
            return $customer;
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return null;
        }
    }
}
