<?php

/**
 * Created by PhpStorm.
 * User: hieu
 * Date: 12/18/2018
 * Time: 10:22 PM
 */


namespace App\Services;

use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Repositories\AdminNotification\AdminNotificationRepositoryInterface;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\NotificationTemplate\NotificationTemplateRepositoryInterface;
use App\Repositories\StoreNotification\StoreNotificationRepositoryInterface;
use App\Repositories\Users\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


/**
 * Class NotificationService
 * @package App\Services
 */
class NotificationService
{
    protected $className = "NotificationService";

    private $notificationTemplate;
    private $adminNotification;
    private $storeNotification;
    private $drug_store;
    private $user;

    public function __construct(NotificationTemplateRepositoryInterface $notificationTemplate,
                                AdminNotificationRepositoryInterface    $adminNotification,
                                StoreNotificationRepositoryInterface    $storeNotification,
                                DrugStoreRepositoryInterface            $drug_store,
                                UserRepositoryInterface                 $user)
    {
        LogEx::constructName($this->className, '__construct');

        $this->notificationTemplate = $notificationTemplate;
        $this->adminNotification = $adminNotification;
        $this->storeNotification = $storeNotification;
        $this->drug_store = $drug_store;
        $this->user = $user;
    }

    public function insertOrUpdate($model, $type)
    {
        $notificationTemplate = $this->notificationTemplate->getByKey($model['key']);

        if ($notificationTemplate) {
            if ($type === 'update') {
                $this->notificationTemplate->updateOneBy('key', $model['key'], $model);
            } else {
                return back()->with('errors', 'Mẫu thông báo đã tồn tại trên hệ thống');
            }
        } else {
            $this->notificationTemplate->create($model);
        }
        return $this->notificationTemplate->getByKey($model['key']);
    }

    public function pushNotification($listNoti)
    {
        LogEx::methodName($this->className, 'pushNotification');
        if ($listNoti) {
            foreach ($listNoti as $noti) {
                $listUser = [];
                switch ($noti->sent_type) {
                    case 'gdp':
                    case 'gpp':
                        $listStore = $this->drug_store->getDrugStoresByStatusAndType(1, $noti->sent_type);
                        if (isset($listStore)) {
                            $listUser = $this->user->getUsersByActiveAndStoreIds('yes', Utils::getIds($listStore));
                        }
                        break;
                    case 'custom':
                        $listStore = $this->drug_store->getDrugStoresByStatusAndIds(1, explode(',', $noti->sent_to));
                        if (isset($listStore)) {
                            $listUser = $this->user->getUsersByActiveAndStoreIds('yes', Utils::getIds($listStore));
                        }
                        break;
                    default:
                        $listStore = $this->drug_store->findManyBy('status', 1);
                        if (isset($listStore)) {
                            $listUser = $this->user->getUsersByActiveAndStoreIds('yes', Utils::getIds($listStore));
                        }
                        break;
                }
                if(isset($listUser)){
                    $data = [];
                    foreach ($listUser as $user){
                        $notiUser = [
                            'user_id' => $user->id,
                            'user_phone' => $user->number_phone,
                            'title' => $noti->title,
                            'content' => $noti->content,
                            'content_sms' => $noti->content_sms,
                            'url' => $noti->url,
                            'type' => $noti->type
                        ];
                        if(isset($noti->content)){
                            $notiUser['is_read'] = false;
                        }
                        if(isset($noti->content_sms)){
                            $notiUser['is_sent'] = false;
                        }

                        array_push($data, $notiUser);
                    }
                    $this->storeNotification->insertBatchWithChunk($data, sizeof($data));
                }
                $this->adminNotification->updateOneById($noti->id, ['status' => 'done']);
            }
        }
    }

    public function validationTemplate($requestInput)
    {
        return Validator::make($requestInput, [
            'key' => 'required|regex:/[a-zA-Z0-9]{2,50}+/',
            'name' => 'required|min:10|max:255',
            'title' => 'max:255',
            'content' => 'max:1000',
            'content_sms' => 'max:500',
        ], [
                'key.required' => 'Vui lòng nhập mã mẫu',
                'key.regex' => 'Mã mẫu chỉ bao gồm các ký tự a-z, A-Z, 0-9, tối thiểu 2 kí tự và tối đa 50 kí tự',
                'name.required' => 'Vui lòng nhập tiêu đề',
                'name.min' => 'Tên mẫu tối thiểu 10 kí tự và tối đa 255 kí tự',
                'name.max' => 'Tên mẫu tối thiểu 10 kí tự và tối đa 255 kí tự',
                'title.max' => 'Tiêu đề thông báo tối đa 255 kí tự',
                'content.max' => 'Nội dung thông báo tối đa 1000 kí tự',
                'content_sms.max' => 'Nội dung thông báo sms tối đa 500 kí tự',
            ]
        );
    }

    public function validationNoti($requestInput)
    {
        $validation = Validator::make($requestInput, [
            'title' => 'required|min:10|max:255',
            'content' => 'max:1000',
            'content_sms' => 'max:500',
            'url' => 'max:500',
            'type' => 'required',
            'sent_type' => 'required'
        ], [
                'title.required' => 'Vui lòng nhập tên thông báo',
                'title.min' => 'Tên thông báo tối thiểu 10 kí tự và tối đa 255 kí tự',
                'title.max' => 'Tên thông báo tối thiểu 10 kí tự và tối đa 255 kí tự',
                'content.max' => 'Nội dung thông báo tối đa 1000 kí tự',
                'content_sms.max' => 'Nội dung thông báo sms tối đa 500 kí tự',
                'url.max' => 'Link thông báo tối đa 500 kí tự',
                'type.required' => 'Vui lòng chọn loại thông báo',
                'sent_type.required' => 'Vui lòng chọn gửi tới',
            ]
        );
        $validation->after(function ($validator) use ($requestInput) {
            if ($requestInput['sent_type'] == 'custom' && (!isset($requestInput['sent_to_array']) || sizeof($requestInput['sent_to_array']) == 0)) {
                $validator->errors()->add('sent_to_array', 'Bạn phải chọn ít nhất một nhà thuốc');
            }
            if (!isset($requestInput['content']) && !isset($requestInput['content_sms'])) {
                $validator->errors()->add('content', 'Bạn phải nhập nội dung thông báo hoặc nội dung SMS');
            }
        });
        return $validation;
    }
}
