<?php

namespace App\Services;

use App\LibExtension\LogEx;

/**
 * Class AbstractBaseService
 *
 * @package App\Services
 */
abstract class AbstractBaseService implements ServicesInterface {
    protected $className = "AbstractBaseService";

    public function __construct()
    {
        LogEx::constructName($this->className, '__construct');

    }
    public function convert ($object){
        LogEx::methodName($this->className, 'convert');

        return json_decode(json_encode($object), true);
    }
}
