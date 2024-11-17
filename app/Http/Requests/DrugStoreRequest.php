<?php

namespace App\Http\Requests;

use App\LibExtension\CommonConstant;

class DrugStoreRequest extends BaseRequest
{
    public function rules(): array
    {
        $phoneRegex = CommonConstant::PHONE_REGEX;
        return [
            'id' => 'numeric',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => ["regex:$phoneRegex", "nullable", "max:15"],
            'password' => 'max:255',
            'username' => 'max:255',
            'pharmacist' => 'max:150',
            'business_license' => 'max:20',
            'reg_number' => 'max:20',
            'base_code' => 'max:255',
            'token' => 'max:1000',
            'warning_date' => 'numeric',
            //'status' => 'required|in:0,1',
            //'start_time' => 'date',
            //'end_time' => 'date',
            'usernamedqg' => 'max:255',
            'passworddqg' => 'max:255',
            'settings' => 'nullable|json',
            'type' => 'required|in:GDP,GPP'
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên nhà thuốc',
            'address' => 'Địa chỉ',
            'phone' => 'Số điện thoại',
            'password' => 'Mật khẩu',
            'username' => 'Tài khoản két nối DQG',
            'pharmacist' => 'Dược sĩ phụ trách',
            'business_license' => 'Giấy phép kinh doanh',
            'reg_number' => 'Số đăng ký',
            'base_code' => 'Mã cơ sở',
            'token' => 'Token',
            'warning_date' => 'Ngày cảnh báo',
            'status' => 'Trạng thái',
            'start_time' => 'Ngày bắt đầu sử dụng',
            'end_time' => 'Ngày kết thúc sử dụng',
            'usernamedqg' => 'Tải khoản kết nối DQG',
            'passworddqg' => 'Mật khẩu kết nối DQG',
            'settings' => 'Cấu hình',
            'type' => "Phân loại"
        ];
    }
}
