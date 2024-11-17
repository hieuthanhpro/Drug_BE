<?php

namespace App\Http\Requests;

use App\LibExtension\CommonConstant;

class CashbookRequest extends BaseRequest
{
    public function rules(): array
    {
        $phoneRegex = CommonConstant::PHONE_REGEX;
        return [
            'id' => 'numeric',
            'code' => 'required|max:20',
            'cash_type' => 'required|numeric',
            'name' => 'required|max:255',
            'phone' => ["regex:$phoneRegex", "nullable", "max:15"],
            'address' => 'max:500',
            'reason' => 'required|max:500',
            'amount' => 'required|numeric',
            'cash_date' => 'date',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Mã phiếu',
            'cash_type' => 'Loại phiếu',
            'name' => 'Họ và tên',
            'phone' => 'Số điện thoại',
            'address' => 'Địa chỉ',
            'reason' => 'Lý do',
            'amount' => 'Số tiền',
            'cash_date' => 'Ngày thu/nộp tiền',
        ];
    }
}
