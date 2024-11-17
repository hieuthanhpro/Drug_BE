<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:49 AM
 */


namespace App\Repositories\Supplier;

use App\Repositories\RepositoryInterface;

interface SupplierRepositoryInterface extends RepositoryInterface
{
    public function findAllbyStore($store_id);
    public function getListSupplier($store_id,$name=null, $phone=null, $address=null);
    public function getDetail($id);
}