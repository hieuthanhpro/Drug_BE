<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 11:04 AM
 */

namespace App\Repositories\InvoiceTmp;

use App\Repositories\RepositoryInterface;

interface InvoiceTmpRepositoryInterface extends RepositoryInterface
{
    public function getDetailById($id);
    public function getAllByDrugStore($drug_store_id);
    public function deleteTmpInvoice($id);
}