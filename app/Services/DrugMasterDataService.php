<?php

namespace App\Services;

use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Repositories\DrugMaster\DrugMasterRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class DrugMasterDataService
 * @package App\Services
 */
class DrugMasterDataService
{
    protected $className = "DrugMasterDataService";
    private $drugMasterDataRepository;

    public function __construct(DrugMasterRepositoryInterface $drugMasterDataRepository)
    {
        LogEx::constructName($this->className, '__construct');
        $this->drugMasterDataRepository = $drugMasterDataRepository;
    }

    /**
     * @param $requestInput
     * @return mixed|null
     */
    public function createOrUpdateDrug($requestInput)
    {
        DB::beginTransaction();
        try {
            if (isset($requestInput['id'])) {
                $this->drugMasterDataRepository->updateOneById($requestInput['id'], $requestInput);
                $unit = $this->drugMasterDataRepository->findOneById($requestInput['id']);
            } else {
                $requestInput['drug_code'] = 'DQG' . Utils::getSequenceDB('DRUG');
                $unit = $this->drugMasterDataRepository->create($requestInput);
                //$this->drugMasterDataRepository->updateOneById($unit->id, ['drug_code' => 'DQG' . Utils::buildDrugMasterCode($unit->id)]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return null;
        }
        return $unit;
    }
}
