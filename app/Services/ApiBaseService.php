<?php
/**
 * Created by PhpStorm.
 * User: hieu
 * Date: 5/15/2018
 * Time: 5:01 PM
 */

namespace App\Services;

use Config;
use App\LibExtension\LogEx;


/**
 * Class Base chứa hàm xử lý common cho API trạm và sensor
 * VD : login, header, footer của một file XML
 *
 * @package App\Services
 */
class ApiBaseService
{
    protected $className = "ApiBaseService";

    protected $username;
    protected $password;
    protected $minutes;
    protected $apiKey;

    public function __construct()
    {
        LogEx::constructName($this->className, '__construct');

        $this->username = Config::get('api.username');
        $this->password = Config::get('api.password');
        $this->minutes = 60;
        $this->apiKey = '';

    }

    /**
     * check login
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    function Login()
    {
        LogEx::methodName($this->className, 'Login');

        if ($this->apiKey == '') {
            $data = '<?xml version="1.0" encoding="utf-8"?>';
            $data .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
            $data .= '<soap:Body>';
            $data .= '<Login xmlns="http://api.agrimedia.vn/">';
            $data .= '<username>' . $this->username . '</username>';
            $data .= '<password>' . $this->password . '</password>';
            $data .= '</Login>';
            $data .= '</soap:Body>';
            $data .= '</soap:Envelope>';
            $result = $this->processRequestApi($data);
            if ($result) {
                $this->apiKey = json_decode($result)->ApiKey;
            } else {
                return back();
            }

        }
    }

    /**
     * generate authenticate
     *
     * @return string
     */
    function authenticate()
    {
        LogEx::methodName($this->className, 'authenticate');

        return '<api:token>' . $this->username . '@@' . $this->apiKey . '</api:token>';
    }

    function header()
    {
        LogEx::methodName($this->className, 'header');

        $data = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:api="http://api.agrimedia.vn/">';
        $data .= '<soapenv:Header/>';
        return $data;
    }

    function footer()
    {
        LogEx::methodName($this->className, 'footer');

        return '</soapenv:Envelope>';
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
