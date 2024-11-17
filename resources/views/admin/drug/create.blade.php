@extends('layouts.admin')
@section('title')
    Tạo mới nhà thuốc
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Tạo Mới Nhà Thuốc</header>
                <button id="panel-button" class="mdl-button mdl-js-button mdl-button--icon pull-right" data-upgraded=",MaterialButton">
                    <i class="material-icons">more_vert</i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('layouts.flash_message')
                </div>
                <form method="post" class="card-body row">
                    @csrf

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepName" name="name">
                            <label class="mdl-textfield__label">Tên nhà thuốc</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepHead" name="phone">
                            <label class="mdl-textfield__label">Điện thoại</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="4" id="text7" name="address"></textarea>
                            <label class="mdl-textfield__label" for="text7">Địa chỉ</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="4" id="text7" name="username"></textarea>
                            <label class="mdl-textfield__label" for="text7">Tài khoản kết nối</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="4" id="text7" name="password"></textarea>
                            <label class="mdl-textfield__label" for="text7">Mật khẩu</label>
                        </div>
                    </div>

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="4" id="text7" name="base_code"></textarea>
                            <label class="mdl-textfield__label" for="text7">Mã nhà thuốc</label>
                        </div>
                    </div>

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="4" id="text7" name="reg_number"></textarea>
                            <label class="mdl-textfield__label" for="text7">Số đăng ký</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="4" id="text7" name="business_license"></textarea>
                            <label class="mdl-textfield__label" for="text7">Giấy phép kinh doanh</label>
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
