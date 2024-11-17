<?php

namespace App\Http\Controllers\Backend;

use App\Models\Invoice;
use App\Repositories\AdsTracking\AdsTrackingRepositoryInterface;
use App\LibExtension\CommonConstant;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\InvoiceDetail\InvoiceDetailRepositoryInterface;
use App\Repositories\WarehouseLog\WarehouseLogRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Repositories\Vouchers\VouchersRepositoryInterface;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Services\DashboardService;
use App\Services\ReportService;

class DashboardController extends Controller
{
    protected $className = "Backend\DashboardController";

    protected $invoice;
    protected $invoice_detail;
    protected $drug;
    protected $warehouse_log;
    protected $warehouse;
    protected $voucher;
    protected $dashboard;
    protected $ads_tracking;
    protected $reportServices;

    public function __construct(
        InvoiceRepositoryInterface       $invoice,
        InvoiceDetailRepositoryInterface $invoice_detail,
        DrugRepositoryInterface          $drug,
        WarehouseLogRepositoryInterface  $warehouse_log,
        WarehouseRepositoryInterface     $warehouse,
        VouchersRepositoryInterface      $voucher,
        DashboardService                 $dashboard,
        AdsTrackingRepositoryInterface   $ads_tracking,
        ReportService                    $reportServices

    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->invoice = $invoice;
        $this->invoice_detail = $invoice_detail;
        $this->drug = $drug;
        $this->warehouse_log = $warehouse_log;
        $this->warehouse = $warehouse;
        $this->voucher = $voucher;
        $this->dashboard = $dashboard;
        $this->ads_tracking = $ads_tracking;
        $this->reportServices = $reportServices;
    }

    public function getInvoiceByTime(Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceByTime');

        $user = JWTAuth::toUser($request->token);
        $data = $this->invoice->getInvoiceByMonth($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getInvoiceByDay(Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceByDay');

        $user = JWTAuth::toUser($request->token);
        $data = $this->invoice->getInvoiceByDay($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getInvoiceByWeek(Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceByWeek');

        $weekDate = Carbon::now();
        try {
            $weekDate = Carbon::parse($request->input('date'));
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
        }
        $fromDate = $weekDate->copy()->subDays(6);
        $toDate = $weekDate;

        $user = JWTAuth::toUser($request->token);
        $data = $this->invoice->getInvoiceByWeek($user->drug_store_id, $fromDate->format('Y-m-d'), $toDate->format('Y-m-d'));
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getInvoiceByYear(Request $request)
    {
        LogEx::methodName($this->className, 'getInvoiceByYear');

        $user = JWTAuth::toUser($request->token);
        $year = $request->input('year');
        $data = $this->invoice->getInvoiceByYear($year, $user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getDrugByGroup(Request $request)
    {
        LogEx::methodName($this->className, 'getDrugByGroup');

        $user = JWTAuth::toUser($request->token);
        $data = $this->drug->countDrugByGroup($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getListDrugWaring(Request $request)
    {
        LogEx::methodName($this->className, 'getListDrugWaring');

        $user = JWTAuth::toUser($request->token);
        $data = $this->drug->getListDrugWaring($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getListDrugWaringCombineWarning(Request $request)
    {
        LogEx::methodName($this->className, 'getListDrugWaringCombineWarning');

        $user = JWTAuth::toUser($request->token);
        $data = $this->drug->getListDrugWaringCombineUnits($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getTopDrug(Request $request)
    {
        LogEx::methodName($this->className, 'getTopDrug');

        $user = JWTAuth::toUser($request->token);
        $input = $request->input();
        $time = $input['time'];
        $type = $input['type'];
        $data = $this->drug->getTopDrug($user->drug_store_id, $time, $type);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getDrugExpired(Request $request)
    {
        LogEx::methodName($this->className, 'getDrugExpired');

        $user = JWTAuth::toUser($request->token);
        $data = $this->drug->getDrugExpired($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getDrugInventory(Request $request)
    {
        LogEx::methodName($this->className, 'getDrugInventory');

        $user = JWTAuth::toUser($request->token);
        $data = $this->drug->getDrugInventory($user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getLogWarehouse(Request $request)
    {
        LogEx::methodName($this->className, 'getLogWarehouse');

        $user = $request->userInfo;
        $current_day = Carbon::now()->format('Y-m-d 00:00:00');
        $log = $this->warehouse_log->getLogCurrentday($current_day, $user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $log);
    }

    public function getChartData(Request $request)
    {
        LogEx::methodName($this->className, 'getChartData');
        $data = Utils::executeRawQuery("select v3.f_dashboard_get_chart_data(?) as result", [Utils::getParams($request->input())]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
    }

    /**
     * api v3
     * form getChartData and f_dashboard_get_chart_data on v3
    */
    public function getChartDataV3(Request $request)
    {
        LogEx::methodName($this->className, 'getChartData');

        $data = $this->dashboardGetChartDataV3($request);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
    }

    public function dashboardGetChartDataV3($request)
    {
        LogEx::methodName($this->className, 'getChartData');

        $p_store_id = $request->userInfo->drug_store_id;

        $tmp_dashboard_get_chart_data_invoice =
            "select d as created_at,
            case when it.invoice_type in ('IV1', 'IV3') then 'IV1' else 'IV2' end as invoice_type,
            coalesce(sum(case when it.invoice_type in ('IV1', 'IV2')
                then 1 else -1 end * (i.pay_amount - coalesce(i.vat_amount, 0))), 0) as amount,
            coalesce(sum(case when it.invoice_type in ('IV1', 'IV2')
                then 1 else -1 end * i.amount), 0) as amount2
            from generate_series(
                        current_date - interval '6 days',
                        current_date, '1 day'::interval
                    ) d
                inner join (
                    select 'IV1' as invoice_type
                    union all
                    select 'IV2'
                    union all
                    select 'IV3'
                    union all
                    select 'IV4'
                ) it
                    on  1 = 1
                left outer join invoice i
                    on  i.invoice_type = it.invoice_type
                    and i.status = 'done'
                    and case when i.invoice_type = 'IV2' then i.created_at else i.receipt_date end::date  = d
                    and i.drug_store_id     = $p_store_id
            group by case when it.invoice_type in ('IV1', 'IV3') then 'IV1' else 'IV2' end, d
            order by case when it.invoice_type in ('IV1', 'IV3') then 'IV1' else 'IV2' end, d";

        $tmp_dashboard_get_chart_data_invoice_detail =
            "select d as created_at,
            coalesce(sum(case
                            when i.invoice_type in ('IV1', 'IV4') then -1
                            when i.invoice_type in ('IV2', 'IV3') then 1
                            else 0
                        end * id.quantity * id.exchange * w.current_cost), 0) as amount,
            coalesce(sum(case
                            when i.invoice_type in ('IV1') then 1
                            when i.invoice_type in ('IV3') then -1
                            else 0
                        end * id.quantity * id.exchange * w.main_cost), 0) as inamount
            from generate_series(
                        current_date - interval '6 days',
                        current_date, '1 day'::interval
                    ) d
                left outer join invoice i
                    on  i.invoice_type      in ('IV1', 'IV2', 'IV3', 'IV4')
                    and i.status            = 'done'
                    and case when i.invoice_type = 'IV2' then i.created_at else i.receipt_date end::date    = d
                    and i.drug_store_id     = $p_store_id
                left outer join invoice_detail id
                    on  id.invoice_id       = i.id
                left outer join warehouse w
                    on  w.drug_id           = id.drug_id
                    and w.is_basic          = 'yes'
                    and w.is_check          = true
            group by d";

        $v_result1 = DB::select("select (jsonb_agg(i.inamount)) as data
            from ($tmp_dashboard_get_chart_data_invoice_detail) i");

        $v_result2 = DB::select("select (jsonb_agg(i.amount)) as data
            from ($tmp_dashboard_get_chart_data_invoice) i
            where i.invoice_type  = 'IV1'");

        $v_result3 = DB::select("select (jsonb_agg(coalesce(t.amount, 0))) as result
            from (
                select  d   as created_at,
                        max(w.amount) - coalesce(sum(i.amount), 0)  as amount
                from    generate_series(current_date - interval '6 days', current_date, '1 day'::interval) d
                    inner join (
                        select  sum(w.current_cost * w.quantity)    as amount
                        from    warehouse w
                        where   w.drug_store_id = $p_store_id
                        and     w.is_basic      = 'yes'
                        and     w.is_check      = false
                        and     w.quantity      > 0
                    ) w
                        on  1 = 1
                    left outer join ($tmp_dashboard_get_chart_data_invoice_detail) i
                        on  i.created_at > d
                group by d
                order by d
            ) t
            ;");

        $v_result3[0]->result = ([($v_result1[0]->data) .",". ($v_result2[0]->data) .",". $v_result3[0]->result]);

        return $v_result3;
    }

    public function getActivities(Request $request)
    {
        LogEx::methodName($this->className, 'getActivities');

        $data = Utils::executeRawQuery("select * from v3.f_dashboard_get_activities(?)", [Utils::getParams($request->input())], $request->url(), $request->input());
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getActivities and f_dashboard_get_activities on v3
    */
    public function getActivitiesV3(Request $request)
    {
        LogEx::methodName($this->className, 'getActivities');

        $data = Utils::executeRawQueryV3(
            $this->dashboardGetActivitiesV3($request),
            $request->url(),
            $request->input()
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function dashboardGetActivitiesV3($request)
    {
        LogEx::methodName($this->className, 'getActivities');

        $drug_store_id = $request->userInfo->drug_store_id;
        $current_date = Carbon::now()->format('Y-m-d');

        return DB::table(DB::raw('invoice i'))
            ->select(
                'i.id as invoice_id',
                'i.invoice_code',
                'i.invoice_type',
                'i.warehouse_action_id as action',
                'i.customer_id',
                DB::raw('coalesce(c.name,\'Khách lẻ\') as customer_name'),
                'i.amount','i.vat_amount','i.discount',
                'i.discount_promotion',
                'i.pay_amount',
                'i.created_by',
                'u.name as creator_name',
                'i.status',
                'i.receipt_date',
                'i.created_at',
                'i.updated_at'
            )
            ->leftJoin(DB::raw('customer c'),function($join) {
                $join->on('c.id','=','i.customer_id')
                    ->where('i.invoice_type','=','IV1');
            })
            ->leftJoin(DB::raw('users u'),'u.id','=','i.created_by')
            ->where('i.drug_store_id', '=', $drug_store_id)
            //->where(DB::raw('coalesce(i.created_at,i.receipt_date)'), '=', $current_date)
            ->where('i.status','=','done')
            ->orderBy('i.updated_at','DESC');
    }

    public function getStatistic(Request $request)
    {
        LogEx::methodName($this->className, 'getStatistic');

        $data = Utils::executeRawQuery("select * from v3.f_dashboard_get_statistic(?)", [Utils::getParams($request->input())], $request->url(), $request->input());
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getStatistic and f_dashboard_get_statistic on v3
    */
    public function getStatisticV3(Request $request)
    {
        LogEx::methodName($this->className, 'getStatisticV3');

        $data = $this->dashboardGetStatisticV3($request);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function dashboardGetStatisticV3($request)
    {
        LogEx::methodName($this->className, 'dashboardGetStatisticV3');

        $p_store_id = $request->userInfo->drug_store_id;
        $v_current_date_start    = (string)Carbon::now()->format('Y-m-d 00:00:00.00000');
        $v_current_date_end      = (string)Carbon::now()->format('Y-m-d 23:59:59.99999');
        $v_current_week_start    = (string)date('Y-m-d 00:00:00.00000', strtotime( Carbon::now()->format('Y-m-d 00:00:00.00000'). ' -6 days'));
        $v_current_month_start   = (string)Carbon::now()->format('Y-m-01 00:00:00.00000');
        $v_current_year_start    = (string)Carbon::now()->format('Y-01-01 00:00:00.00000');

        $lowqtycount = count($this->dashboardGetWarningQuantityDrugV3($request));
        $lowdatecount = count($this->dashboardGetWarningDateDrugV3($request));
        $catalogcount = $this->dashboardGetPromotionDataV3($request)->get()->count();

        return DB::select("    select  coalesce((
                select  sum(case when invoice_type = 'IV1' then pay_amount - vat_amount else -pay_amount end)
                from    invoice
                where   drug_store_id                   = {$p_store_id}
                --and     receipt_date                    BETWEEN  $v_current_date_start AND $v_current_date_end
                and     invoice_type                    in ('IV1', 'IV3')
                and     status                          = 'done'
            ), 0)::numeric                      as dayincome,
            coalesce((
                select  sum(case when invoice_type = 'IV1' then pay_amount - vat_amount else -pay_amount end)
                from    invoice
                where   drug_store_id                   = {$p_store_id}
                --and     receipt_date                    BETWEEN  $v_current_week_start AND $v_current_date_end
                and     invoice_type                    in ('IV1', 'IV3')
                and     status                          = 'done'
            ), 0)::numeric                      as weekincome,
            coalesce((
                select  sum(case when invoice_type = 'IV1' then pay_amount - vat_amount else -pay_amount end)
                from    invoice
                where   drug_store_id                   = {$p_store_id}
                --and     receipt_date                    BETWEEN  $v_current_month_start AND $v_current_date_end
                and     invoice_type                    in ('IV1', 'IV3')
                and     status                          = 'done'
            ), 0)::numeric                      as monthincome,
            coalesce((
                select  sum(case when invoice_type = 'IV1' then pay_amount - vat_amount else -pay_amount end)
                from    invoice
                where   drug_store_id                   = {$p_store_id}
                --and     receipt_date                    BETWEEN  $v_current_year_start AND $v_current_date_end
                and     invoice_type                    in ('IV1', 'IV3')
                and     status                          = 'done'
            ), 0)::numeric                      as yearincome,
            coalesce((
                ({$lowdatecount})
            ), 0)::numeric                      as lowdatecount,
            coalesce((
                ({$lowqtycount})
            ), 0)::numeric                      as lowqtycount,
            coalesce((
                select  count(*)
                from    t_order o
                    inner join t_order_detail d
                        on  d.order_id  = o.id
                where   o.drug_store_id = {$p_store_id}
                and     o.status        not in ('done', 'cancel')
            ), 0)::numeric                      as ordercount,
            coalesce((
                ({$catalogcount})
            ), 0)::numeric                      as catalogcount
           ");
    }

    public function getWarningQuantityItems(Request $request)
    {
        LogEx::methodName($this->className, 'getWarningQuantityItems');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery("select * from v3.f_dashboard_get_warning_quantity_drug(?)", [Utils::getParams($requestInput)], $request->url(), $requestInput);
        if (isset($requestInput['detail']) && $requestInput['detail'] == 'true') {
            $data = json_decode(str_replace(['"units":"', '","numbers":', '"numbers":"', ']"}', '\"'], ['"units":', ',"numbers":', '"numbers":', ']}', '"'], json_encode($data)));
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getWarningQuantityItems and f_dashboard_get_warning_quantity_drug on v3
    */
    public function getWarningQuantityItemsV3(Request $request)
    {
        LogEx::methodName($this->className, 'getWarningQuantityItemsV3');

        $data = $this->dashboardGetWarningQuantityDrugV3($request);
        //if (isset($requestInput['detail']) && $requestInput['detail'] == 'true') {
        $data = json_decode(str_replace(['"units":"', '","numbers":', '"numbers":"', ']"}', '\"'], ['"units":', ',"numbers":', '"numbers":', ']}', '"'], json_encode($data)));
        //}
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function dashboardGetWarningQuantityDrugV3($request)
    {
        LogEx::methodName($this->className, 'dashboardGetWarningQuantityDrugV3');

        $p_drug_store_id = $request->userInfo->drug_store_id;

        $tmp_warning = "select *
            from (
                select  distinct on (d.id)
                        d.id,
                        d.drug_store_id,
                        dg.name as drug_store_name,
                        d.drug_category_id,
                        d.drug_group_id,
                        d.name,
                        d.drug_code,
                        d.barcode,
                        d.short_name,
                        d.substances,
                        d.concentration,
                        d.country,
                        d.company,
                        d.registry_number,
                        d.package_form,
                        d.image,
                        d.warning_unit,
                        u.name as warning_unit_name,
                        coalesce(d.warning_quantity_min, 0) as warning_quantity_min,
                        d.warning_quantity_max,
                        floor(sum(coalesce(w.quantity,0) / b.exchange) over (partition by d.id)) as quantity,
                        floor(sum(coalesce(w.quantity,0)) over (partition by d.id)) as base_quantity
                from drug d
                    left outer join warehouse b
                        on  b.drug_id       = d.id
                        and b.is_check      = true
                        and b.unit_id       = d.warning_unit
                    left outer join warehouse w
                        on  w.drug_id       = d.id
                        and w.is_check      = false
                        and w.is_basic      = 'yes'
                    inner join unit u
                        on  u.id            = d.warning_unit
                    inner join drugstores dg
                        on dg.id            = d.drug_store_id
                where   d.drug_store_id     = {$p_drug_store_id}
                and     d.active            = 'yes'
            ) t
            where   t.warning_quantity_min  > t.quantity";

        $tmp_drug_units = "
            select w.drug_id,
                jsonb_agg(jsonb_build_object(
                        'unit_id', u.id,
                        'unit_name', u.name,
                        'is_basic', w.is_basic,
                        'exchange', w.exchange,
                        'pre_cost', w.pre_cost,
                        'main_cost', w.main_cost,
                        'current_cost', w.current_cost
                    )) as units
            from warehouse w
                inner join ({$tmp_warning}) t
                    on  t.id        = w.drug_id
                    and w.is_check  = true
                inner join unit u
                    on  u.id        = w.unit_id
            group by w.drug_id";

        $tmp_drug_numbers = "
            select  w.drug_id,
                jsonb_agg(jsonb_build_object(
                        'number', w.number,
                        'expiry_date', w.expiry_date,
                        'quantity', w.quantity
                    )) as numbers
            from warehouse w
                inner join ({$tmp_warning}) t
                    on  t.id        = w.drug_id
                    and w.is_check  = false
                    and w.is_basic  = 'yes'
            group by w.drug_id
        ";

        return DB::select("
            select  t.*,
                u.units,
                n.numbers
            from ({$tmp_warning}) t
                left outer join ({$tmp_drug_units}) u
                    on u.drug_id   = t.id
                left outer join ({$tmp_drug_numbers}) n
                    on n.drug_id   = t.id
            order by t.warning_quantity_min - t.quantity desc
        ");
    }

    /**
     * api v3
     * from dashboardGetWarningQuantityDrugV3
    */
    public function dashboardGetWarningQuantityDrugNewV3(Request $request)
    {
        LogEx::methodName($this->className, 'dashboardGetWarningQuantityDrugNewV3');

        $search = $request->input('query') ?? null;

        $tmp_warning = "select *
            from (
                select  distinct on (d.id)
                        d.id,
                        --d.drug_store_id,
                        dg.name as drug_store_name,
                        d.drug_category_id,
                        d.drug_group_id,
                        d.name,
                        d.drug_code,
                        d.barcode,
                        d.short_name,
                        d.substances,
                        d.concentration,
                        d.country,
                        d.company,
                        d.registry_number,
                        d.package_form,
                        d.image,
                        d.warning_unit,
                        u.name as warning_unit_name,
                        coalesce(d.warning_quantity_min, 0) as warning_quantity_min,
                        d.warning_quantity_max,
                        floor(sum(coalesce(w.quantity,0) / b.exchange) over (partition by d.id)) as quantity,
                        floor(sum(coalesce(w.quantity,0)) over (partition by d.id)) as base_quantity
                from drug d
                    left outer join warehouse b
                        on  b.drug_id       = d.id
                        and b.is_check      = true
                        and b.unit_id       = d.warning_unit
                    left outer join warehouse w
                        on  w.drug_id       = d.id
                        and w.is_check      = false
                        and w.is_basic      = 'yes'
                    inner join unit u
                        on  u.id            = d.warning_unit
                    inner join drugstores dg
                        on dg.id            = d.drug_store_id
                        and dg.type         = 'GDP'    
                and     d.active            = 'yes'
                --and     coalesce(d.warning_quantity_min, 0) > (coalesce(w.quantity,0) / b.exchange)
                --and     (coalesce(w.quantity,0) / b.exchange) >= 0
                and     (
                            d.drug_code ~* '{$search}'
                            or d.barcode ~* '{$search}'
                            or d.name ~* '{$search}'
                        )
            ) t
            where   t.warning_quantity_min  > t.quantity
            --group by t.id, t.drug_code, t.barcode, t.name, t.quantity, t.warning_quantity_min
            ";

        $tmp_drug_units = "
            select w.drug_id,
                jsonb_agg(jsonb_build_object(
                        'unit_id', u.id,
                        'unit_name', u.name,
                        'is_basic', w.is_basic,
                        'exchange', w.exchange,
                        'pre_cost', w.pre_cost,
                        'main_cost', w.main_cost,
                        'current_cost', w.current_cost
                    )) as units
            from warehouse w
                inner join ({$tmp_warning}) t
                    on  t.id        = w.drug_id
                    and w.is_check  = true
                inner join unit u
                    on  u.id        = w.unit_id
            group by w.drug_id";

        $tmp_drug_numbers = "
            select  w.drug_id,
                jsonb_agg(jsonb_build_object(
                        'number', w.number,
                        'expiry_date', w.expiry_date,
                        'quantity', w.quantity
                    )) as numbers
            from warehouse w
                inner join ({$tmp_warning}) t
                    on  t.id        = w.drug_id
                    and w.is_check  = false
                    and w.is_basic  = 'yes'
            group by w.drug_id
        ";

        $perPage = 10;
        $page = $request->input('page') ?? 1;
        $offset = ($page - 1) * $perPage;

        $total = count(
            DB::select("
            select  t.*,
                u.units,
                n.numbers
            from ({$tmp_warning}) t
                left outer join ({$tmp_drug_units}) u
                    on u.drug_id   = t.id
                left outer join ({$tmp_drug_numbers}) n
                    on n.drug_id   = t.id
            order by t.warning_quantity_min - t.quantity desc")
        );

        $data = DB::select("
            select  t.*,
                u.units,
                n.numbers
            from ({$tmp_warning}) t
                left outer join ({$tmp_drug_units}) u
                    on u.drug_id   = t.id
                left outer join ({$tmp_drug_numbers}) n
                    on n.drug_id   = t.id
            order by t.warning_quantity_min - t.quantity desc
            limit {$perPage} offset {$offset}
        ");

        $data = json_decode(str_replace(['"units":"', '","numbers":', '"numbers":"', ']"}', '\"'], ['"units":', ',"numbers":', '"numbers":', ']}', '"'], json_encode($data)));

        return \App\Helper::successResponse(
            CommonConstant::SUCCESS_CODE,
            CommonConstant::MSG_SUCCESS,
            new LengthAwarePaginator($data, $total, 10, 1, ['path' => $request->url()])
        );
    }

    /**
     * api v3
     * from dashboardGetDrugStoreByDrugV3
     */
    public function dashboardGetDrugStoreByDrugV3(Request $request)
    {
        LogEx::methodName($this->className, 'dashboardGetDrugStoreByDrugV3');

        $listDrugs = $request->input('drugstorelist');
        $tmpStores = DB::table(
            DB::raw('drug d'))
            ->distinct()
            ->select(
                'dg.id',
                'dg.name'
            )
            ->join(DB::raw('drugstores dg'), function ($join) {
                $join->on('dg.id', '=', 'd.drug_store_id')
                    ->where('dg.type', '=', 'GDP');
            })
            ->where('d.active', '=', 'yes')
            ->whereIn('d.id', $listDrugs)
            ->groupBy(['dg.id', 'dg.name'])
            ->get()->toArray();

        if (count($listDrugs) == 1 && count($tmpStores) > 0 ||
            count($listDrugs) > 1 && count($tmpStores) == 1) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $tmpStores);
        } else {
            return \App\Helper::successResponse(
                CommonConstant::UNPROCESSABLE_ENTITY,
                'Không có NCC nào có đủ hàng theo yêu cầu',
                []
            );
        }

    }
        public function getWarningDateItems(Request $request)
    {
        LogEx::methodName($this->className, 'getWarningDateItems');

        $data = Utils::executeRawQuery("select * from v3.f_dashboard_get_warning_date_drug(?)", [Utils::getParams($request->input())], $request->url(), $request->input());
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from f_dashboard_get_warning_date_drug on v3 and getWarningDateItems
    */
    public function getWarningDateItemsV3(Request $request)
    {
        LogEx::methodName($this->className, 'getWarningDateItemsV3');

        $data = $this->dashboardGetWarningDateDrugV3($request);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function dashboardGetWarningDateDrugV3(Request $request)
    {
        LogEx::methodName($this->className, 'getWarningDateItems');

        $p_store_id = $request->userInfo->drug_store_id;

        return DB::select("
            select  d.id,
            d.name,
            d.drug_code,
            d.barcode,
            w.number,
            w.expiry_date::date                     as expiry_date,
            w.quantity,
            case
                when d.warning_days is not null
                    and d.warning_days > 0
                    then d.warning_days
                when s.warning_date is not null
                    and s.warning_date > 0
                    then s.warning_date
                else 180
            end::bigint                             as warning_days
            from    warehouse w
                inner join drug d
                    on  d.id        = w.drug_id
                    and d.active    = 'yes'
                inner join drugstores s
                    on  s.id    = w.drug_store_id
            where   w.drug_store_id = {$p_store_id}
            and     w.is_check      = false
            and     w.is_basic      = 'yes'
            and     w.quantity      > 0
            and     w.expiry_date   < current_date +  case
                                                            when d.warning_days is not null
                                                                and d.warning_days > 0
                                                                then d.warning_days
                                                            when s.warning_date is not null
                                                                and s.warning_date > 0
                                                                then s.warning_date
                                                            else 180
                                                        end::int
            order by w.expiry_date");
    }

    public function getPromotionData(Request $request)
    {
        LogEx::methodName($this->className, 'getPromotionData');
        $data = Utils::executeRawQuery("select * from v3.f_dashboard_get_promotion_data(?)", [Utils::getParams($request->input())], $request->url(), $request->input());
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from getPromotionData and dashboardGetPromotionDataV3
    */
    public function getPromotionDataV3(Request $request)
    {
        LogEx::methodName($this->className, 'getPromotionDataV3');

        $data = Utils::executeRawQueryV3(
            $this->dashboardGetPromotionDataV3($request),
            $request->url(),
            $request->input()
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function dashboardGetPromotionDataV3($request)
    {
        LogEx::methodName($this->className, 'dashboardGetPromotionDataV3');

        $p_store_id = $request->userInfo->drug_store_id;
        $searchText = $request->input('search') ?? null;

        $queryDB = '1 = 1';

        if (!empty($searchText)) $queryDB = $queryDB . " AND (d.name ~* '" . $searchText .
            "' or d.unit_name  ~* '" . $searchText . "' or d.company  ~* '" . $searchText ."')";

        $subQuery = DB::table(DB::raw('drug d'))
            ->distinct()
            ->select(
                DB::raw('on (d.id)
                d.*'),
                'u.name as unit_name',
                'w.current_cost',
                'w.exchange',
                'w.is_basic'
            )
            ->join(DB::raw('warehouse w'), function($join) {
                $join->on('w.drug_id','=','d.id')
                    ->whereRaw('w.is_check = true');
            })
            ->join(DB::raw('unit u'),'u.id','=','w.unit_id')
            ->where('d.drug_store_id', '=', $p_store_id)
            //->where('d.active','=','yes')
            ->orderBy('d.id','ASC')
            ->orderBy('w.exchange','DESC')
            ->orderBy('w.is_basic','DESC');

        $sql_with_bindings = str_replace_array('?', $subQuery->getBindings(), $subQuery->toSql());

        return DB::table(DB::raw("($sql_with_bindings) as d"))
            ->select(
                'd.id',
                'd.name',
                'd.package_form',
                'd.unit_name',
                'd.company',
                'd.concentration',
                'd.substances',
                'd.company as distributer',
                'd.current_cost'
            )
            ->whereRaw($queryDB);
    }

    public function addViews(Request $request)
    {
        LogEx::methodName($this->className, 'addView');

        $data = $this->dashboard->addViews($request);
//        if ($data > 0) {
//            $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
//        } else {
//            $resp = $this->responseApi(CommonConstant::ERROR_DATA_CODE, Utils::getErrorMessage('drug', $data), null);
//        }
//        return response()->json($resp);
    }

    public function getAdsTracking(Request $request)
    {
        LogEx::methodName($this->className, 'reportDrugByType');

        //$user = $request->userInfo;
        //$type = $request->input('type');
        $user = $request->userInfo;
        $data = $this->ads_tracking->filter($request,$user->drug_store_id);

        $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        return response()->json($resp);

    }

    /**
     * api v3
     * exportAdsTracking
    */
    public function exportAdsTracking(Request $request)
    {
        LogEx::methodName($this->className, 'exportAdsTracking');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = $this->ads_tracking->filter($request,$user->drug_store_id);

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $data;
                    break;
                case "current_page":
                    $data = $data;
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = $data;
                    break;
            }
        }

        $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);

        return response()->json($resp);
    }

    /**
     * api v3
     * invoiceViews
     */
    public function invoiceViews(Request $request)
    {
        LogEx::methodName($this->className, 'invoiceViews');

        $user = $request->userInfo;
        $drugStoreId = $request->userInfo->drug_store_id;
        $requestInput = $request->input();

        $invoices = DB::table('invoice')
            ->select('status', DB::raw('COUNT(status) AS count'))
            ->where('drug_store_id', '=', $drugStoreId)
            ->groupBy('status')
            ->get()->toArray();

        //tinh % status
        $ceiledInvoice = [];
        $ceiledInvoice['done'] = 0;
        $ceiledInvoice['miss_pay'] = 0;
        //$totalInvoce = array_sum(array_column($invoices, 'count'));
        $totalInvoce = 0;
        //'pending', 'done', 'processing', 'temp', 'cancel'
        $keyReport = ['pending', 'done', 'processing'];
        foreach ($invoices as $item) {
            if (in_array($item->status, $keyReport)) {
                $totalInvoce += $item->count;
                if ($item->status == 'done')
                    $ceiledInvoice['done'] += $item->count;
            }
        }
        $ceiledInvoice['done'] = ($ceiledInvoice['done']/$totalInvoce)*100;
        $ceiledInvoice['miss_pay'] = 100 - $ceiledInvoice['done'];

        //so sanh don ban voi ngay hom qua
        $toDayYesterDay = [];
        $toDay = Carbon::now()->format('Y-m-d');
        $yesTerDay = date('Y-m-d', strtotime("-1 days"));

        $invoices = DB::table('invoice')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(created_at) AS count'))
            ->where('drug_store_id', '=', $drugStoreId)
            ->where( function ($query) use ($toDay, $yesTerDay) {
                $query->whereDate(DB::raw('Date(created_at)'), $toDay)
                    ->orWhereDate(DB::raw('Date(created_at)'), $yesTerDay);
            })
            ->groupBy(DB::raw('Date(created_at)'))
            ->get()->toArray();

        $invoiceIV3 = DB::table('invoice')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(created_at) AS count'))
            ->where('drug_store_id', '=', $drugStoreId)
            ->where('invoice_type', '=', 'IV3')
            ->where( function ($query) use ($toDay, $yesTerDay) {
                $query->whereDate(DB::raw('Date(created_at)'), $toDay)
                    ->orWhereDate(DB::raw('Date(created_at)'), $yesTerDay);
            })
            ->groupBy(DB::raw('Date(created_at)'))
            ->get()->toArray();

        if (count($invoices) == 0)
            $toDayYesterDay['hoa_don'] = [
                'phan_tram' => 'Không có hóa đơn được thực hiện',
                'up' => 0
            ];

        if (count($invoices) > 0) {
            if (empty($invoices[1]->count) || empty($invoices[0]->count)) {
                if (!empty($invoices[1]->count))
                    $toDayYesterDay['hoa_don'] = [
                        'phan_tram' => 'Không có hóa đơn được thực hiện trong ngày: '. $toDay,
                        'up' => 0
                    ];
                if (!empty($invoices[0]->count))
                    $toDayYesterDay['hoa_don'] = [
                        'phan_tram' => 'Không có hóa đơn được thực hiện trong ngày: '. $yesTerDay,
                        'up' => 0
                    ];
            } else {
                $toDayYesterDay['hoa_don'] = [
                    'phan_tram' => (($invoices[1]->count - $invoices[0]->count)*100)/($invoices[0]->count),
                    'up' => ($invoices[1]->count - $invoices[0]->count)
                ];
            }
        }

        if (count($invoiceIV3) == 0)
            $toDayYesterDay['tra_hang'] = [
                'phan_tram' => 'Không có hóa đơn được thực hiện',
                'up' => 0
            ];

        if (count($invoiceIV3) > 0) {
            if (empty($invoiceIV3[1]->count) || empty($invoiceIV3[0]->count)) {
                if (!empty($invoiceIV3[1]->count))
                    $toDayYesterDay['tra_hang'] = [
                        'phan_tram' => 'Không có hóa đơn được thực hiện trong ngày: '. $toDay,
                        'up' => 0
                    ];
                if (!empty($invoiceIV3[0]->count))
                    $toDayYesterDay['tra_hang'] = [
                        'phan_tram' => 'Không có hóa đơn được thực hiện trong ngày: '. $yesTerDay,
                        'up' => 0
                    ];
            } else {
                $toDayYesterDay['tra_hang'] = [
                    'phan_tram' => (($invoiceIV3[1]->count - $invoiceIV3[0]->count)*100)/($invoiceIV3[0]->count),
                    'up' => ($invoiceIV3[1]->count - $invoiceIV3[0]->count)
                ];
            }
        }
        //kho hang
        $sanPhamDinhMuc = 0;
        $soLuong = 0;
        $tongtien = 0;
        $thongTinKho = DB::table("warehouse")
            ->select(
                'warehouse.id',
                'drug.drug_code',
                DB::raw('drug.name as drug_name'),
                'warehouse.number',
                'warehouse.expiry_date',
                'warehouse.unit_id',
                DB::raw('unit.name as unit_name'),
                'warehouse.quantity',
                'warehouse.main_cost',
                'warehouse.current_cost',
                'drug.package_form',
                DB::raw("warehouse.main_cost * warehouse.quantity as total_buy"),
                DB::raw("warehouse.current_cost * warehouse.quantity as total_sell")
            )
            ->join('drug', function ($join) {
                $join->on('drug.id', '=', 'warehouse.drug_id')
                    ->where('drug.active', '=', 'yes');
            })
            ->join("unit", "unit.id", "warehouse.unit_id")
            ->where('warehouse.drug_store_id', $drugStoreId)
            ->where('drug.drug_store_id', $drugStoreId)
            ->where("warehouse.is_check", "=", false)
            ->where("warehouse.is_basic", "=", "yes")
            ->where("warehouse.quantity", ">=", 1)
            ->orderBy("drug.name")
            ->orderBy("warehouse.expiry_date")
            ->get()
            ->chunk(50);

        foreach ($thongTinKho as $infos) {
            foreach ($infos as $info) {
                if ($info->expiry_date <= $toDay) $sanPhamDinhMuc += 1;
                $soLuong += $info->quantity;
                $tongtien += $info->total_buy;
            }
        }
        //lich su thanh toan
        $lichSu = [
            'total' => $totalInvoce,
            'danh_sach' => DB::table('invoice')
                ->select('*')
                ->where('drug_store_id', '=', $drugStoreId)
                ->limit(10)
                ->get()->toArray()
        ];
        $charInvoice = [
            'tong_so' => $totalInvoce,
            'phan_tram' => $ceiledInvoice,
            'so_sanh' => $toDayYesterDay,
            'kho_hang' => [
                'dinh_muc' => $sanPhamDinhMuc,
                'so_luong' => $soLuong,
                'tong_tien' => $tongtien
            ],
            'lich_su' => $lichSu
        ];

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $charInvoice);
    }

    /**
     * api v3
     * reportMoney
     */
    public function reportMoney(Request $request)
    {
        LogEx::methodName($this->className, 'reportMoney');

        $drugStoreId = $request->userInfo->drug_store_id;
        $requestInput = $request->input();
        $week = $requestInput['week'] ?? null;
        $month = $requestInput['month'] ?? null;
        $toDate = Carbon::now()->format('Y-m-d');

        $fromDate = ($week == 7) ?
            date('Y-m-d', strtotime("-7 days")) :
            date('Y-m-d', strtotime("-". $month ." days"));

        $datas = DB::table(DB::raw('invoice i'))
            ->select(
                'i.receipt_date as created_at',
                DB::raw('SUM(case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.amount - i.discount - coalesce(i.discount_promotion, 0) end) as sumamount')
            )
            ->where('i.drug_store_id', '=', $drugStoreId)
            ->where('i.status','=','done')
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->where(function ($query) use ($fromDate, $toDate) {
                    $query->where('i.invoice_type', '=','IV1')
                        ->whereBetween('i.receipt_date', [$fromDate, $toDate]);
                })
                    ->orWhere(function ($query) use ($fromDate, $toDate) {
                        $query->where('i.invoice_type', '=', 'IV3')
                            ->whereBetween('i.receipt_date', [$fromDate,  $toDate]);
                    });
            })
            ->groupBy('i.receipt_date')
            ->orderBy('i.receipt_date', 'DESC')
            ->get();

        $rangArray = [];
        $Date1 = $fromDate;
        $Date2 = $toDate;
        $Variable1 = strtotime($Date1);
        $Variable2 = strtotime($Date2);

        for ($currentDate = $Variable2; $currentDate >= $Variable1; $currentDate -= (86400)) {
            $Store = date('Y-m-d 00:00:00', $currentDate);
            $rangArray[] = ["created_at" => $Store, "sumamount" => 0];
        }

        foreach ($rangArray as $key => $item) {
            foreach ($datas as $data) {
                if ($item['created_at'] == $data->created_at) $rangArray[$key] = get_object_vars($data);
            }
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $rangArray);
    }

    /**
     * api v3
     * invoiceTodayWeek
     */
    public function invoiceTodayWeek(Request $request)
    {
        LogEx::methodName($this->className, 'invoiceTodayWeek');

        $drugStoreId = $request->userInfo->drug_store_id;
        $requestInput = $request->input();
        $week = $requestInput['week'] ?? null;
        $month = $requestInput['month'] ?? null;
        $toDate = Carbon::now()->toDateTimeString();

        $fromDate = ($week == 7) ?
            date('Y-m-d', strtotime("-7 days")) :
            date('Y-m-d', strtotime("-". $month ." days"));

        $weeks = DB::table(DB::raw('invoice i'))
            ->select(
                'i.receipt_date as created_at',
                DB::raw('SUM(case
                when i.invoice_type = \'IV3\' then + 1
                else + 0 end) as sum_iv3'),
                DB::raw('SUM(case
                when i.invoice_type = \'IV1\' then + 1
                else + 0 end) as sum_iv1'),
                DB::raw('SUM(1) as total_invoice')
            )
            ->where('i.drug_store_id', '=', $drugStoreId)
            ->where('i.status','=','done')
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->where(function ($query) use ($fromDate, $toDate) {
                    $query->where('i.invoice_type', '=','IV1')
                        ->whereBetween('i.receipt_date', [$fromDate, $toDate]);
                })
                    ->orWhere(function ($query) use ($fromDate, $toDate) {
                        $query->where('i.invoice_type', '=', 'IV3')
                            ->whereBetween('i.receipt_date', [$fromDate,  $toDate]);
                    });
            })
            ->groupBy('i.receipt_date')
            ->orderBy('i.receipt_date', 'DESC')
            ->get();

        $rangArray = [];
        $Date1 = $fromDate;
        $Date2 = $toDate;
        $Variable1 = strtotime($Date1);
        $Variable2 = strtotime($Date2);

        for ($currentDate = $Variable2; $currentDate >= $Variable1; $currentDate -= (86400)) {
            $Store = date('Y-m-d 00:00:00', $currentDate);
            $rangArray[] = [
                "created_at" => $Store,
                "sumamount" => 0,
                "sum_iv3" => 0,
                "sum_iv1" => 0,
                "total_invoice" => 0
            ];
        }

        foreach ($rangArray as $key => $item) {
            foreach ($weeks as $data) {
                if ($item['created_at'] == $data->created_at) $rangArray[$key] = get_object_vars($data);
            }
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $rangArray);
    }

    /**
     * api v3
     * topProduct
     */
    public function topProduct(Request $request)
    {
        LogEx::methodName($this->className, 'topProduct');

        $drugStoreId = $request->userInfo->drug_store_id;
        $requestInput = $request->input();
        $week = $requestInput['week'] ?? null;
        $month = $requestInput['month'] ?? null;
        $sortBy = $requestInput['sort_by'] ?? null;
        $toDate = Carbon::now()->format('Y-m-d');

        $fromDate = ($week == 7) ?
            date('Y-m-d', strtotime("-7 days")) :
            date('Y-m-d', strtotime("-". $month ." days"));

        $datas = DB::table(DB::raw('invoice i'))
            ->select(
                'invoice_detail.drug_id',
                DB::raw("(select name from drug where drug.id = invoice_detail.drug_id) as drug_name"),
                DB::raw('SUM(1) as total_invoice'),
                DB::raw('SUM(invoice_detail.quantity) as total_quantity'),
                DB::raw('SUM(case
                when i.invoice_type = \'IV3\' then - i.amount
                else i.amount - i.discount - coalesce(i.discount_promotion, 0)
            end) as sumamount')
            )
            ->leftJoin('invoice_detail', 'i.id', '=', 'invoice_detail.invoice_id')
            ->where('i.drug_store_id', '=', $drugStoreId)
            ->where('i.status','=','done')
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->where(function ($query) use ($fromDate, $toDate) {
                    $query->where('i.invoice_type', '=','IV1')
                        ->whereBetween('i.receipt_date', [$fromDate, $toDate]);
                });
            })
            ->groupBy('invoice_detail.drug_id')
            ->when($sortBy, function ($query) use ($sortBy) {
                $query->orderBy($sortBy, 'DESC');
            })
            ->limit(10)
            ->get();

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $datas);
    }

    /**
     * api v3
     * realRevenue
     */
    public function realRevenue(Request $request)
    {
        LogEx::methodName($this->className, 'realRevenue');

        $user = $request->userInfo;
        $form_date = date('Y-m-d', strtotime("-13 days"));
        $to_date = Carbon::now()->format('Y-m-d');
        $datas = [];

        $query = $this->reportServices->reportRevenueV3Mobile($user->drug_store_id, $form_date, $to_date);
        $weeks = $query
            ->get()
            ->toArray();

        $toDate = Carbon::now()->format('Y-m-d');
        $fromDate = date('Y-m-d', strtotime("-13 days"));
        $rangArray = [];
        $Date1 = $fromDate;
        $Date2 = $toDate;
        $Variable1 = strtotime($Date1);
        $Variable2 = strtotime($Date2);

        for ($currentDate = $Variable2; $currentDate >= $Variable1; $currentDate -= (86400)) {
            $Store = date('Y-m-d 00:00:00', $currentDate);
            $rangArray[] = [
               "created_at" => $Store,
               "drug_code" => 0,
               "cash_amount" => 0,
               "not_cash_amount" => 0,
               "direct_amount" => 0,
               "not_direct_amount" => 0,
               "vat_amount" => 0,
               "amount" => 0,
               "discount" => 0,
               "total" => 0,
               "debt" => 0,
               "sumamount" => 0
            ];
        }

        foreach ($rangArray as $key => $item) {
            foreach ($weeks as $data) {
                if ($item['created_at'] == $data->created_at) $rangArray[$key] = get_object_vars($data);
            }
        }

        /**
         cash_amount = Thu tiền mặt
         not_direct_amount = Doanh thu bán Online/COD
         vat_amount = vat
         amount = Doanh thu trước giảm giá
         discount = Giảm giá
         total = Doanh thu chưa công nợ
         debt = Công nợ
         sumamount' = Tổng doanh thu
         direct_amount' = Doanh thu bán trực tiếp
         tong thanh toan = Doanh thu chưa công nợ
        */
        $currentWeek = array_chunk($rangArray, 7)[0];
        $beforeWeek = array_chunk($rangArray, 7)[1];
        $sumCurrentWeek = [
            'sumamount' => array_sum(array_column($currentWeek, 'sumamount')),
            'total' => array_sum(array_column($currentWeek, 'total'))
        ];
        $sumBeforeWeek = [
            'sumamount' => array_sum(array_column($beforeWeek, 'direct_amount'))
        ];

        $growth = (!empty($sumCurrentWeek['sumamount']) && $sumBeforeWeek['sumamount']) ?
            [
                'growth' => (($sumCurrentWeek['sumamount'] - $sumBeforeWeek['sumamount']) / $sumBeforeWeek['sumamount']) * 100,
                'money' => ($sumCurrentWeek['sumamount'] - $sumBeforeWeek['sumamount'])
            ] :
            null;

        $datas = [
            'math' => $sumCurrentWeek ?? null,
            'chart' => $currentWeek ?? null,
            'diffchecker' => $growth
        ];

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $datas);
    }

    /**
     * api v3
     * gumRevenue
     */
    public function gumRevenue(Request $request)
    {
        LogEx::methodName($this->className, 'gumRevenue ');

        $weeks = $this->reportServices->filterRevenueProfit($request->input(), $request->userInfo->drug_store_id);

        $rangArray = [];
        $date1 = date('Y-m-d', strtotime("-6 days"));
        $date2 = Carbon::now()->toDateString();
        $variable1 = strtotime($date1);
        $variable2 = strtotime($date2);

        for ($currentDate = $variable2; $currentDate >= $variable1; $currentDate -= (86400)) {
            $store = date('Y-m-d', $currentDate);
            $rangArray[] = [
                "created_at" => $store,
                "revenue" => 0,
                "cost" => 0,
                "profit" => 0
            ];
        }

        foreach ($rangArray as $key => $item) {
            foreach ($weeks['chart'] as $data) {
                if ($item['created_at'] == $data->created_at) $rangArray[$key] = get_object_vars($data);
            }
        }

        $weeks['chart'] = $rangArray;

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $weeks);
    }
}
