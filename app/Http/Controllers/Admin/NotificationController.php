<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\LibExtension\LogEx;
use App\Repositories\AdminNotification\AdminNotificationRepositoryInterface;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\NotificationTemplate\NotificationTemplateRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $className = "Admin\NotificationController";

    protected $notificationService;
    protected $notificationTemplate;
    protected $adminNotification;
    protected $drugStore;


    public function __construct(NotificationService                     $notificationService,
                                NotificationTemplateRepositoryInterface $notificationTemplate,
                                AdminNotificationRepositoryInterface    $adminNotification,
                                DrugStoreRepositoryInterface            $drugStore)
    {
        LogEx::constructName($this->className, '__construct');
        $this->notificationService = $notificationService;
        $this->notificationTemplate = $notificationTemplate;
        $this->adminNotification = $adminNotification;
        $this->drugStore = $drugStore;
    }

    public function getNotificationTemplate()
    {
        LogEx::methodName($this->className, 'getNotificationTemplate');
        $data = $this->notificationTemplate->findAll();
        return view('admin.notification.template', compact('data'));
    }

    public function editTemplate($key)
    {
        $data = $this->notificationTemplate->getByKey($key);
        return view('admin.notification.edit', compact('data'));
    }

    public function updateTemplate(Request $request)
    {
        try {
            $requestInput = $request->input();
            $validation = $this->notificationService->validationTemplate($requestInput);

            if ($validation->fails()) {
                return back()->withInput()->withErrors($validation);
            }

            $this->notificationService->insertOrUpdate($requestInput, 'update');
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
            return back()->with('errors', 'Cập nhật mẫu thông báo không thành công');
        }
        return back()->with('success', 'Cập nhật mẫu thông báo thành công');
    }

    public function viewCreateTemplate()
    {
        LogEx::methodName($this->className, 'viewCreateTemplate');
        return view('admin.notification.create');
    }

    public function createTemplate(Request $request)
    {
        LogEx::methodName($this->className, 'createTemplate');

        try {
            $requestInput = $request->input();
            $validation = $this->notificationService->validationTemplate($requestInput);

            if ($validation->fails()) {
                return back()->withInput($requestInput)->withErrors($validation);
            }
            $this->notificationService->insertOrUpdate($requestInput, 'create');
            return redirect()->route('admin.notification.editTemplate', ['key' => $requestInput['key']])->with('success', 'Tạo mẫu thông báo thành công');
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
        }
        return back()->with('errors', 'Tạo mẫu thông báo không thành công');
    }

    public function viewAdminNoti()
    {
        LogEx::methodName($this->className, 'viewAdminNoti');
        $data = $this->adminNotification->findAll();
        return view('admin.notificationadmin.list_noti', compact('data'));
    }

    public function viewCreateAdminNoti()
    {
        LogEx::methodName($this->className, 'viewCreateAdminNoti');
        $drug_store = $this->drugStore->findAll();
        return view('admin.notificationadmin.create', compact('drug_store'));
    }

    public function createAdminNoti(Request $request)
    {
        LogEx::methodName($this->className, 'createAdminNoti');

        try {
            $requestInput = $request->input();
            $validation = $this->notificationService->validationAdminNoti($requestInput);

            if ($validation->fails()) {
                return back()->withInput($requestInput)->withErrors($validation);
            }
            if ($requestInput['sent_type'] == 'custom') {
                $requestInput['sent_to'] = collect($requestInput['sent_to_array'])->implode(',');
            }
            $requestInput['status'] = 'waiting';

            $data = $this->adminNotification->create($requestInput);
            if (isset($data)) {
                return redirect()->route('admin.notification.listNoti')->with('success', 'Tạo thông báo chủ động thành công');
            }
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
        }
        return back()->with('errors', 'Tạo thông báo chủ động không thành công');
    }

    public function detailAdminNoti($id)
    {
        LogEx::methodName($this->className, 'detailAdminNoti');

        $data = $this->adminNotification->findOneById($id);
        if (isset($data)) {
            if ($data['sent_type'] == 'custom') {
                $listDrug = $this->drugStore->findManyByIds(explode(',', $data['sent_to']));
                $sentToList = [];
                foreach ($listDrug as $i => $d) {
                    $sentToList[] = $d['name'];
                }
                $data['sent_to_list'] = collect($sentToList)->implode(', ');
            }
            return view('admin.notificationadmin.detail_noti', compact('data'));
        }
        return redirect()->route('admin.notification.listNoti')->with('errors', 'Không tìm thấy thông báo');
    }
}
