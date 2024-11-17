<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\NotificationTemplate\NotificationTemplateRepositoryInterface;
use App\Repositories\StoreNotification\StoreNotificationRepositoryInterface;
use App\Services\PromotionLogsService;
use App\Services\PromotionService;
use App\Http\Controllers\Controller;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;

class OrderController extends Controller
{
    protected $className = "Backend\OrderController";

    protected $order;
    protected $orderService;
    protected $drugStore;
    private $notificationTemplate;
    private $storeNotification;
    protected $invoice;
    protected $promotionService;
    protected $promotionLogsService;

    public function __construct(
        OrderService                            $orderService,
        DrugStoreRepositoryInterface            $drugStore,
        NotificationTemplateRepositoryInterface $notificationTemplate,
        StoreNotificationRepositoryInterface    $storeNotification,
        InvoiceRepositoryInterface              $invoice,
        PromotionService                        $promotionService,
        PromotionLogsService                    $promotionLogsService
    )
    {
        LogEx::constructName($this->className, '__construct');
        $this->orderService = $orderService;
        $this->drugStore = $drugStore;
        $this->notificationTemplate = $notificationTemplate;
        $this->storeNotification = $storeNotification;
        $this->invoice = $invoice;
        $this->promotionService = $promotionService;
        $this->promotionLogsService = $promotionLogsService;
    }

    public function getDetailOrder($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailOrder');

        $user = $request->userInfo;
        $check = $this->order->findOneById($id);
        if (!empty($check) && $check->drug_store_id == $user->drug_store_id) {
            $data = $this->order->getDetailById($id);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } else {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
    }

    public function getDetailOrderAdmin($id, Request $request)
    {
        LogEx::methodName($this->className, 'getDetailOrderAdmin');

        $user = $request->userInfo;
        $check = $this->order->findOneById($id);
        if (!empty($check) && $check->drug_store_id == $user->drug_store_id) {
            $data = $this->order->getDetailByIdFromAdmin($id);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } else {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
    }

    public function cancelOrder($id, Request $request)
    {
        LogEx::methodName($this->className, 'cancelOrder');

        $data = $this->order->cancelOrder($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function orderSave(Request $request)
    {
        LogEx::methodName($this->className, 'orderSave');
        $requestInput = $request->input();
        $orderId = $requestInput['id'] ?? null;
        $dataOld = $orderId ? Utils::executeRawQuery('select v3.f_order_detail(?) as result', [$orderId]) : null;
        $user = $request->userInfo;
        $drugStore = $this->drugStore->findOneById($user->drug_store_id);
        try {
            if (isset($requestInput['drug_store_id'])) {
                $params = Utils::getParams($requestInput, [], false);
            } else {
                $params = Utils::getParams($requestInput);
            }
            if ($requestInput["status"] === 'confirm'
                && $drugStore->type === "GDP"
                && isset($requestInput["promotion_ids"])
                && sizeof($requestInput["promotion_ids"]) > 0)
            {
                if ($this->promotionService->validatePromotionByInvoiceOrOrder($user->drug_store_id, $requestInput, true) === false) {
                    return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, "Chương trình khuyến mại áp dụng không chính xác");
                };
            }
            $data = Utils::executeRawQuery('select v3.f_order_save(?) as result', [$params], $request->url(), $requestInput);
            try {
                $orderData = json_decode($data[0]->result, true);
                // Change status invoice IV1
                if ($orderData['status'] === 'done' && isset($orderData['out_invoice_id'])) {
                    $invoiceData = $this->invoice->getDetailById($orderData['out_invoice_id']);
                    if (isset($invoiceData)) {
                        if ($invoiceData['invoice']->payment_status == 'paid' || $invoiceData['invoice']->payment_status == 'partial_paid') {
                            $this->invoice->updateOneById($orderData['out_invoice_id'], ['shipping_status' => 'done', 'status' => 'done']);
                        } else {
                            $this->invoice->updateOneById($orderData['out_invoice_id'], ['shipping_status' => 'done']);
                        }
                    }
                }
                // Add promotion logs
                if ($requestInput["status"] === 'confirm'
                    && $drugStore->type === "GDP"
                    && isset($requestInput["promotion_ids"])
                    && sizeof($requestInput["promotion_ids"]) > 0)
                {
                    $this->promotionLogsService->createPromotionLogByOrder($requestInput, $orderData['id'], $user->drug_store_id);
                }

                $this->sendSmsOrderGPP($orderData);
                $this->sendSmsOrderGDP($orderData, $dataOld);
            } catch (\Exception $ee) {
                LogEx::info($ee->getMessage());
            }
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, json_decode($data[0]->result));
    }

    /**
     * api v3
     * from orderSave
    */
    public function orderSaveV3(Request $request)
    {
        LogEx::methodName($this->className, 'orderSaveV3');

        $requestInput = $request->input();
        $orderId = $requestInput['id'] ?? null;
        $dataOld = $orderId ?
            $this->orderService->orderDetailV3(
                $request->input(),
                $request->userInfo->drug_store_id) :
            null;
        $user = $request->userInfo;
        $drugStore = $this->drugStore->findOneById($user->drug_store_id);

        try {
            if (isset($requestInput['drug_store_id'])) {
                $params = Utils::getParams($requestInput, [], false);
            } else {
                $params = Utils::getParams($requestInput);
            }
            if ($requestInput["status"] === 'confirm'
                && $drugStore->type === "GDP"
                && isset($requestInput["promotion_ids"])
                && sizeof($requestInput["promotion_ids"]) > 0)
            {
                if ($this->promotionService->validatePromotionByInvoiceOrOrder($user->drug_store_id, $requestInput, true) === false) {
                    return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, "Chương trình khuyến mại áp dụng không chính xác");
                };
            }
            $data = Utils::executeRawQuery('select v3.f_order_save(?) as result', [$params], $request->url(), $requestInput);
            try {
                $orderData = json_decode($data[0]->result, true);
                // Change status invoice IV1
                if ($orderData['status'] === 'done' && isset($orderData['out_invoice_id'])) {
                    $invoiceData = $this->invoice->getDetailById($orderData['out_invoice_id']);
                    if (isset($invoiceData)) {
                        if ($invoiceData['invoice']->payment_status == 'paid' || $invoiceData['invoice']->payment_status == 'partial_paid') {
                            $this->invoice->updateOneById($orderData['out_invoice_id'], ['shipping_status' => 'done', 'status' => 'done']);
                        } else {
                            $this->invoice->updateOneById($orderData['out_invoice_id'], ['shipping_status' => 'done']);
                        }
                    }
                }
                // Add promotion logs
                if ($requestInput["status"] === 'confirm'
                    && $drugStore->type === "GDP"
                    && isset($requestInput["promotion_ids"])
                    && sizeof($requestInput["promotion_ids"]) > 0)
                {
                    $this->promotionLogsService->createPromotionLogByOrder($requestInput, $orderData['id'], $user->drug_store_id);
                }

                $this->sendSmsOrderGPP($orderData);
                $this->sendSmsOrderGDP($orderData, $dataOld);
            } catch (\Exception $ee) {
                LogEx::info($ee->getMessage());
            }
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, json_decode($data[0]->result));
    }

    public function orderList(Request $request)
    {
        LogEx::methodName($this->className, 'orderList');

        try {
            $requestInput = $request->input();
            $data = Utils::executeRawQuery('select * from v3.f_order_list(?)', [Utils::getParams($requestInput)], $request->url(), $requestInput);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function orderListByDrug(Request $request)
    {
        LogEx::methodName($this->className, 'orderListByDrug');

        $requestInput = $request->input();
        try {
            $data = Utils::executeRawQuery('select * from v3.f_order_list_drug(?)', [Utils::getParams($requestInput)], $request->url(), $requestInput);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    /**
     * api v3
     * from function orderListByDrug and export
    */
    public function orderListByDrugV3(Request $request)
    {
        LogEx::methodName($this->className, 'orderListByDrug');

        $requestInput = $request->input();
        try {
            $query = $this->orderService->orderListDrugV3($request->input(), $request->userInfo->drug_store_id);
            $data = Utils::executeRawQueryV3(
                $query,
                $request->url(),
                $request->input()
            );

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function orderExportListByDrugV3(Request $request){
        LogEx::methodName($this->className, 'orderExportListByDrugV3');

        $data = $this->orderService->orderExportListByDrugV3($request);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function orderDetail(Request $request)
    {
        LogEx::methodName($this->className, 'orderDetail');
        try {

            $requestInput = $request->input();
            $data = Utils::executeRawQuery('select v3.f_order_detail(?) as result', [Utils::getParams($requestInput, [], true)], $request->url(), $requestInput);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, json_decode($data[0]->result));
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    /**
     * api v3
     * from orderDetail
    */
    public function orderDetailV3(Request $request)
    {
        LogEx::methodName($this->className, 'orderDetail');
        try {
            $query = $this->orderService->orderDetailV3(
                $request->input(),
                $request->userInfo->drug_store_id
            );

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $query);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function orderDetailV3New(Request $request)
    {
        LogEx::methodName($this->className, 'orderDetail');
        try {
            $query = $this->orderService->orderDetailV3New(
                $request->input(),
                $request->userInfo->drug_store_id
            );

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $query);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function orderReserve(Request $request)
    {
        LogEx::methodName($this->className, 'reserve');

        try {
            $requestInput = $request->input();
            $data = Utils::executeRawQuery("select * from v3.f_order_reserve(?)", [Utils::getParams($requestInput)], $request->url(), $requestInput);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    /**
     * api v3
     *from orderReserve
    */
    public function orderReserveV3(Request $request)
    {
        LogEx::methodName($this->className, 'orderReserveV3');

        try {
            $query = $this->orderService->orderReserveV3($request);

            if (gettype($query) == 'string') return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, $query);

            $data = Utils::executeRawQueryV3(
                $query,
                $request->url(),
                $request->input()
            );

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    private function sendSmsOrderGPP($orderData)
    {
        LogEx::methodName($this->className, 'sendSmsOrderGPP');
        try {
            $orderId = $orderData['id'];
            $orderCode = $orderData['order_code'];
            $orderStatus = $orderData['status'];
            $gdpStore = $this->drugStore->findOneById($orderData['gdp_id']);
            $usersData = Utils::executeRawQuery("select u.id, u.number_phone, u.settings, u.permission
from users u where u.drug_store_id = ? and active = 'yes' and (v3.f_json_exists(u.permission, 'order_manage') or u.user_role = 'admin')", [$orderData['drug_store_id']]);

            $templateGpp = null;
            $linkGpp = "/order/list?id=" . $orderId;
            switch ($orderStatus) {
                case "cancel_gdp":
                    $templateGpp = $this->notificationTemplate->getByKey('order_cancel_gdp');
                    break;
                case "confirm":
                    $templateGpp = $this->notificationTemplate->getByKey('order_confirm_gdp');
                    break;
                case "delivery":
                    $templateGpp = $this->notificationTemplate->getByKey('order_delivery_gdp');
                    break;
                default:
                    break;
            }
            if (isset($templateGpp)) {
                $dataNotiGPP = [];
                foreach ($usersData as $user) {
                    $userSettings = json_decode($user->settings);
                    $notiUser = [
                        'user_id' => $user->id,
                        'user_phone' => $user->number_phone,
                        'title' => str_replace(['{{storename}}', '{{ordercode}}'], [$gdpStore["name"], $orderCode], $templateGpp->title),
                        'content' => str_replace(['{{storename}}', '{{ordercode}}'], [$gdpStore["name"], $orderCode], $templateGpp->content),
                        'content_sms' => str_replace(['{{storename}}', '{{ordercode}}'], [$gdpStore["name"], $orderCode], $templateGpp->content_sms),
                        'url' => $linkGpp,
                        'type' => 'order',
                        'is_read' => false,
                        'is_sent' => false
                    ];
                    if (isset($userSettings) && isset($userSettings->subscription)) {
                        $pushData = array(
                            'subscription' => $userSettings->subscription,
                            'pushData' => array(
                                'title' => 'SPHACY GPP',
                                'image' => 'https://promotion.sphacy.vn/gpp.png',
                                'tag' => 'order',
                                'id' => $orderCode . '-' . $orderStatus,
                                'url' => 'https://gpp.sphacy.vn' . $linkGpp,
                                'text' => str_replace(['{{storename}}', '{{ordercode}}'], [$gdpStore["name"], $orderCode], $templateGpp->title),
                                'updated_at' => $orderData['updated_at']
                            )
                        );
                        try {
                            LogEx::info("[ORDER] send push notification to user " . $user->id);
                            shell_exec('/sphacy_gppbatch/push.sh "' . str_replace('"', '\"', json_encode($pushData)) . '"');
                        } catch (\Exception $eee) {
                            LogEx::warning("[ORDER] can not send push notification");
                        }
                    }
                    array_push($dataNotiGPP, $notiUser);
                }
                $this->storeNotification->insertBatchWithChunk($dataNotiGPP, sizeof($dataNotiGPP));
            }
        } catch (\Exception $ee) {
            LogEx::info($ee->getMessage());
        }
    }

    private function sendSmsOrderGDP($orderData, $dataOld)
    {
        LogEx::methodName($this->className, 'sendSmsOrderGDP');
        try {
            $orderDataOld = $dataOld ? json_decode($dataOld[0]->result, true) : null;
            $orderId = $orderData['id'];
            $orderCode = $orderData['order_code'];
            $orderStatus = $orderData['status'];
            $drugStore = $this->drugStore->findOneById($orderData['drug_store_id']);
            $usersDataGdp = Utils::executeRawQuery("select u.id, u.number_phone, u.settings, u.permission
from users u where u.drug_store_id = ? and active = 'yes' and (v3.f_json_exists(u.permission, 'order_manage') or u.user_role = 'admin')", [$orderData['gdp_id']]);

            $templateGdp = null;
            $linkGdp = null;
            switch ($orderStatus) {
                case "sent":
                    if (!isset($orderDataOld)) {
                        $templateGdp = $this->notificationTemplate->getByKey('order_create_gpp');
                        $linkGdp = "/order/confirm/" . $orderId;
                    }
                    break;
                case "cancel_gpp":
                    $templateGdp = $this->notificationTemplate->getByKey('order_cancel_gpp');
                    $linkGdp = "/order/manage?id=" . $orderId;
                    break;
                case "done":
                    $templateGdp = $this->notificationTemplate->getByKey('order_done_gpp');
                    $linkGdp = "/order/manage?id=" . $orderId;
                    break;
                default:
                    break;
            }
            if (isset($templateGdp)) {
                $dataNotiGDP = [];
                foreach ($usersDataGdp as $user) {
                    $userSettings = json_decode($user->settings);
                    $notiUser = [
                        'user_id' => $user->id,
                        'user_phone' => $user->number_phone,
                        'title' => str_replace(['{{storename}}', '{{ordercode}}'], [$drugStore["name"], $orderCode], $templateGdp->title),
                        'content' => str_replace(['{{storename}}', '{{ordercode}}'], [$drugStore["name"], $orderCode], $templateGdp->content),
                        'content_sms' => str_replace(['{{storename}}', '{{ordercode}}'], [$drugStore["name"], $orderCode], $templateGdp->content_sms),
                        'url' => $linkGdp,
                        'type' => 'order',
                        'is_read' => false,
                        'is_sent' => false
                    ];
                    if (isset($userSettings) && isset($userSettings->subscription)) {
                        $pushData = array(
                            'subscription' => $userSettings->subscription,
                            'pushData' => array(
                                'title' => 'SPHACY GDP',
                                'image' => 'https://promotion.sphacy.vn/gdp.png',
                                'tag' => 'order',
                                'id' => $orderCode . '-' . $orderStatus,
                                'url' => 'https://gdp.sphacy.vn' . $linkGdp,
                                'text' => str_replace(['{{storename}}', '{{ordercode}}'], [$drugStore["name"], $orderCode], $templateGdp->title),
                                'updated_at' => $orderData['updated_at']
                            )
                        );
                        try {
                            LogEx::info("[ORDER] send push notification to user " . $user->id);
                            shell_exec('/sphacy_gppbatch/push.sh "' . str_replace('"', '\"', json_encode($pushData)) . '"');
                        } catch (\Exception $eee) {
                            LogEx::warning("[ORDER] can not send push notification");
                        }
                    }
                    array_push($dataNotiGDP, $notiUser);
                }
                $this->storeNotification->insertBatchWithChunk($dataNotiGDP, sizeof($dataNotiGDP));

            }
        } catch (\Exception $ee) {
            LogEx::info($ee->getMessage());
        }
    }

    /**
     * api v3
     * orderListV3 and export
    */
    public function orderListV3(Request $request)
    {
        LogEx::methodName($this->className, 'orderListV3');

        try {
            $data = $this->orderService->orderListV3(
                $request->input(),
                $request->userInfo->drug_store_id,
                $request->userInfo->id
            );
            $data = Utils::executeRawQueryV3(
                $data,
                $request->url(),
                $request->input()
            );

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
    }

    public function orderExportV3(Request $request){
        LogEx::methodName($this->className, 'orderExportV3');

        $data = $this->orderService->exportOrderListV3($request);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

}
