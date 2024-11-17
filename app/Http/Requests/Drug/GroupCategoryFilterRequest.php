<?php

namespace App\Http\Requests\Drug;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class GroupCategoryFilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'query' => 'nullable|max:255',
            'is_drug' => 'nullable|in:true,false',
            'type_export' => ['nullable', Rule::in(["all", 'current_page', 'current_search'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'Từ khóa',
            'is_drug' => 'Loại sản phẩm',
        ];
    }
}
