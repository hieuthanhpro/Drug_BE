<?php

namespace App\Http\Requests\Warehouse;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class WarehouseFilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'query' => 'nullable|max:255',
            'type_export' => Rule::in(["all", 'current_page', 'current_search']),
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'Từ khóa',
        ];
    }
}
