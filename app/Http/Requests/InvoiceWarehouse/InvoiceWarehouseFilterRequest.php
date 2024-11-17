<?php

namespace App\Http\Requests\InvoiceWarehouse;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class InvoiceWarehouseFilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'query' => 'nullable|max:255',
            'type' => [Rule::in(['import', 'export'])],
            'created_by' => 'nullable|numeric',
            'from_date' => 'nullable|max:255',
            'to_date' => 'nullable|max:255',
            'status' => ['nullable', Rule::in([
                'processing',
                'done',
                'cancel',
                'temp',
                'pending',
                'delivery'
            ])],
            'type_export' => ['nullable', Rule::in(["all", 'current_page', 'current_search'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'Từ khóa',
            'created_by' => 'Người tạo',
            'from_date' => 'Từ ngày',
            'to_date' => 'Đến ngày',
            'status' => 'Trạng thái hóa đơn',
            'type' => 'Loại hóa đơn',
        ];
    }
}
