<?php

namespace App\Services;

use App\LibExtension\LogEx;
use App\Repositories\NotificationTemplate\NotificationTemplateRepositoryInterface;
use App\Repositories\Users\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Class UserService
 * @package App\Services
 */
class UserService
{
    protected $className = "UserService";
    private $userRepository;
    private $smsService;
    private $notificationTemplate;

    public function __construct(UserRepositoryInterface $userRepository,
                                SMSService              $smsService, NotificationTemplateRepositoryInterface $notificationTemplate)
    {
        LogEx::constructName($this->className, '__construct');
        $this->userRepository = $userRepository;
        $this->smsService = $smsService;
        $this->notificationTemplate = $notificationTemplate;
    }

    public function createOrUpdateUser($requestInput)
    {
        DB::beginTransaction();
        try {
            $permission = array();
            if (!empty($requestInput['password'])) $requestInput['password'] = Hash::make($requestInput['password']);
            if (!empty($requestInput['is_system_admin'])) {
                if ($requestInput['is_system_admin'] === true) array_push($permission, "system");
            }
            if (!empty($requestInput['is_order_manager'])) {
                if ($requestInput['is_order_manager'] === true) array_push($permission, "order_manage");
            }
            $requestInput['permission'] = json_encode($permission);
            if (isset($requestInput['id'])) {
                $this->userRepository->updateOneById($requestInput['id'], $requestInput);
                $drugStore = $this->userRepository->findOneById($requestInput['id']);
            } else {
                $requestInput['active'] = 'yes';
                $requestInput['remember_token'] = 'yes';
                $drugStore = $this->userRepository->create($requestInput);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return null;
        }
        return $drugStore;
    }

    public function filterUser($requestInput, $drugStoreId = 0)
    {
        return $this->userRepository->filterUser($requestInput, $drugStoreId);
    }

    public function checkAvailableUsername($username, $id = null)
    {
        $count = $this->userRepository->checkAvailableUsername($username, $id);
        return $count === 0;
    }

    public function sendSMSCreateUser($userData, $passwordOrg)
    {
        LogEx::info("Send sms to user " . $userData['id']);
        try {
            $template = $this->notificationTemplate->getByKey('gdp_register_success');
            if (strtolower($userData['drug_store']['type']) === 'gpp') {
                $template = $this->notificationTemplate->getByKey('gpp_register_success');
            }
            $message = str_replace(['{{username}}', '{{password}}'], [$userData['username'], $passwordOrg], $template->content_sms);
            $this->smsService->sendSMS($message, $userData['number_phone']);
        } catch (\Exception $e) {
            LogEx::error("Can not send sms to userId=" . $userData['id'] . " and numberPhone=" . $userData['number_phone'] . ". Exception msg:" . $e->getMessage());
        }
    }
}
