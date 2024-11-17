<?php
namespace App\LibExtension;

class RoleUtils
{
    protected $className = "RoleUtils";

    private const ROLE_MAP = array(
        "admin" => ["sell", "warehousing", "warehousing_view", "goods_statistic", "sell_statistic", "activity", "drug", "drug_manage", "order", "revenue", "revenue_profit", "goods_inout", "stock_inout", "prescription_sell", "quality_control", "special_drug", "stock", "stock_check", "stock_confirm", "goods_remove", "manage_system", "manage_customer", "manage_supplier", "cash_book", "cash_book_confirm"],
        "stock_manage" => ["warehousing", "warehousing_view", "goods_statistic", "drug", "drug_manage", "order", "goods_inout", "stock_inout", "quality_control", "stock", "stock_confirm", "goods_remove"],
        "sell_manage" => ["sell", "warehousing_view", "goods_statistic", "sell_statistic", "activity", "drug", "drug_manage", "revenue", "goods_inout", "stock_inout", "prescription_sell", "quality_control", "special_drug", "stock", "stock_check", "manage_customer", "cash_book"],
        "warehousing_manage" => ["warehousing", "warehousing_view", "goods_statistic", "sell_statistic", "activity", "drug", "drug_manage", "order", "goods_inout", "stock_inout", "quality_control", "special_drug", "stock", "stock_check", "goods_remove", "manage_supplier", "cash_book"],
        "sell" => ["sell", "warehousing_view", "goods_statistic", "sell_statistic", "activity", "drug", "prescription_sell", "stock", "stock_check", "manage_customer"],
        "warehousing" => ["warehousing", "warehousing_view", "goods_statistic", "drug", "drug_manage", "stock_inout", "stock", "stock_check", "goods_remove"],
        "cashier" => ["goods_statistic", "sell_statistic", "activity", "revenue", "revenue_profit", "goods_inout", "stock_inout", "cash_book", "cash_book_confirm"],
        "sale" => ["order_po"]
    );

    private const PERMISSION_MAP_CONTROLLER = array(
        "App\Http\Controllers\Backend\AdminController@filterDrugStore" => "manage_system",
        "App\Http\Controllers\Backend\AdminController@deleteDrugStore" => "manage_system",
        "App\Http\Controllers\Backend\AdminController@lockDrugStore" => "manage_system",
        "App\Http\Controllers\Backend\AdminController@createOrUpdateDrugStore" => "system",
        "App\Http\Controllers\Backend\AdminController@checkDrugStore" => "system",
        "App\Http\Controllers\Backend\AdminController@copyDrugFromDrugStore" => "system",
        "App\Http\Controllers\Backend\AdminController@filterUser" => "system",
        "App\Http\Controllers\Backend\AdminController@createOrUpdateUser" => "system",
        "App\Http\Controllers\Backend\AdminController@createOrUpdateUnit" => "system",
        "App\Http\Controllers\Backend\AdminController@createOrUpdateDrugDQG" => "system"
    );

    /**
     * Kiểm tra role có đủ điều kiện ko?
     * @param $userInfo
     * @param $routerCheck
     * @return bool
     */
    public static function checkRole($userInfo, $routerCheck)
    {
        $userPermission = RoleUtils::ROLE_MAP[$userInfo->user_role];
        $roleCheck = RoleUtils::PERMISSION_MAP_CONTROLLER[$routerCheck];
        if(in_array('system', json_decode($userInfo->permission))){
            return true;
        }
        if (!isset($userPermission)) {
            $userPermission = $userInfo->permission;
        }
        $sameRole = array_intersect((array)$roleCheck, $userPermission);
        if (isset($sameRole) && sizeof($sameRole) > 0) {
            return true;
        }
        return false;
    }
}
