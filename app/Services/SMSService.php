<?php

namespace App\Services;

use App\LibExtension\CommonConstant;
use Artisaninweb\SoapWrapper\SoapWrapper;
use App\LibExtension\LogEx;
use Propaganistas\LaravelPhone\PhoneNumber;

class SMSService
{
    protected $className = "SMSService";
    /**
     * @var SoapWrapper
     */
    protected $soapWrapper;

    /**
     * SMSController constructor.
     *
     * @param SoapWrapper $soapWrapper
     */

    public function __construct(SoapWrapper $soapWrapper)
    {
        LogEx::constructName($this->className, '__construct');

        $this->soapWrapper = $soapWrapper;
    }

    public function getNotiReturnOrderSuccess($drug_store_name, $amount, $time, $orderCode, $toNumbers) {
        LogEx::methodName($this->className, 'getNotiReturnOrderSuccess');

        $message = 'Sphacy cam on '.$drug_store_name.' da dat hang thanh cong tai Sphacy';
        $message .= '. Tong gia tri da dat: '.$amount .'(VND)';
        $message .= '. Ngay gio giao hang: '.$time;
        $message .= '. Ma don hang: '.$orderCode;

        return $message;
    }

    public function notiReturnOrderSuccess($drug_store_name, $amount, $time, $orderCode, $toNumbers) {
        LogEx::methodName($this->className, 'notiReturnOrderSuccess');

        return $this->sendSMS($this->getNotiReturnOrderSuccess($drug_store_name, $amount, $time, $orderCode, $toNumbers), $toNumbers);
    }

    public function sendSMS($message, $toNumbers) {
        LogEx::methodName($this->className, 'sendSMS');
        if(env('APP_ENV') === 'prod') {
            if (preg_match(CommonConstant::PROHIBIT_CHARACTOR_REGEX, $message)) {
                return "Tin nhắn chứa kí tự đặc biệt " . CommonConstant::PROHIBIT_CHARACTOR_REGEX . " . Vui lòng loại bỏ các kí tự này";
            }
            // Format number to 84xxxxx
            $number_formated = PhoneNumber::make($toNumbers, 'VN')->formatE164();
            $number_formated = str_replace('+', '', $number_formated);

            $this->soapWrapper->add('SMSCaller', function ($service) {
                $service
                    ->wsdl(CommonConstant::SERVICE_URI)
                    ->trace(true);
            });

            $data = array(
                "username" => env('SMS_USER'),
                "password" => env('SMS_PASSWORD'),
                "brandname" => env('SMS_BRANDNAME'),
                "loaitin" => "1",
                "phonenumber" => $number_formated,
                "message" => $message
            );
            try {
                $response = $this->soapWrapper->call('SMSCaller.SendBrandSms', array($data));
                LogEx::methodName('SMSCaller.SendBrandSms: ', $response);
                if ($response) {
                    switch ($response->SendBrandSmsResult) {
                        case 0:
                            return 'Invalid username or password';
                        case 1:
                            return 'Invalid brand name';
                        case 2:
                            return 'Invalid phone number';
                        case 3:
                            return 'Brand name chưa khai báo';
                        case 4:
                            return 'Partner chưa khai báo';
                        case 5:
                            return 'Template chưa khai báo';
                        case 6:
                            return 'Login telco system fail';
                        case 7:
                            return 'Error sending sms to telco';
                        case 8:
                            return 'Tin nhắn spam, mỗi số điện thoại nhận tối đa 2 lần cho cũng một nội dung trên một ngày, và 50 lần cho nội dung khác nhau trên một ngày';
                        case 100:
                            return 'database error';
                        default:
                            return true;
                    }
                }
            } catch (\SoapFault $e) {
                LogEx::methodName('SMSCaller.SendBrandSms_SoapFault: ', $e);
            } catch (\ErrorException $e) {
                LogEx::methodName('SMSCaller.SendBrandSms_ErrorException: ', $e);
            }
        }
        return false;
    }
}
