<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\LibExtension\LogEx;

class UpdateDrugStore extends FormRequest
{
    protected $requestName = "UpdateDrugStore";
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
        LogEx::authName($this->requestName, 'rules');

        return [
            'name' => 'required|max:191',
            'address' =>'required|max:191',
            'phone' =>'required|max:100',
            'pharmacist' => 'required|max:191',
        ];
    }
    public function messages(): array
    {
        LogEx::requestName($this->requestName, 'messages');

        return[
            'name.required'=>'Tên không được để trống ! ',
            'name.max'=>'Tên không được quá :max ký tự ! ',
            'address.required'=>'Địa chỉ không được để trống ! ',
            'phone.required'=>'Số điện thoại không được để trống ! ',
            'username.required'=>'Tài khỏan kết nối không được để trống ! ',
            'password.required'=>'Mật khẩu không được để trống ! ',
            'pharmacist.required' => 'Tên dược sĩ phụ trách không được để trống',
        ];
    }
}
