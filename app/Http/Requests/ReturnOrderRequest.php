<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\LibExtension\LogEx;

class ReturnOrderRequest extends FormRequest
{
    protected $requestName = "ReturnOrderRequest";
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        LogEx::authName($this->requestName, 'authorize');

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        LogEx::requestName($this->requestName, 'rules');

        return array(
            'number' => 'required',
            'number.*' => 'required',
            'expiry_date' => 'required',
            'expiry_date.*' => 'required|date_format:Y-m-d|after:today',
            'quantity' => 'required',
            'quantity.*' => 'required|numeric|min:1',
            'cost' => 'required',
            'cost.*' => 'required|numeric',
            'unit' => 'required',
            'unit.*' => 'required',
            'time' => 'required',
        );
    }

    public function messages(): array
    {
        LogEx::requestName($this->requestName, 'messages');

        return [
            'number.required' => 'Vui lòng nhập số lô',
            'number.*.required' => 'Vui lòng nhập số lô',
            'expiry_date.required' => 'Vui lòng nhập hạn sử dụng',
            'expiry_date.*.required' => 'Vui lòng nhập hạn sử dụng',
            'expiry_date.*.date_format' => 'Vui lòng nhập hạn sử dụng đúng định dạng',
            'expiry_date.*.after' => 'Vui lòng nhập hạn sử dụng sau ngày hôm nay',
            'quantity.required' => 'Vui lòng nhập số lượng',
            'quantity.*.required' => 'Vui lòng nhập số lượng',
            'quantity.*.numeric' => 'Vui lòng nhập số lượng là số',
            'quantity.*.min' => 'Vui lòng nhập số lượng nhỏ nhất là 1',
            'cost.required' => 'Vui lòng nhập giá',
            'cost.*.required' => 'Vui lòng nhập giá',
            'cost.*.numeric' => 'Vui lòng nhập giá là số',
            'unit.required' => 'Vui lòng nhập đơn vị',
            'unit.*.required' => 'Vui lòng nhập đơn vị',
            'time.required' => 'Vui lòng chọn thời gian giao hàng',
        ];
    }
}
