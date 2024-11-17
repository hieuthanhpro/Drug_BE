<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/19/2018
 * Time: 3:17 PM
 */


namespace App\Repositories\WarehouseLog;

use App\Repositories\RepositoryInterface;

interface WarehouseLogRepositoryInterface extends RepositoryInterface
{
    public function getLogCurrentday($current_day,$drug_store_id);
}