<?php

namespace App\Services;

use App\LibExtension\LogEx;
use App\Repositories\Unit\UnitRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class UnitService
 * @package App\Services
 */
class UnitService
{
    protected $className = "UnitService";
    private $unitRepository;

    public function __construct(UnitRepositoryInterface $unitRepository)
    {
        LogEx::constructName($this->className, '__construct');
        $this->unitRepository = $unitRepository;
    }

    /**
     * @param $requestInput
     * @return mixed|null
     */
    public function createOrUpdateUnit($requestInput)
    {
        DB::beginTransaction();
        try {
            if (isset($requestInput['id'])) {
                $this->unitRepository->updateOneById($requestInput['id'], $requestInput);
                $unit = $this->unitRepository->findOneById($requestInput['id']);
            } else {
                $unit = $this->unitRepository->create($requestInput);
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
