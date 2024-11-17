<?php

namespace App\Http\Requests;

use App\LibExtension\CommonConstant;

class UserRequest extends BaseRequest
{
    public function rules(): array
    {
        $phoneRegex = CommonConstant::PHONE_REGEX;
        return [
            'id' => 'numeric',
            'drug_store_id' => 'nullable|numeric',
            'name' => 'required|string|max:100',
            'username' => 'required|alpha_dash|max:100',
            'password' => 'max:100',
            'number_phone' => ["regex:$phoneRegex", "required", "max:15"],
            'email' => 'email|max:50',
            'user_role' => 'required|max:20',
            //'is_order_manager' => 'boolean',
            //'is_system_admin' => 'boolean'
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên người dùng',
            'username' => 'Tên đăng nhập',
            'password' => 'Mật khẩu',
            'number_phone' => 'Số điện thoại',
            'email' => 'Email',
            'user_role' => 'Phân quyền'
        ];
    }
}
