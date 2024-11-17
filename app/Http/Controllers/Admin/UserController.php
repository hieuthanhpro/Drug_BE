<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\NotificationTemplate\NotificationTemplateRepositoryInterface;
use App\Repositories\Users\UserRepositoryInterface;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\LibExtension\LogEx;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $className = "Admin\UserController";

    private $user;
    private $drugStore;
    private $smsService;
    private $notificationTemplate;

    public function __construct(UserRepositoryInterface $user, DrugStoreRepositoryInterface $drugStore, SMSService $smsService,
                                NotificationTemplateRepositoryInterface $notificationTemplate)
    {
        LogEx::constructName($this->className, '__construct');

        $this->user = $user;
        $this->drugStore = $drugStore;
        $this->smsService = $smsService;
        $this->notificationTemplate = $notificationTemplate;
    }
    public function index(){
        LogEx::methodName($this->className, 'index');

        $data = $this->user->findAll();
        return view('admin.user.index',compact('data'));
    }
    public function create(){
        LogEx::methodName($this->className, 'create');

        $drug_store = $this->drugStore->findAll();
        return view('admin.user.create',compact('drug_store'));
    }

    public function store(Request $request){
        LogEx::methodName($this->className, 'store');

        $data = $request->all();
        $validation = Validator::make($data, [
            'number_phone' => 'required|phone:VN',
        ], [
            'number_phone.required' => 'Vui lòng nhập số điện thoại',
            'number_phone.phone' => 'Vui lòng kiểm tra lại số điện thoại',
        ]);

        if($validation->fails()) {
            return back()->withInput()->withErrors($validation);
        }

        $username = $data['username'];
        $password = $data['password'];

        $check_name = $this->user->findOneBy('username',$username);
        if(!empty($check_name)){
            return back()->with('errors', 'tên đăng nhập đã tồn tại');
        }

        if ($data['password'] != $data['password_check']){
            return back()->with('errors', 'xác nhận mật khẩu sai');
        }
        if ($data['role_id'] == 1) {
            unset($data['drug_store_id']);
        }
        $data['password'] = Hash::make($data['password']);
        $data['active'] = 'yes';
        $data['drug_store_id'] = $request->input('drug_store');
        unset($data['_token']);
        unset($data['drug_store']);

        $data['full_name'] =  $data['name'];
        $create = $this->user->create($data);
        if ($create){
            // Send SMS to user
            $drugStoreOfUser = $this->drugStore->findOneById($data['drug_store_id']);
            $template = $this->notificationTemplate->getByKey('gdp_register_success');
            if(strtolower($drugStoreOfUser->type) === 'gpp'){
                $template = $this->notificationTemplate->getByKey('gpp_register_success');
            }
            $message = str_replace(['{{username}}', '{{password}}'], [$username, $password], $template->content_sms);

            $msg = $this->smsService->sendSMS($message, $data['number_phone']);
            if (gettype($msg) == "boolean" && $msg == true) {
                return redirect()->route('admin.user.index')->with('success', 'Tạo tài khoản thành công');
            } else {
                return redirect()->route('admin.user.index')->with('success', 'Tạo tài khoản thành công')->with('errors', $msg);
            }
        }
        return back()->with('errors', 'tạo tài khoản thất bại');
    }

    public function delete($id) {
        LogEx::methodName($this->className, 'delete');

        $data = $this->user->findOneById($id);
        if (!empty($data)){
            $delete = $this->user->deleteOneById($id);
            if ($delete){
                return back()->with('success', 'xóa tài khoản thành công');
            }else{
                return back()->with('errors', 'xóa tài khoản thất bại');
            }
        }
        return back()->with('errors', 'không có thông tin tài khoản');
    }
}
