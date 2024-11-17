<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 10:44 AM
 */


namespace App\Repositories\VouchersCheck;

use App\Repositories\RepositoryInterface;

interface VouchersCheckRepositoryInterface extends RepositoryInterface
{
    public function getList($drug_store_id, $searchStr, $from_date, $to_date, $status);
    public function getDetailList($drug_store_id, $from_date, $to_date);
}