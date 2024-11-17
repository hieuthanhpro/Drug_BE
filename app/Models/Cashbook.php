<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class Cashbook
 *
 *
 * @package App\Models
 */
class Cashbook extends Eloquent
{
    private $errors;
    protected $className = "Cashbook";
    protected $table = 'cashbook';
    protected $fillable = [
        'drug_store_id',
        'code',
        'cash_type',
        'user_id',
        'supplier_id',
        'customer_id',
        'invoice_id',
        'name',
        'phone',
        'address',
        'reason',
        'amount',
        'evidence',
        'created_by',
        'status',
        'cash_date',
        'method',
        'payment_method',
        'gdp_id'
    ];

    public $enum_mapping = [
        'status' => ['done', 'cancel'],
        'method' => ['auto', 'manual'],
        'payment_method' => ['cash', 'banking', 'card', 'momo', 'vnpay', 'other']
    ];

    private $rules = array(
        'cash_type' => 'required',
        'amount' => 'required',
        'cash_date' => 'required',
        'type' => 'required'
    );

    private $customMsg = array(
        'cash_type.required' => 'Vui lòng chọn loại phiếu',
        'amount.required' => 'Vui lòng nhập số tiền',
        'cash_date.required' => 'Vui lòng chọn ngày',
        'type.required' => 'Không xác định được loại phiếu'
    );

    public function validate($data)
    {
        // make a new validator object
        $v = Validator::make($data, $this->rules, $this->customMsg);

        $v->after(function ($validator) use ($data) {
            if (!isset($data['user_id']) && !isset($data['supplier_id']) && !isset($data['customer_id'])) {
                $validator->errors()->add('name', 'Vui lòng chọn người nhận hoặc người nộp tiền');
            }
            if($data['type'] != 'PT' && $data['type'] != 'PC'){
                $validator->errors()->add('type', 'Không xác định được loại phiếu');
            }
        });

        // check for failure
        if ($v->fails())
        {
            // set errors and return false
            $this->errors = $v->errors;
            return false;
        }

        // validation pass
        return true;
    }

    public function errors()
    {
        return $this->errors;
    }
}
