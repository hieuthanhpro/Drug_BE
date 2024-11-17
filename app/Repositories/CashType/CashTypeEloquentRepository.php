<?php

namespace App\Repositories\CashType;

use app\libextension\logex;
use App\Models\CashType;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Support\Facades\DB;

class CashTypeEloquentRepository extends AbstractBaseRepository implements CashTypeRepositoryInterface
{
    protected $className = "CashTypeEloquentRepository";

    public function __construct(CashType $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getCashType($drugStoreId, $type)
    {
        LogEx::methodName($this->className, 'getCashType');
        $typeFinal = 'pay_slip';
        if ($type === 'PT') {
            $typeFinal = 'receipt';
        }
        return DB::table("cash_type")
            ->select('*')
            ->where(
                function ($query) use ($drugStoreId) {
                    $query->where('drug_store_id', $drugStoreId)->orWhereNull('drug_store_id');
                })->where('type', "=", $typeFinal)
            ->where(function ($query) {
                $query->whereNull('is_hidden')->orWhere("is_hidden", "=", false);
            })
            ->orderBy("id")->get();
    }

    public function getCashTypeByInvoiceType($invoiceType)
    {
        LogEx::methodName($this->className, 'getCashByInvoiceType');
        return DB::table("cash_type")
            ->select('*')
            ->where('invoice_type', $invoiceType)->first();
    }
}
