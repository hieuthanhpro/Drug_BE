<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\User;
use App\LibExtension\LogEx;

class LoginController extends Controller
{
    protected $className = "Auth\LoginController";
    protected $redirectTo = '/home';
    use AuthenticatesUsers;

    public function __construct()
    {
        LogEx::constructName($this->className, '__construct');

        $this->middleware('guest')->except('logout');
    }

    /**
     * Đăng nhập admin
     * @param LoginRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(LoginRequest $request): \Illuminate\Http\RedirectResponse
    {
        LogEx::methodName($this->className, 'login');
        $requestInput = $request->input();

        $user = User::where('username', $request->get('username'))->first();
        if (empty($user)) {
            return back()->with('errors', 'Tài khoản không tồn tại');
        }
        if ($user->active == 'no') {
            return back()->with('errors', 'Tài khoản không có quyền truy cập');
        }

        if ($request->get('username') != $user->name) {
            return back()->with('errors', 'Tên đăng nhập không có trong hệ thống !');
        }

        if (Hash::check($requestInput['password'], $user->password)) {
            // Set Auth Details
            Auth::login($user);

            // Redirect home page
            if ($user->role_id != 1) {
                return redirect()->route('admin.drug.index');
            } else {
                return redirect()->route('admin.drugstore.index');
            }
        }
        return back()->with('errors', 'Sai mật khẩu !');
    }
}
