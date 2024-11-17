<!DOCTYPE html>
<html lang="en">

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>
    <title>Đăng Nhập Thành Viên</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" href="{{asset('auth')}}/images/icons/favicon.ico"/>
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('auth')}}/vendor/bootstrap/css/bootstrap.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('auth')}}/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('auth')}}/vendor/animate/animate.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('auth')}}/vendor/css-hamburgers/hamburgers.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('auth')}}/vendor/select2/select2.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="{{asset('auth')}}/css/util.css">
    <link rel="stylesheet" type="text/css" href="{{asset('auth')}}/css/main.css">
    <!--===============================================================================================-->
</head>
<body>

<div class="limiter">
    <div class="container-login100">
        <div class="wrap-login100">
            <div class="login100-pic js-tilt" data-tilt>
                <img src="{{asset('auth')}}/images/img-01.png" alt="IMG">
            </div>
            <form method="POST" action="{{ route('login') }}" aria-label="{{ __('Login') }}" class="login100-form validate-form">
                @csrf
                <span class="login100-form-title">
                    Đăng Nhập Thành Viên
                </span>
                @include('layouts.flash_message')
                <div class="wrap-input100 validate-input" data-validate = "Tên đăng nhập không đươc để trống">
                    <input class="input100" type="text" name="username" placeholder="tên đăng nhập" value="{{ old('username') }}">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                </div>
                <div class="wrap-input100 validate-input" data-validate = "Mật khẩu không được để trống">
                    <input class="input100" type="password" name="password" placeholder="mật khẩu">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100"><i class="fa fa-lock" aria-hidden="true"></i></span>
                </div>
                <div class="container-login100-form-btn">
                    <button class="login100-form-btn">
                        Đăng Nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!--===============================================================================================-->
<script src="{{asset('auth')}}/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
<script src="{{asset('auth')}}/vendor/bootstrap/js/popper.js"></script>
<script src="{{asset('auth')}}/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
<script src="{{asset('auth')}}/vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
<script src="{{asset('auth')}}/vendor/tilt/tilt.jquery.min.js"></script>
<script >
    $('.js-tilt').tilt({
        scale: 1.1
    })
</script>
<script src="{{asset('auth')}}/js/main.js"></script>
</body>
</html>
