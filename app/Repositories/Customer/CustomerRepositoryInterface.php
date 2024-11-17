<?php

namespace App\Repositories\Customer;

use App\Repositories\RepositoryInterface;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    public function filter($requestInput, $drugStoreId);
    public function getCustomersByStoreId($drugStoreId);
    public function getDetail($id,$drugStoreId);
}
