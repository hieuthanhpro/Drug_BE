<?php
namespace App\Repositories\CashType;

use App\Repositories\RepositoryInterface;

interface CashTypeRepositoryInterface extends RepositoryInterface
{
    public function getCashType($drug_store_id, $type);
    public function getCashTypeByInvoiceType($invoiceType);
}
