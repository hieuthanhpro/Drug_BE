<?php

namespace App\Http\Requests\Invoice;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class InvoiceFilterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'invoice_type' => [Rule::in(['IV1', 'IV2', 'IV3', 'IV4', 'IV5', 'IV6', 'IV7', 'IV8'])],
            'query' => 'nullable|max:255',
            'created_by' => 'nullable|numeric',
            'sale' => 'nullable|numeric',
            'customer' => 'nullable|numeric',
            'supplier' => 'nullable|numeric',
            'from_date' => 'nullable|max:255',
            'to_date' => 'nullable|max:255',
            'status' => ['nullable', Rule::in([
                'processing',
                'done',
                'cancel',
                'temp',
            ])],
            'shipping_status' => ['nullable', Rule::in([
                'processing',
                'delivery',
                'done',
            ])],
            'payment_status' => ['nullable', Rule::in([
                'unpaid',
                'partial_paid',
                'paid',
            ])],
            'type_export' => ['nullable', Rule::in(["all", 'current_page', 'current_search'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'Từ khóa',
            'created_by' => 'Người tạo',
            'sale' => 'Sale bán hàng',
            'customer' => 'Khách hàng',
            'supplier' => 'Nhà cung cấp',
            'from_date' => 'Từ ngày',
            'to_date' => 'Đến ngày',
            'status' => 'Trạng thái hóa đơn',
            'shipping_status' => 'Trạng thái vận chuyển',
            'payment_status' => 'Trạng thái thanh toán',
        ];
    }
}
