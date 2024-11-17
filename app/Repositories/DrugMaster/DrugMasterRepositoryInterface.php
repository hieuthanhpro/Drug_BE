<?php

/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 1/13/2019
 * Time: 6:08 PM
 *
 */

namespace App\Repositories\DrugMaster;

use App\Repositories\RepositoryInterface;

interface DrugMasterRepositoryInterface extends RepositoryInterface
{
    public function getDrugMasterByName($name);
    public function drugMasterListV3($name);
}
