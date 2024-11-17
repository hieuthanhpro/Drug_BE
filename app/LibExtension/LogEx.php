<?php

namespace App\LibExtension;

use Illuminate\Support\Facades\Log;


class LogEx extends Log
{

    private static $debug = false;
    private static $logDB = true;
    private static $logORMDB = false;
    private static $strStartORM = "select column_name";
    private static $strStartInsert = "insert ";
    private static $strStartUpdate = "update ";

    // ###### INFO ######
    public static function methodName($className, $name)
    {
        parent::info("[$className - $name]");
    }

    public static function requestName($requestName, $name)
    {
        parent::info("[$requestName - $name]");
    }
    public static function information($msg)
    {
        parent::info($msg);
    }

    // ###### DEBUG ######

    public static function debug($message)
    {
        if (self::$debug)
            parent::debug($message);
    }

    public static function constructName($className, $name)
    {
        self::debug("[$className - $name]");
    }

    public static function registerName($className, $name)
    {
        self::debug("[$className - $name]");
    }

    public static function bootName($className, $name)
    {
        self::debug("[$className - $name]");
    }

    public static function mapName($className, $name)
    {
        self::debug("[$className - $name]");
    }

    public static function mapApiRoutesName($className, $name)
    {
        self::debug("[$className - $name]");
    }

    public static function mapWebRoutesName($className, $name)
    {
        self::debug("[$className - $name]");
    }

    public static function authName($className, $name)
    {
        self::debug("[$className - $name]");
    }

    public static function handleException($className, $name)
    {
        self::debug("[$className - $name]");
    }


    // ###### MONITOR ######
    public static function monitorLog($message)
    {
        parent::warning($message);
    }
    public static function try_catch($className, $exception)
    {
        self::monitorLog("[$className] $exception");
    }

    // ###### SQL query, ORM ######
    public static function debugDB($message)
    {
        parent::debug($message);
    }

    public static function debugORMDB($message)
    {
        if (self::$logORMDB) {
            parent::debug($message);
        }
    }

    public static function logSQL($sql, $bindings, $time)
    {
        // Postgres log query except ORM query
        if (self::$logDB) {
            // Not contain ORM query
            if (strpos($sql, self::$strStartORM) !==  false) {
                self::debugORMDB("Duration: $time - ORM table: $bindings[1]");
            } else {
                $strPrefix = "";
                if (strpos($sql, self::$strStartInsert) ===  0) {
                    $strPrefix = '#### INSERT ###';
                } else if (strpos($sql, self::$strStartUpdate) ===  0) {
                    $strPrefix = '#### UPDATE ###';
                }
                self::debugDB("$strPrefix Duration: $time - $sql -[" . implode(', ', $bindings) . "]");
            }
        }
    }

    public static function printDebug($obj)
    {
        self::debug("printDebug");
        self::debug(print_r($obj));
    }
}
