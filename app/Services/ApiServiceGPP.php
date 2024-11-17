<?php

/**
 * Created by PhpStorm.
 * User: hieu
 * Date: 2/18/2019
 * Time: 10:31 PM
 */

namespace App\Services;

use Config;
use App\Services\CommonConstant;
use App\LibExtension\LogEx;


/**
 * Class Base chứa hàm xử lý common cho API trạm và sensor
 * VD : login, header, footer của một file XML
 *
 * @package App\Services
 */
class ApiServiceGPP
{
    protected $className = "ApiServiceGPP";

    public function loginApi($method = "POST", $url, $data)
    {
        LogEx::methodName($this->className, 'loginApi');

        $curl = curl_init();

        // curl_setopt($curl, CURLOPT_INTERFACE, env('SERVER_IP', '103.221.222.166'));

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", CommonConstant::URL_API_CUCDUOC . '/api/tai_khoan/dang_nhap', http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        $result = curl_exec($curl);
        // if(!$result){die("Connection Failure");}
        if (!$result) {
            LogEx::error("ERROR_loginApi_CUCDUOC");
        }
        if(curl_errno($curl)){
            LogEx::error(curl_error($curl));
        }

        curl_close($curl);
        return $result;
    }

    public function callAPI($method, $url, $data, $token)
    {
        LogEx::methodName($this->className, 'callAPI');

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_INTERFACE, env('SERVER_IP', '103.221.222.166'));

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "DELETE":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: bearer ' . $token,
            'Content-Type: application/json',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        $result = curl_exec($curl);
        // if(!$result){die("Connection Failure");}
        if (!$result) {
            LogEx::error("ERROR_callAPI_CUCDUOC");
        }
        if(curl_errno($curl)){
            LogEx::error(curl_error($curl));
        }

        curl_close($curl);


        return $result;
    }




    /**
     * process request API
     *
     * @param $data
     * @return mixed
     */
    function processRequestApi($data)
    {
        LogEx::methodName($this->className, 'processRequestApi');

        $url = "http://api.agrimedia.vn/WeatherServices.asmx";
        $headers = array(
            "Content-type: text/xml;charset=utf-8",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . strlen($data)
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
