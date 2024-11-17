<?php

namespace App\Http\Requests\Drug;

use App\Http\Requests\BaseRequest;

class DrugRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'nullable|numeric',
            'name' => 'required|max:500',
            //'image_file' => 'nullable|sometimes|mimes:jpg,jpeg,png,gif',
            'short_name' => 'nullable|max:50',
            'drug_group_id' => 'nullable|numeric',
            'drug_category_id' => 'nullable|numeric',
            'unit_id' => 'required|numeric',
            'current_cost' => 'required|numeric|min:1',
            'barcode' => 'nullable|max:100',
            'registry_number' => 'nullable|max:500',
            'country' => 'nullable|max:50',
            'company' => 'nullable|max:150',
            'package_form' => 'nullable|max:500',
            'concentration' => 'nullable|max:500',
            'substances' => 'nullable|max:500',
            'units' => 'nullable|array',
            'units.*.unit_id' => 'required|numeric',
            'units.*.exchange' => 'required|numeric|min:2',
            'units.*.current_cost' => 'required|numeric|min:1',
            'warning_unit' => 'nullable|numeric',
            //'warning_quantity_min' => 'nullable|numeric|min:1',
            //'warning_quantity_max' => 'nullable|numeric|min:1',
            //'warning_days' => 'nullable|numeric|min:1',
            'is_master_data' => 'nullable|in:true,false',
            'master_data_id' => 'nullable|numeric'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Id',
            'name' => 'Tên',
            'image_file' => 'Hình ảnh',
            'image' => 'Hình ảnh',
            'short_name' => 'Tên viết tắt',
            'drug_group_id' => 'Nhóm',
            'drug_category_id' => 'Danh mục',
            'unit_id' => 'Đơn vị cơ bản',
            'current_cost' => 'Giá bán đơn vị cơ bản',
            'barcode' => 'Mã vạch',
            'registry_number' => 'Số đăng ký',
            'country' => 'Nước sản xuất',
            'company' => 'Hãng sản xuất',
            'package_form' => 'Quy cách đóng gói',
            'concentration' => 'Hàm lượng',
            'substances' => 'Hoạt chất',
            'units' => 'Đơn vị quy đổi',
            'units.*.unit_id' => 'Đơn vị quy đổi',
            'units.*.exchange' => 'Hệ số quy đổi',
            'units.*.current_cost' => 'Giá bán quy đổi',
            'warning_unit' => 'Đơn vị cảnh báo',
            'warning_quantity_min' => 'Tồn kho tối thiểu',
            'warning_quantity_max' => 'Tồn kho tối đa',
            'warning_days' => 'Cảnh báo hết hạn',
        ];
    }
}
