<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:48 AM
 */

namespace App\Repositories\Supplier;

use App\Models\Supplier;
use App\Models\Invoice;
use App\Models\Vouchers;
use Illuminate\Support\Facades\DB;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;

class SupplierEloquentRepository extends AbstractBaseRepository implements SupplierRepositoryInterface
{
    protected $className = "SupplierEloquentRepository";

    public function __construct(Supplier $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }


    public function findAllbyStore($store_id){
        LogEx::methodName($this->className, 'findAllbyStore');

        $data = $this->findManyBy('drug_store_id',$store_id);
        return $data;
    }


    public function getDetail($id){
        LogEx::methodName($this->className, 'getDetail');

        $result_data = null;
        $data = DB::table("supplier")
            ->select(
                'supplier.name',
                'supplier.status',
                'supplier.number_phone',
                'supplier.email',
                'supplier.tax_number',
                'supplier.website',
                'supplier.address'

            )

            ->where('supplier.id', $id)
            ->first();


        $invoice = Invoice::select('invoice.invoice_type', \DB::raw('SUM(invoice.amount) as amount, SUM(invoice.vat_amount) as vat_amount,SUM(invoice.discount) as discount,SUM(invoice.pay_amount) as pay_amount'))
            ->where('invoice.invoice_type', "IV2")
            ->where('invoice.customer_id',$id)
            ->where('invoice.status','done')
            ->groupBy('invoice.invoice_type')
            ->first();

        // $voucher = Vouchers::select('vouchers.supplier_id', \DB::raw('SUM(vouchers.amount) as amount'))
        //     ->where('vouchers.type', 0)
        //     ->where('vouchers.status',1)
        //     ->where('vouchers.supplier_id',$id)
        //     ->groupBy('vouchers.supplier_id')
        //     ->first();

        $total_amount = $invoice['amount'] + $invoice['vat_amount'] - $invoice['discount'];
        $total_payment = $invoice['pay_amount'];
        $amount = $total_amount - $total_payment;

        $data->total_payment = $total_payment;
        $data->total_amount = $total_amount;
        $data->amount = $amount;


        $result_data[] = $data;
        return $result_data;
    }


    public function getListSupplier($store_id,$name=null, $phone=null, $address=null) {
        LogEx::methodName($this->className, 'getListSupplier');

        $data_result = array();
        $query = DB::table("supplier")
            ->select(
                'supplier.id',
                'supplier.name',
                'supplier.number_phone',
                'supplier.tax_number',
                'supplier.email',
                'supplier.website',
                'supplier.address',
                'supplier.status'
            )
            ->selectRaw('sum(invoice.amount + invoice.vat_amount - invoice.discount) as total_amount, sum(invoice.pay_amount) as total_payment, sum(invoice.amount + invoice.vat_amount - invoice.discount - invoice.pay_amount) as amount')
            ->leftJoin('invoice', function($join) {
                $join
                    ->on('invoice.customer_id', '=', 'supplier.id')
                    ->on('invoice.invoice_type', DB::raw("'IV2'"))
                    ->on('invoice.status', DB::raw("'done'"))
                ;
            })
            ->where('supplier.drug_store_id', $store_id)
            ;

        if (!empty($name)) {
            $query->where('supplier.name', 'ilike', $name . '%');
        }
        if (!empty($phone)) {
            $query->where('supplier.number_phone', 'like', '%' . $phone . '%');
        }
        if (!empty($address)) {
            $query->where('supplier.address', 'like', '%' . $address . '%');
        }
        $query = $query->groupBy(
            'supplier.id',
            'supplier.name',
            'supplier.tax_number',
            'supplier.number_phone',
            'supplier.email',
            'supplier.website',
            'supplier.address',
            'supplier.status'
        );
        $supplier = $query->orderBy('supplier.id', 'DESC')->paginate(10);
        // if(!empty($supplier)){
        //     foreach ($supplier as $value){
        //         $invoice = Invoice::select('invoice.invoice_type', \DB::raw('SUM(invoice.amount) as amount, SUM(invoice.vat_amount) as vat_amount,SUM(invoice.discount) as discount'))
        //                 ->where('invoice.invoice_type', "IV2")
        //                 ->where('invoice.customer_id',$value->id)
        //                 ->where('invoice.status','done')
        //                 ->groupBy('invoice.invoice_type')
        //                 ->first();

        //         $voucher = Vouchers::select('vouchers.supplier_id', \DB::raw('SUM(vouchers.amount) as amount'))
        //             ->where('vouchers.type', 0)
        //             ->where('vouchers.status',1)
        //             ->where('vouchers.supplier_id',$value->id)
        //             ->groupBy('vouchers.supplier_id')
        //             ->first();

        //         $total_amount = $invoice['amount'] + $invoice['vat_amount'] - $invoice['discount'];
        //         $total_payment = $voucher['amount'];
        //         $amount = $total_amount - $total_payment;
        //         $value->total_payment = $total_payment;
        //         $value->total_amount = $total_amount;
        //         $value->amount = $amount;
        //         $data_result['data'][] = $value;
        //     }
        // }

        $formatSupplier = json_decode(json_encode($supplier), true);

        $data_result['data'] = $formatSupplier['data'];
        $data_result['current_page'] = $formatSupplier['current_page'];
        $data_result['first_page_url'] = $formatSupplier['first_page_url'];
        $data_result['from'] = $formatSupplier['from'];
        $data_result['last_page'] = $formatSupplier['last_page'];
        $data_result['to'] = $formatSupplier['to'];
        $data_result['total'] = $formatSupplier['total'];
        $data_result['path'] = $formatSupplier['path'];
        $data_result['last_page_url'] = $formatSupplier['last_page_url'];
        $data_result['next_page_url'] = $formatSupplier['next_page_url'];

        return $data_result;
    }
}
