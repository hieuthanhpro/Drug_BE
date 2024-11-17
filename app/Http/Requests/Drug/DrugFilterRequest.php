<?php

namespace App\Http\Requests\Drug;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class DrugFilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'query' => 'nullable|max:255',
            'group' => 'nullable|numeric',
            'category' => 'nullable|numeric',
            'is_drug' => 'nullable|in:true,false',
            'active' => 'nullable|in:yes,no',
            'sort_by' => ['nullable', Rule::in([
                'drug_name_asc',
                'drug_name_desc',
                'drug_code_asc',
                'drug_code_desc',
                'bar_code_asc',
                'bar_code_desc',
                'unit_name_asc',
                'unit_name_desc',
                'out_price_asc',
                'out_price_desc',
                'quantity_asc',
                'quantity_desc'
            ])],
            'ids' => 'nullable|array',
            'ids.*' => 'numeric',
            'type_export' => ['nullable', Rule::in(["all", 'current_page', 'current_select', 'current_search'])],
            'limit' => 'nullable|integer|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'Từ khóa',
            'group' => 'Nhóm',
            'category' => 'Danh mục',
            'is_drug' => 'Loại sản phẩm',
            'active' => 'Trạng thái',
            'sort_by' => 'Sắp xếp',
            'limit' => 'Giới hạn',
        ];
    }
}
