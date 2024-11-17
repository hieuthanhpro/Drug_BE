@extends('layouts.admin')
@section('title')
    Tạo mới tài khoản
@endsection
@section('css')
    <!-- data tables -->
    <link rel="stylesheet" href="{{asset('admin')}}/assets/plugins/select2/css/select2-bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="{{asset('admin')}}/assets/plugins/select2/css/select2.css" type="text/css" />
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Tạo Mới Tài Khoản</header>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('layouts.flash_message')
                </div>
                <form method="post" class="card-body row">
                    @csrf

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepName" name="name" value="{{old('name')}}" required>
                            <label class="mdl-textfield__label">Tên tài khoản</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepName" name="username" value="{{old('username')}}" required>
                            <label class="mdl-textfield__label">Tên đăng nhập</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="password" id="txtDepName" name="password" required>
                            <label class="mdl-textfield__label">Mật khẩu</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="password" id="txtDepName" name="password_check" required>
                            <label class="mdl-textfield__label">Xác thực mật khẩu</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="email" id="txtDepHead" name="email" value="{{old('email')}}">
                            <label class="mdl-textfield__label">Email</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepHead" name="number_phone" value="{{old('number_phone')}}" required>
                            <label class="mdl-textfield__label">Điện thoại</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="form-group row">
                            <label class="col-lg-3 col-md-4 control-label">Hiệu thuốc: </label>
                            <div class="col-lg-9 col-md-8">
                                <select class="form-control select-drugstore" name="drug_store" value="{{old('drug_store')}}" style="height: 50px">
                                    @if(!empty($drug_store))
                                        @foreach($drug_store as $value)
                                            <option value="{{$value->id}}">{{$value->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="form-group row">
                            <label class="col-lg-3 col-md-4 control-label">Quyền: </label>
                            <div class="col-lg-9 col-md-8">
                                <select class="form-control" name="role_id" >
                                    <option value="1">
                                        Admin
                                    </option>
                                    <option value="0">bán thuốc</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 p-t-20 text-center">
                        <button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 m-r-20 btn-pink">Tạo</button>
                        <button type="reset" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 btn-default">Nhập lại</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{asset('admin')}}/assets/plugins/select2/js/select2.js"></script>
    <script >
        $(document).ready(function() {
            $(".select-drugstore").select2({
                height: 'resolve' 
            });
        });
    </script>

@endsection