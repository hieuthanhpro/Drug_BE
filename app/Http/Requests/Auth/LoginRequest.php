<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\LibExtension\LogEx;

class LoginRequest extends FormRequest
{
    protected $className = "Auth\LoginRequest";
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        LogEx::authName($this->className, 'authorize');

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        LogEx::authName($this->className, 'rules');

        return [
            'username' => 'required|max:191',
            'password' =>'required|max:191'
        ];
    }
    public function messages(): array
    {
        LogEx::authName($this->className, 'messages');

        return[
            'username.required'=>'Tên đăng nhập không được để trống ! ',
            'password.required'=>'Mật khẩu không được để trống ! ',
        ];
    }
}
