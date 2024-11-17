<?php
namespace App\Repositories\Cashbook;

use App\Repositories\RepositoryInterface;

interface CashbookRepositoryInterface extends RepositoryInterface
{
    public function filter($requestInput, $drugStoreId);
}
