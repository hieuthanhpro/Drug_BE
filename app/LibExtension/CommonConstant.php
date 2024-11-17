<?php

namespace App\LibExtension;

/**
 * Class CommonConstant
 * @package App\Services
 */
abstract class CommonConstant
{
    protected $className = "CommonConstant";

    // HTTP Code
    public const SUCCESS_CODE = 200;
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const CONFLICT = 409;
    public const UNPROCESSABLE_ENTITY = 422;
    public const INTERNAL_SERVER_ERROR = 500;
    public const NOT_IMPLEMENTED = 501;

    public const ADMIN_ROLE = 1;

    public const MSG_SUCCESS = 'Thành công';
    public const MSG_ERROR = "Có lỗi xảy ra. Vui lòng F5 để thử lại";
    public const MSG_DATA_EXISTS = 'Dữ liệu đã tồn tại';
    public const MSG_MISS_PARAM = "Thiếu param";
    public const MSG_NOTFOUND = 'Không tìm thấy dữ liệu trên hệ thống';
    public const MSG_ERROR_ACCESSDENIED = 'Bạn không có quyền sử dụng tính năng này';
    public const MSG_ERROR_UNPROCESSABLE_ENTITY = "Dữ liệu không hợp lệ";

    public const MSG_EXCEL_FAIL = 'Sai định dạng file excel';
    public const MSG_EXCEL_MISS = 'Tên,số đăng ký và số lô không được để trống';
    public const MSG_SUCCESS_VOUCHERS = 'Tạo phiếu thành công';
    public const MSG_ALREADY_EXISTS = "Đã tồn tại";
    public const MSG_ERROR_QUANTILY = "Số lượng không đủ để hủy";
    public const URL_API_CUCDUOC = "https://duocquocgia.com.vn";

    // Service sms brand
    public const SERVICE_URI = 'http://221.132.39.104:8083/bsmsws.asmx?wsdl';
    public const PROHIBIT_CHARACTOR_REGEX = "/[@$^'~\[\]{}\\\\|]/";

    public const ACTIION_TYPE_MAP = array(
        'IV1' => 'AC1',
        'IV2' => 'AC2',
        'IV3' => 'AC3',
        'IV4' => 'AC4',
        'IV5' => 'AC5',
        'IV6' => 'AC6',
        'IV7' => 'AC7',
        'IV8' => 'AC8',
        'IV9' => 'AC9'
    );

    public const ACTIION_TYPE = array(
        'AC1' => "Bán hàng cho khách",
        'AC2' => "Nhập hàng từ NCC",
        'AC3' => "Khách trả lại thuốc",
        'AC4' => "Trả hàng cho NCC",
        'AC5' => "xuất cân bằng kho",
        'AC6' => "Chuyển kho",
        'AC7' => "Nhập Tồn",
        'AC8' => "Xuất Hủy",
    );

    public const ACTION_TYPE_ADD = array(
        'AC1' => "Bán hàng cho khách",
        'AC2' => "Nhập hàng từ NCC",
        'AC3' => "Khách trả lại thuốc",
        'AC4' => "Trả hàng cho NCC",
        'AC5' => "xuất cân bằng kho",
        'AC6' => "Chuyển kho",
        'AC8' => "Xuất Hủy",
        'AC9' => "Nhập Tồn"
    );

    public const ACTION_TYPE_SUB = array(
        'AC1' => "Bán hàng cho khách",
        'AC2' => "Nhập hàng từ NCC",
        'AC3' => "Khách trả lại thuốc",
        'AC4' => "Trả hàng cho NCC",
        'AC5' => "xuất cân bằng kho",
        'AC6' => "Chuyển kho",
        'AC8' => "Xuất Hủy",
    );

    public const PHONE_REGEX = "/^[0-9. ]{1,}$/";

    /**
     * Format số không làm thay đổi phần thập phân
     * @param $number
     * @param string $decPoint
     * @param string $thousandsSep
     * @return string
     */
    public static function numberFormatUnchangedPrecision($number, $decPoint = '.', $thousandsSep = ',')
    {
        $decimals = 0;
        if (preg_match('{\.\d+}', $number, $matches) === 1) {
            $decimals = strlen($matches[0]) - 1;
        }
        return number_format($number, $decimals, $decPoint, $thousandsSep);
    }
}
