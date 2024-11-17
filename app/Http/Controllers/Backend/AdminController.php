<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DrugMasterDataRequest;
use App\Http\Requests\DrugStoreRequest;
use App\Http\Requests\UserRequest;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Repositories\Bank\BankRepositoryInterface;
use App\LibExtension\CommonConstant;
use App\Services\SMSService;
use App\LibExtension\RoleUtils;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\Unit\UnitRepositoryInterface;
use App\Repositories\Users\UserRepositoryInterface;
use App\Services\DrugMasterDataService;
use App\Services\DrugStoreService;
use App\Services\UnitService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AdminController extends Controller
{
    protected $className = 'Backend\AdminController';
    private $drugStore;
    private $drugStoreService;
    private $bankRepository;
    private $user;
    private $userService;
    private $unit;
    private $unitService;
    private $drugMasterDataService;

    public function __construct(DrugStoreRepositoryInterface $drugStore, DrugStoreService $drugStoreService,
                                UserRepositoryInterface      $user, UserService $userService,
                                UnitRepositoryInterface      $unit, UnitService $unitService,
                                DrugMasterDataService        $drugMasterDataService, BankRepositoryInterface $bankRepository)
    {
        LogEx::constructName($this->className, '__construct');
        $this->bankRepository = $bankRepository;
        $this->drugStore = $drugStore;
        $this->drugStoreService = $drugStoreService;
        $this->user = $user;
        $this->userService = $userService;
        $this->unit = $unit;
        $this->unitService = $unitService;
        $this->drugMasterDataService = $drugMasterDataService;
    }

    /**
     * Lọc danh sách nhà thuốc toàn bộ hệ thống
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterDrugStore(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'filterDrugStore');
        $requestInput = $request->input();
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $data = $this->drugStoreService->filterDrugStore($requestInput);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * Xóa một nhà thuốc
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDrugStore(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'drugStoreDelete');
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $requestInput = $request->input();
        $drugStore = $this->drugStore->findOneById($requestInput['id']);
        if (!isset($drugStore)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $data = $this->drugStoreService->deleteDrugStore($requestInput['id']);
        if ($data === false) {
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    /**
     * Khóa một nhà thuốc
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lockDrugStore(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'drugStoreDelete');
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $requestInput = $request->input();
        $drugStore = $this->drugStore->findOneById($requestInput['id']);
        if (!isset($drugStore)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $this->drugStore->updateOneById($drugStore->id, ['status' => !($drugStore->status === true || $drugStore->status === 1)]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    /**
     * Tạo mới hoặc cập nhật nhà thuốc
     * @param DrugStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrUpdateDrugStore(DrugStoreRequest $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'drugStoreSave');
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $requestInput = $request->input();
        if (isset($requestInput['id'])) {
            $drugStore = $this->drugStore->findOneById($requestInput['id']);
            if (!isset($drugStore)) {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
            }
        }
        $data = $this->drugStoreService->createOrUpdate($requestInput);
        if (isset($data)) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /**
     * Nhân bản sản phẩm từ nhà thuốc A sang nhà thuốc B
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function copyDrugFromDrugStore(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'copyDrugFromDrugStore');
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $requestInput = $request->input();
        $data = $this->drugStoreService->drugStoreCopyDrug($requestInput);
        if ($data === false) {
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    /**
     * Kiểm tra số lượng sản phẩm, hóa đơn, tồn kho của nhà thuốc
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDrugStore(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'drugStoreCheck');
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $requestInput = $request->input();
        LogEx::info($requestInput['id']);
        $drugStore = $this->drugStore->findOneById($requestInput['id']);
        if (!isset($drugStore)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        $data = $this->drugStoreService->checkDrugStore($drugStore);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * Lấy danh sách nhà thuốc theo kiểu: GDP, GPP và có cho chọn
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDrugStoreListBySource(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'getDrugStoreListBySource');
        $requestInput = $request->input();
        $source = isset($requestInput['source']) ? strtoupper($requestInput['source']) : null;
        $data = $this->drugStoreService->getDrugStoreBySource($source);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * @deprecated
     */
    public function announcementList(Request $request)
    {
        LogEx::methodName($this->className, 'announcementList');
        $requestInput = $request->input();
        try {
            $data = Utils::executeRawQuery("select * from v3.f_admin_announcement_list(?)", [Utils::getParams($requestInput)], $request->url(), $requestInput);
            $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            $resp = $this->responseApi(CommonConstant::INTERNAL_SERVER_ERROR, Utils::getExceptionMessage($e), null);
        }
        return response()->json($resp);
    }

    /**
     * @deprecated
     */
    public function announcementSave(Request $request)
    {
        LogEx::methodName($this->className, 'announcementSave');
        $requestInput = $request->input();
        try {
            $data = Utils::executeRawQuery("select v3.f_admin_announcement_save(?) as result", [Utils::getParams($requestInput)]);
            $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, json_decode($data[0]->result));
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            $resp = $this->responseApi(CommonConstant::INTERNAL_SERVER_ERROR, Utils::getExceptionMessage($e), null);
        }
        return response()->json($resp);
    }

    /**
     * Lọc danh sách người dùng toàn bộ hệ thống
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterUser(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'filterUser');
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $requestInput = $request->input();
        $data = $this->userService->filterUser($requestInput);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * Tạo mới hoặc cập nhật một người dùng
     * @param UserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrUpdateUser(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'userSave');
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $requestInput = $request->input();
        if (!isset($requestInput['id']) && !isset($requestInput['password'])) {
            $errors = array(
                'password' => ['Mật khẩu không được bỏ trống']
            );
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY, $errors);
        }

        //Check nếu update user thì user đó có tồn tại trên hệ thống hay ko?
        if (isset($requestInput['id'])) {
            $user = $this->user->findOneById($requestInput['id']);
            if (!isset($user)) {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
            }
        }

        //Check tên đăng nhập có avaiable hay ko?
        if (!empty($requestInput['username'])) {
            $checkAvaiableUsername = $this->userService->checkAvailableUsername($requestInput['username'], $requestInput['id'] ?? null);
            if ($checkAvaiableUsername === false) {
                $errors = array(
                    'username' => ['Tên đăng nhập đã tồn tại']
                );
                return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY, $errors);
            }
        }
        $requestInput['drug_store_id'] = $request->userInfo->drug_store_id;
        $data = $this->userService->createOrUpdateUser($requestInput);
        if (isset($data)) {
            if (!isset($requestInput['id'])) {
                $this->userService->sendSMSCreateUser($data, $requestInput['password']);
            }
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /**
     * Tạo mới hoặc cập nhật đơn vị sản phẩm
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrUpdateUnit(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'unitSave');
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $requestInput = $request->input();

        //Check nếu update unit thì unit đó có tồn tại trên hệ thống hay ko?
        if (isset($requestInput['id'])) {
            $user = $this->unit->findOneById($requestInput['id']);
            if (!isset($user)) {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
            }
        }

        $data = $this->unitService->createOrUpdateUnit($requestInput);
        if (isset($data)) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /**
     * Tạo mới hoặc cập nhật thuốc Dược quốc gia
     * @param DrugMasterDataRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrUpdateDrugDQG(Request $request): \Illuminate\Http\JsonResponse
    {
        LogEx::methodName($this->className, 'drugDQGSave');
        if (!RoleUtils::checkRole($request->user, Route::currentRouteAction())) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        $requestInput = $request->input();
        $img_base64 = $requestInput['image'] ?? '';
        if (!empty($img_base64) && preg_match('/^data:image/', $img_base64)) {
            $img = Utils::generateImageFromBase64($img_base64);
            $requestInput['image'] = url("upload/images/" . $img);
        }
        $data = $this->drugMasterDataService->createOrUpdateDrug($requestInput);
        if (isset($data)) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }
    public function getBanks(Request $request)
    {
        $data = $this->bankRepository->findAll();
        $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        return response()->json($resp);
    }
}
