<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/18/2018
 * Time: 9:17 AM
 */

namespace App\Repositories\AdsTracking;

use App\LibExtension\Utils;
use App\Models\AdsTracking;
use App\Models\Vouchers;
use App\Models\Customer;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;

class AdsTrackingEloquentRepository extends AbstractBaseRepository implements AdsTrackingRepositoryInterface
{
    protected $className = "AdsTrackingEloquentRepository";
    public function __construct(AdsTracking $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function filter($request,$dug_store_id, $limit = null)
    {
        LogEx::methodName($this->className, 'filter');
        $limit = $limit ?? $request["limit"] ?? 10;
        $fromDate = $request["from"] ?? null;
        $toDate = $request["to"] ?? null;
        $adsLink = $request["ads_link"] ?? null;
        $action = $request["action"] ?? null;
        $queryDB = '1 = 1';
        LogEx::info($adsLink);
        if (isset($fromDate)) {
            $queryDB = $queryDB . " AND ads_tracking.created_time >= '" . $fromDate . " 00:00:00.000000'";
        }
        if (isset($toDate)) {
            $queryDB = $queryDB . " AND ads_tracking.created_time <= '" . $toDate . " 23:59:59.999999'";
        }
        if (isset($adsLink)) {
            $queryDB = $queryDB . " AND (ads_tracking.banner ~* '" . $adsLink ."')";
        }
        if (isset($action)) {
            $queryDB = $queryDB . " AND (ads_tracking.action_name ~* '" . $action ."')";
        }
        $data_result = array();

        $data = DB::table("ads_tracking")
            ->select(

                'ads_tracking.id',
                'ads_tracking.banner',
                'users.name',
                'ads_tracking.created_time',
                'ads_tracking.action_name'
            )
            ->join("users", "users.id", "ads_tracking.account")
            ->whereRaw($queryDB)
            ->paginate($limit);
        if(isset($fromDate) && isset($toDate) )
        {
            $user_id = DB::select("SELECT DISTINCT account FROM ads_tracking WHERE created_time >= '"
                . $fromDate . " 00:00:00.000000' AND created_time <= '" . $toDate . " 00:00:00.000000'");
            $sum_data = DB::table("ads_tracking")
            ->select(
                DB::raw("(SELECT COUNT(*) FROM ads_tracking WHERE action_name = 'view' AND created_time >= '"
                    . $fromDate . " 00:00:00.000000' AND created_time <= '" . $toDate . " 00:00:00.000000' ) as views"),
                DB::raw("(SELECT COUNT(*) FROM ads_tracking WHERE action_name = 'click' AND created_time >= '"
                    . $fromDate . " 00:00:00.000000' AND created_time <= '" . $toDate . " 00:00:00.000000' ) as click"),
                DB::raw("(SELECT COUNT(*) FROM ads_tracking WHERE action_name = 'view' AND created_time >= '"
                    . $fromDate . " 00:00:00.000000' AND created_time <= '" . $toDate . " 00:00:00.000000' ) * 0.0002777 as time")
            )
            ->whereRaw($queryDB)
            ->paginate($limit);
        }
        else{
            $sum_data = DB::table("ads_tracking")
                ->select(
                    DB::raw("(SELECT COUNT(*) FROM ads_tracking WHERE action_name = 'view' ) as views"),
                    DB::raw("(SELECT COUNT(*) FROM ads_tracking WHERE action_name = 'click') as click"),
                    DB::raw("(SELECT COUNT(*) FROM ads_tracking WHERE action_name = 'view' ) * 0.0002777 as time")
                )
                ->whereRaw($queryDB)
                ->paginate($limit);
            $user_id = DB::select("SELECT DISTINCT account FROM ads_tracking");
        }

        $sum_user = count($user_id);
        $data_result['data'] = $data;
        $data_result['sum_data'] = $sum_data;
        $data_result['sum_user'] = $sum_user;
        return $data_result;
    }
}
