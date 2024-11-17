<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Repositories\StoreNotification\StoreNotificationRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;

class NotificationController extends Controller
{
    protected $className = "Backend\NotificationController";

    protected $storeNotification;

    public function __construct(StoreNotificationRepositoryInterface $storeNotification)
    {
        LogEx::constructName($this->className, '__construct');

        $this->storeNotification = $storeNotification;
    }

    public function getNewestNotifications(Request $request)
    {
        LogEx::methodName($this->className, 'getNotifications');
        $user = $request->userInfo;
        $requestInput = $request->input();
        $page = $requestInput['page'] ?? 1;
        $perPage = $requestInput['per_page'] ?? 5;
        $listNoti = $this->storeNotification->getTopNewestByUserId($page, $perPage, $user->id);
        $count = $this->storeNotification->getCount($user->id);
        $data = [
            'list_noti' => $listNoti,
            'total' => $count[0]->total,
            'unread' => $count[0]->unread
        ];
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function readNotification($id){
        $this->storeNotification->readNoti($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
    }
}
