<?php

namespace App\Http\Requests\Invoice;

use App\Http\Requests\BaseRequest;

class InvoiceSalesRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'amount' => 'nullable|numeric',
            'description' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'discount_rate' => 'nullable|numeric',
            'discount_type' => 'nullable',
            'line_items' => 'nullable|array',

            'line_items.*.combo_name' => 'nullable|max:150',
            'line_items.*.note' => 'nullable',
            'line_items.*.price' => 'nullable|numeric',
            'line_items.*.quantity' => 'nullable|numeric',
            'line_items.*.total_amount' => 'nullable|numeric',

            'line_items.*.age_select' => 'nullable',
            'line_items.*.name_patient' => 'nullable|max:150',
            'line_items.*.patient_code' => 'nullable|numeric',
            'line_items.*.year_old' => 'nullable|numeric',

            'line_items.*.discount' => 'nullable|numeric',
            'line_items.*.drug_id' => 'nullable|numeric',
            'line_items.*.expiry_date' => 'nullable|max:150',
            'line_items.*.number' => 'nullable|max:150',
            'line_items.*.unit_id' => 'nullable|max:150',
            'line_items.*.vat' => 'nullable|numeric',
            'line_items.*.warehouse_quantity' => 'nullable|numeric',

            'line_items.*.items' => 'nullable|array',
            'line_items.*.items.*.combo_quantity' => 'nullable|numeric',
            'line_items.*.items.*.drug_id' => 'nullable|numeric',
            'line_items.*.items.*.expiry_date' => 'nullable|max:150',
            'line_items.*.items.*.number' => 'nullable',
            'line_items.*.items.*.price' => 'nullable|numeric',
            'line_items.*.items.*.quantity' => 'nullable|numeric',
            'line_items.*.items.*.total_amount' => 'nullable|numeric',
            'line_items.*.items.*.unit_id' => 'nullable|numeric',
            'line_items.*.items.*.vat' => 'nullable|numeric',
            'line_items.*.items.*.discount' => 'nullable|numeric',

            'method' => 'required|max:150',
            'pay_amount' => 'required|max:150',
            'payment_method' => 'required',
            'promotion_ids' => 'nullable|array',
            'receipt_date' => 'required|max:150',


        ];
    }
}
