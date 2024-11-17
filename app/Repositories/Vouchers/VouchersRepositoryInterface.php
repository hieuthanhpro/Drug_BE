<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 10:44 AM
 */


namespace App\Repositories\Vouchers;

use App\Repositories\RepositoryInterface;

interface VouchersRepositoryInterface extends RepositoryInterface
{
    public function getListVouchersByCondition($drug_store_id,$form_date = null, $to_date = null,$type=null,$invoice_type = null,$amount =null);
    public function getListSupplier($id,$form_date=null,$to_date =null,$type=null);
    public function getVoucherByMonth($dug_store_id);
    public function getVouchersByYear($year, $dug_store_id);
    public function getDetail($id, $type = null);
}