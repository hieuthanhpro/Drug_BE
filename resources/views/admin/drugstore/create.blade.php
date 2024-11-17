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

                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepName" name="name" required>
                            <label class="mdl-textfield__label">Tên nhà thuốc</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepHead" name="pharmacist">
                            <label class="mdl-textfield__label">Dược sĩ phụ trách</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepHead" name="phone" required>
                            <label class="mdl-textfield__label">Điện thoại</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input type="text" class="mdl-textfield__input" name="address" id="address">
                            <label class="mdl-textfield__label" for="address">Địa chỉ</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input type="text" class="mdl-textfield__input" name="username" id="username">
                            <label class="mdl-textfield__label" for="username">Tài khoản kết nối</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input type="text" class="mdl-textfield__input" id="password" name="password">
                            <label class="mdl-textfield__label" for="password">Mật khẩu</label>
                        </div>
                    </div>

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input type="text" class="mdl-textfield__input" name="base_code" id="base_code">
                            <label class="mdl-textfield__label" for="text7">Mã nhà thuốc</label>
                        </div>
                    </div>

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input type="text" class="mdl-textfield__input" name="reg_number" id="reg_number">
                            <label class="mdl-textfield__label" for="text7">Số đăng ký</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input type="text" class="mdl-textfield__input" name="business_license" id="business_license">
                            <label class="mdl-textfield__label" for="business_license">Giấy phép kinh doanh</label>
                        </div>
                    </div>

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Ngày bắt đầu sử dụng</label>
                            <input type="date" class="mdl-textfield__input" name="start_time" placeholder="Ngày bắt đầu sử dụng">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Ngày hết hạn</label>
                            <input type="date" class="mdl-textfield__input" name="end_time" placeholder="Ngày hết hạn">
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
