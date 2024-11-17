<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 5/4/2019
 * Time: 2:45 PM
 */


namespace App\Repositories\InvoiceDose;

use App\Repositories\RepositoryInterface;

interface InvoiceDoseRepositoryInterface extends RepositoryInterface
{
    public function getDetailByInvoice($invoice_id, $drug_store_id);
    public function getListInvoiceId($drug_store_id);
    function getDetailByInvoiceId($invoice_id);
}