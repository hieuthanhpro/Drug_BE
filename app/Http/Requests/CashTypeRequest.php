<?php

namespace App\Http\Requests;

class CashTypeRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'numeric',
            'name' => 'required|max:255',
            'type' => 'nullable|string'
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên loại phiếu',
            'type' => 'Kiểu danh sách'
        ];
    }
}
