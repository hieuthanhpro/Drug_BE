<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/12/2018
 * Time: 11:09 AM
 */

namespace App\Repositories\InvoiceDetail;

use App\Repositories\RepositoryInterface;

interface InvoiceDetailRepositoryInterface extends RepositoryInterface
{
    public function getTopDrug($drug_store_id);
}