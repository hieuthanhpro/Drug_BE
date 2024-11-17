<?php

namespace App\Http\Requests\Drug;

use App\Http\Requests\BaseRequest;

class GroupCategorySaveRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'name' => 'required|max:120',
            'is_drug' => 'nullable|in:true,false',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Id',
            'name' => 'Từ khóa',
            'is_drug' => 'Loại sản phẩm',
        ];
    }
}
