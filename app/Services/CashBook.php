<?php

namespace App\Services;

Use App\Repositories\Vouchers\VouchersRepositoryInterface;
Use App\Repositories\Debt\DebtRepositoryInterface;
use App\LibExtension\LogEx;


/**
 * Class LoginService
 * @deprecated
 * @package App\Services
 */
class CashBook
{
    protected $className = "xClass";

    protected $vouchers;
    protected $debt;

    public function __construct(VouchersRepositoryInterface $vouchers,DebtRepositoryInterface $debt)
    {
        LogEx::constructName($this->className, '__construct');

        $this->vouchers = $vouchers;
        $this->debt = $debt;
    }

    public function createVouchers($data,$invoice_type,$cutomer_id){
        LogEx::methodName($this->className, 'createVouchers');

        $this->vouchers->create($data);
        $amount = $data['amount'];
        if($invoice_type == 'IV2'){
            $check_debt = $this->debt->findOneBy('supplier_id',$cutomer_id);
            if(!empty($check_debt)){
                $amount_update = $check_debt->amount - $amount;
                $payment_amount = $check_debt->total_payment + $amount;
                $this->debt->updateOneBy('supplier_id',$cutomer_id,['amount' => $amount_update,'total_payment'=>$payment_amount]);
            }
        }elseif ($invoice_type == 'IV1'){
            $check_debt = $this->debt->findOneBy('customer_id',$cutomer_id);
            if(!empty($check_debt)){
                $amount_update = $check_debt->amount - $amount;
                $payment_amount = $check_debt->total_payment + $amount;
                $this->debt->updateOneBy('customer_id',$cutomer_id,['amount' => $amount_update,'total_payment'=>$payment_amount]);
            }
        }
        return true;
    }

}
