<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;
use App\Models\DrugStore;
use App\Models\User;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\NotificationTemplate\NotificationTemplateRepositoryInterface;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\Users\UserRepositoryInterface;
use App\Services\ApiServiceGPP;
use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsersController extends Controller
{
    protected $className = "Backend\UsersController";

    protected $users;
    protected $role;
    protected $drugStore;
    protected $apiService;
    private $smsService;
    private $notificationTemplate;

    public function __construct(
        UserRepositoryInterface                 $users,
        RoleRepositoryInterface                 $role,
        DrugStoreRepositoryInterface            $drugStore,
        ApiServiceGPP                           $apiService,
        SMSService                              $smsService,
        NotificationTemplateRepositoryInterface $notificationTemplate
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->users = $users;
        $this->role = $role;
        $this->drugStore = $drugStore;
        $this->apiService = $apiService;
        $this->smsService = $smsService;
        $this->notificationTemplate = $notificationTemplate;
    }

    public function login(Request $request)
    {
        LogEx::methodName($this->className, 'login');

        $input = $request->all();
        $hasUser = $this->users->findOneBy('username', $input['username']);
        if (!empty($hasUser) && $hasUser->active === "yes") {
            $checkPass = Hash::check($input['password'], $hasUser->password);
            if ($checkPass == true) {
                $credentials = $request->only('username', 'password');
                try {
                    if (!$token = JWTAuth::attempt($credentials)) {
                        return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
                    }
                } catch (JWTException $e) {
                    LogEx::try_catch($this->className, $e);
                    return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
                }

                $user = JWTAuth::toUser($token);
                $userInfo = array(
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'full_name' => $user->full_name,
                    'user_role' => $user->user_role,
                    'permission' => json_decode($user->permission),
                    'settings' => json_decode($user->settings),
                    'drug_store_id' => $user->drug_store_id
                );
                $drugStoreInfo = $this->drugStore->findOneById($userInfo['drug_store_id']);
                $drugStoreInfo['password'] = '';
                $drugStoreInfo->settings = json_decode($drugStoreInfo->settings);

                // Check type current login not equal drugstore
                $type = $input['type'] ?? 'gpp';
                $drugStoreType = $drugStoreInfo->type ?? '';
                if (strtolower($drugStoreType) != strtolower($type)) {
                    LogEx::methodName($this->className, 'Login_error');
                    return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, 'Bạn không có quyền truy cập vào hệ thống');
                }

                $dataResult = array(
                    'userinfo' => $userInfo,
                    'drug_store' => $drugStoreInfo,
                    'token' => $token
                );

                $requestInput = $request->input();
                if (isset($requestInput['subscription'])) {
                    try {
                        $data = Utils::executeRawQuery("select v3.f_user_settings(?) as result", [json_encode(array('subscription' => $requestInput['subscription']))]);
                    } catch (\Exception $e) {
                        LogEx::info($e->getMessage());
                    }
                }
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $dataResult);
            }
        }
        return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, 'Tài khoản không hợp lệ vui lòng liên hệ hotline: 0931.439.456 hoặc 0917.777.711 để được hỗ trợ.');
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate($request->header('token'));
        } catch (JWTException $exception) {
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
    }

    public function getUserInfo(Request $request)
    {
        LogEx::methodName($this->className, 'getUserInfo');

        $user = JWTAuth::toUser($request->token);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $user);
    }

    public function changePass(Request $request)
    {
        LogEx::methodName($this->className, 'changePass');

        $input = $request->all();
        //check các trường nhập vào
        $validator = Validator::make($request->all(), [
            'password_old' => 'required|max:100|min:2',
            'password' => 'required|max:100|min:2||confirmed',
        ]);
        if ($validator->fails()) {
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY, $validator->errors());
        }
        $user = JWTAuth::toUser($request->token);
        //check thông tin user
        if (empty($user)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }
        //check trạng thái user
        if ($user['active'] != 'yes') {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, 'Tài khoản người dùng bị vô hiệu hóa');
        }
        //check mật khẩu cũ
        $check = Hash::check($input['password_old'], $user['password']);
        if ($check == false) {
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
        }
        $userId = $user['id'];

        //mã hóa pass mới
        $passwordNew = Hash::make($input['password']);
        //cập nhật pass mới vào db
        DB::beginTransaction();
        try {
            $this->users->updateOneById($userId, ['password' => $passwordNew]);
            DB::commit();
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function create(Request $request)
    {
        LogEx::methodName($this->className, 'create');

        $input = $request->all();
        $user = JWTAuth::toUser($request->token);
        $username = $input['username'];

        $checkUser = $this->users->findOneBy('username', $username);
        if (!empty($checkUser)) {
            return \App\Helper::errorResponse(CommonConstant::CONFLICT, CommonConstant::MSG_ALREADY_EXISTS);
        }
        if ($user['role_id'] != CommonConstant::ADMIN_ROLE) {
            return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, 'Bạn không có quyền tạo tài khoản');
        }
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:100|min:2|unique:users',
            'role_id' => 'required|numeric',
            'full_name' => 'required|max:50|min:2',
            'email' => 'nullable|email|max:50',
            'number_phone' => 'required|max:100|min:10',
            'active' => 'required',
            'drug_store_id' => 'required|numeric',
            'avatar' => 'max:125|min:2',
            'password' => 'required|max:100|min:2',
        ]);
        if ($validator->fails()) {
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        unset($input['user_id']);
        unset($input['userInfo']);
        $input['password'] = Hash::make($input['password']);
        DB::beginTransaction();
        try {
            $this->users->create($input);
            DB::commit();
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $input);
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function getByDrugStore(Request $request)
    {
        LogEx::methodName($this->className, 'getByDrugStore');

        $user = JWTAuth::toUser($request->token);
        $data = $this->users->findManyBy('drug_store_id', $user->drug_store_id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function setWarningDate(Request $request)
    {
        LogEx::methodName($this->className, 'setWarningDate');

        $user = JWTAuth::toUser($request->token);
        $warningDate = $request->input('warning_date');
        $data = $this->drugStore->updateOneById($user->drug_store_id, ['warning_date' => $warningDate]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getListUser(Request $request)
    {
        LogEx::methodName($this->className, 'getListUser');

        $user = JWTAuth::toUser($request->token);
        $data = DB::table('users')
            ->select(
                'users.*',
                'role.name as role_name'
            )
            ->join('role', 'role.id', 'users.role_id')
            ->where('users.drug_store_id', $user->drug_store_id)
            ->paginate(10);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function update($id, Request $request)
    {
        LogEx::methodName($this->className, 'update');

        $input = $request->input();
        $dataUpdate = array(
            'name' => $input['name'] ?? '',
            'username' => $input['username'],
            'full_name' => $input['full_name'],
            'user_role' => $input['user_role'],
            'email' => $input['email'],
            'number_phone' => $input['number_phone']
        );
        if (isset($input['password']) && isset($input['password_confirm'])) {
            if ($input['password'] !== $input['password_confirm']) {
                $resp = $this->responseApi(CommonConstant::FORBIDDEN, "Mật khẩu và nhập lại mật khẩu không trùng khớp", null);
                return response()->json($resp);
            }
            $data_update['password'] = Hash::make($input['password']);
        }
        if (isset($input['permission'])) {
            $dataUpdate['permission'] = $input['permission'];
        }
        $update = $this->users->updateOneById($id, $dataUpdate);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $update);
    }

    public function createByStore(Request $request)
    {
        LogEx::methodName($this->className, 'createByStore');

        $input = $request->input();
        $user = $request->userInfo;
        $data_create = array(
            'drug_store_id' => $user->drug_store_id,
            'name' => isset($input['name']) ? $input['name'] : '',
            'username' => $input['username'],
            'full_name' => $input['full_name'],
            'user_role' => $input['user_role'],
            'email' => $input['email'],
            'number_phone' => $input['number_phone']
        );
        if (isset($input['password']) && isset($input['password_confirm'])) {
            if ($input['password'] !== $input['password_confirm']) {
                $resp = $this->responseApi(CommonConstant::FORBIDDEN, "Mật khẩu và nhập lại mật khẩu không trùng khớp", null);
                return response()->json($resp);
            }
            $data_create['password'] = Hash::make($input['password']);
        }
        if (isset($input['permission'])) {
            $data_create['permission'] = $input['permission'];
        }
        $isExist = $this->users->findManyBy("username", $input['username']);
        if (isset($isExist) && sizeof($isExist) > 0) {
            $resp = $this->responseApi(CommonConstant::FORBIDDEN, "Tên đăng nhập đã được sử dụng", null);
            return response()->json($resp);
        }
        $update = $this->users->create($data_create);
        $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $update);
        return response()->json($resp);
    }

    public function deleteByStore($id)
    {
        LogEx::methodName($this->className, 'deleteByStore');
        $update = $this->users->deleteOneById($id);
        $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, true);
        return response()->json($resp);
    }

    public function delete($id)
    {
        LogEx::methodName($this->className, 'delete');

        $delete = $this->users->deleteOneById($id);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $delete);
    }

    public function resetPassword($id)
    {
        LogEx::methodName($this->className, 'resetPassword');

        $pass = '123456aA@';
        $pass = Hash::make($pass);
        $update = $this->users->updateOneById($id, ['password' => $pass]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $update);
    }

    public function getEmployeeStore(Request $request)
    {
        LogEx::methodName($this->className, 'getEmployeeStore');

        $user = $request->userInfo;
        $fields = ['id', 'name', 'username', 'full_name'];
        $employees = User::where('drug_store_id', $user->drug_store_id)
            ->select($fields)
            ->where('role_id', '!=', 1)
            ->get();
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $employees);
    }

    public function getNotifications(Request $request)
    {
        LogEx::methodName($this->className, 'getNotifications');

        $user = $request->userInfo;
        $notifications = $user->unreadNotifications;
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $notifications);
    }

    public function markAsReadNotification($id, Request $request)
    {
        LogEx::methodName($this->className, 'markAsReadNotification');

        $user = $request->userInfo;
        $isMarked = false;
        foreach ($user->unreadNotifications as $notification) {
            if ($notification->id == $id) {
                $notification->markAsRead();
                $isMarked = true;
            }
        }

        if ($isMarked) {
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
        }
        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
    }

    public function refresh(Request $request)
    {
        LogEx::methodName($this->className, 'refresh');
        $user = JWTAuth::toUser($request->token);
        $requestInput = $request->input();
        if (isset($user)) {
            $token = JWTAuth::refresh($request->token);
            $user = $this->users->findOneById($user->id);
            $drugStore = $this->drugStore->findOneById($user->drug_store_id);
            $drugStore->settings = json_decode($drugStore->settings);
            $user['token'] = $token;
            $result = array(
                'userinfo' => array(
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'full_name' => $user->full_name,
                    'user_role' => $user->user_role,
                    'permission' => json_decode($user->permission),
                    'settings' => json_decode($user->settings),
                    'drug_store_id' => $user->drug_store_id
                ),
                'drug_store' => $drugStore,
                'token' => $token
            );
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $result);
        }
        if (isset($requestInput['subscription'])) {
            try {
                Utils::executeRawQuery("select v3.f_user_settings(?) as result", [json_encode(array('subscription', $requestInput['subscription']))]);
            } catch (\Exception $e) {
                LogEx::info($e->getMessage());
            }
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function setUserSetting(Request $request)
    {
        LogEx::methodName($this->className, 'setUserSetting');

        $data = DB::select('select v3.f_user_setting(?) as result', [Utils::getParams($request->input())]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from setUserSetting
    */
    public function setUserSettingV3(Request $request)
    {
        LogEx::methodName($this->className, 'setUserSetting');

        $drugStoreId = $request->userInfo->drug_store_id;
        $requestInput = $request->input();

        $invoice_print_header = Utils::coalesce($requestInput, 'invoice_print_header', null);
        $invoice_print_footer = Utils::coalesce($requestInput, 'invoice_print_footer', null);
        $setting = ($invoice_print_footer || $invoice_print_header) ?
                    json_encode([
                        "invoice_print_footer" => $invoice_print_footer,
                        "invoice_print_header" => $invoice_print_header]) :
                    null;

        $dataSubmid = [
            "warning_date" => Utils::coalesce($requestInput, 'warning_days', null),
            "bank_id" => Utils::coalesce($requestInput, 'bank_id', null),
            "bank_branch" => Utils::coalesce($requestInput, 'bank_branch', null),
            "bank_account_name" => Utils::coalesce($requestInput, 'bank_account_name', null),
            "bank_account_number" => Utils::coalesce($requestInput, 'bank_account_number', null),
            "vnpay_code" => Utils::coalesce($requestInput, 'vnpay_code', null),
            "settings" => $setting
        ];

        $data = DB::table('drugstores')
            ->where('id', $drugStoreId)
            ->update(array_filter($dataSubmid));

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function getList(Request $request)
    {
        LogEx::methodName($this->className, 'getList');

        $requestInput = $request->input();
        $getList = $request->input('user_role') ?? null;
        $user = JWTAuth::toUser($request->token);
        if ($getList) {
            $data = $this->filterUser($requestInput, $user->drug_store_id,  $request->input('user_role'));
//                Utils::executeRawQuery('select id, drug_store_id, user_type, name, full_name, username, number_phone, email, avatar,
//                active, user_role, permission, created_at, updated_at, deleted_at
//                from users where drug_store_id = ? and user_role = ?',
//                [$user->drug_store_id, $request->input('user_role')],
//                $request->url(),
//                $request->input());
        } else {
            $data = $this->filterUser($requestInput, $user->drug_store_id);
//                Utils::executeRawQuery('select id, drug_store_id, user_type, name, full_name, username, number_phone, email, avatar,
//                active, user_role, permission, created_at, updated_at, deleted_at
//                from users where drug_store_id = ?',
//                [$user->drug_store_id],
//                $request->url(),
//                $request->input());
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
    /**
     * api v3
     * filter users
    */
    public function filterUser ($requestInput, $drugStoreId, $userRole = null)
    {
        $page = $requestInput['page'] ?? 1;
        $per_page = $requestInput['per_page'] ?? 10;
        $offset = ($page - 1) * $per_page;

        $select = "select  
                    u.id, u.drug_store_id, u.user_type, u.name, u.full_name, u.username, u.number_phone, u.email, 
                    u.avatar, u.active, u.user_role, u.permission, u.created_at, u.updated_at, u.deleted_at
                    from users u
                    inner join drugstores s
                    on  s.id = u.drug_store_id";
        $selectCount = "SELECT count(u.*) as total FROM users u inner join drugstores s on s.id = u.drug_store_id";
        $where = " where u.drug_store_id = $drugStoreId";
        if(is_string($userRole)){
            $where = $where . " AND u.user_role = '$userRole'";
        }
        if (!empty($requestInput['query'])) {
            $keySearch = trim($requestInput['query']);
            $where = $where . " AND (u.name ~* '" . $keySearch
                . "' or u.full_name  ~* '" . $keySearch
                . "' or u.username  ~* '" . $keySearch
                . "' or u.number_phone  ~* '" . $keySearch
                . "' or u.email  ~* '" . $keySearch
                . "' or s.name  ~* '" . $keySearch
                ."')";
        }

        $orderLimit = " order by u.id ASC limit " . $per_page . " offset " . $offset;
        $data = DB::select($select . $where . $orderLimit);
        $dataCount = DB::select($selectCount . $where);

        return new LengthAwarePaginator($data, $dataCount[0]->total, $per_page, $page);
    }

    /**
     * api v3
     * exportList
     */
    public function exportList(Request $request)
    {
        LogEx::methodName($this->className, 'exportList');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = [];

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = Utils::executeRawQuery(
                        'select id, drug_store_id, user_type, name, full_name, username, number_phone, email, avatar, 
                        active, user_role, permission, created_at, updated_at, deleted_at 
                        from users where drug_store_id = ?',
                        [$user->drug_store_id],
                        $request->url(),
                        $request->input(),
                        1,
                        3500
                    );
                    break;
                case "current_page":
                    $data = Utils::executeRawQuery(
                        'select id, drug_store_id, user_type, name, full_name, username, number_phone, email, avatar, 
                        active, user_role, permission, created_at, updated_at, deleted_at 
                        from users where drug_store_id = ?',
                        [$user->drug_store_id],
                        $request->url(),
                        $request->input(),
                        1
                    );
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = Utils::executeRawQuery(
                        'select id, drug_store_id, user_type, name, full_name, username, number_phone, email, avatar, 
                        active, user_role, permission, created_at, updated_at, deleted_at 
                        from users where drug_store_id = ?',
                        [$user->drug_store_id],
                        $request->url(),
                        $request->input(),
                        1,
                        3500
                    );
                    break;
            }
        }

        return $data;
    }

    public function settings(Request $request)
    {
        LogEx::methodName($this->className, 'settings');
        $requestInput = $request->input();
        try {
            $data = Utils::executeRawQuery("select v3.f_user_settings(?) as result", [Utils::getParams($requestInput)]);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function forgotPassword(Request $request)
    {
        LogEx::methodName($this->className, 'forgotPassword');
        $requestInput = $request->input();
        $type = $requestInput['type'];
        $username = $requestInput['username'] ?? null;
        $phoneNumber = $requestInput['phone_number'];
        try {
            if (isset($username)) {
                $user = $this->users->findOneByCredentials(['username' => $username, 'number_phone' => $phoneNumber]);
                if (!isset($user)) {
                    return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
                }
            } else {
                $users = $this->users->findManyBy('number_phone', $phoneNumber);
                if (sizeof($users) > 1) {
                    return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, 'Có nhiều hơn 1 tài khoản liên kết với số điện thoại', 'multiple');
                } else if (sizeof($users) === 0) {
                    return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
                } else {
                    $user = $users[0];
                }
            }
            $dateNow = Carbon::now();
            $countOtp = $user['count_otp'] ?? 0;
            $firstSentOtp = $user['first_sent_otp'] ?? $dateNow->toDateTimeString();
            //check trạng thái user
            if ($user['active'] != 'yes') {
                return \App\Helper::errorResponse(CommonConstant::FORBIDDEN, 'Tài khoản người dùng bị vô hiệu hóa');
            }
            switch ($type) {
                case 'verified_phone':
                case 'resend_otp':
                    //check OTP
                    if ($countOtp == 3 && $dateNow->diffInMinutes($user['first_sent_otp']) <= 10) {
                        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, 'Bạn chỉ có thể gửi tối đa 3 sms trong vòng 10 phút');
                    } else if ($countOtp == 3 && $dateNow->diffInMinutes($user['first_sent_otp']) >= 10) {
                        $countOtp = 0;
                        $firstSentOtp = $dateNow->toDateTimeString();
                    } else if ($countOtp > 0 && $dateNow->diffInMinutes($user['last_sent_otp']) < 1) {
                        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, 'Bạn chỉ có thể gửi mã xác minh sau 1 phút');
                    }
                    DB::beginTransaction();
                    if (env('APP_ENV') === 'prod') {
                        $otp = Utils::quickRandom(6, 'number');
                    } else {
                        $otp = "123456";
                    }
                    $this->users->updateOneById($user['id'], ['otp' => $otp,
                        'count_otp' => $countOtp + 1,
                        'first_sent_otp' => $firstSentOtp,
                        'last_sent_otp' => $dateNow->toDateTimeString()]);
                    DB::commit();
                    $template = $this->notificationTemplate->getByKey('forgot_password');
                    $message = str_replace('{{otp}}', $otp, $template->content_sms);
                    $this->smsService->sendSMS($message, $phoneNumber);
                    return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, "Mã xác minh đã được gửi tới số điện thoại của bạn", 1);

                case 'verified_otp':
                    if (!isset($user['otp']) || $user['otp'] != $requestInput['otp'] || $dateNow->diffInMinutes($user['last_sent_otp']) > 5) {
                        return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, 'Mã xác minh của bạn không chính xác hoặc đã hết hạn');
                    }
                    return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);

                case 'new_password':
                    if (!isset($user['otp']) || $user['otp'] != $requestInput['otp'] || $dateNow->diffInMinutes($user['last_sent_otp']) > 5) {
                        return \App\Helper::successResponse(CommonConstant::FORBIDDEN, 'Mã xác minh của bạn không chính xác hoặc đã hết hạn');
                    }
                    //check các trường nhập vào
                    $validator = Validator::make($request->all(), [
                        'password' => 'required|max:100|min:2',
                        'password_confirmation' => 'required|max:100|min:2|same:password',
                    ]);
                    if ($validator->fails()) {
                        return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY, $validator->errors());
                    }
                    $this->users->updateOneById($user['id'], ['password' => Hash::make($requestInput['password']), 'otp' => null, 'count_otp' => 0, 'first_sent_otp' => null, 'last_sent_otp' => null]);
                    return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, "Cập nhật mật khẩu cho tài khoản thành công", 1);
            }
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function getDrugStoreInfo(Request $request)
    {
        $user = $request->user;
        $data = $this->drugStore->findOneById($user->drug_store_id);
        $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        return response()->json($resp);
    }
}
