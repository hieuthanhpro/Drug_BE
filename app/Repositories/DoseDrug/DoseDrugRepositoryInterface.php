<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:43 AM
 */


namespace App\Repositories\DoseDrug;

use App\Repositories\RepositoryInterface;

interface DoseDrugRepositoryInterface extends RepositoryInterface
{
    public function getListDoseDrug($drug_store_id,$name,$category,$group);
    public function getDetailDose($id);

}