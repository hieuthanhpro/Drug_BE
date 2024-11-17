<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'nullable|numeric',
            'name' => 'required|max:255',
            'gender' => Rule::in(["company", 'male', 'fmale']),
            'address' => 'nullable|max:255',
            'birthday' => 'nullable|date',
            'email' => 'nullable|max:50',
            'number_phone' => 'nullable|max:50',
            'tax_number' => 'nullable|max:50',
            'website' => 'nullable|max:125'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Id',
            'name' => 'Tên',
            'gender' => 'Giới tính',
            'address' => 'Địa chỉ',
            'birthday' => 'Ngày sinh',
            'email' => 'Địa chỉ email',
            'number_phone' => 'Số điện thoại',
            'tax_number' => 'Mã số thuế',
            'website' => 'Địa chỉ website'
        ];
    }
}
