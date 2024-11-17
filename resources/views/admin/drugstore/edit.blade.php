@extends('layouts.admin')
@section('title')
    Cập nhật nhà thuốc
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Cập Nhật Nhà Thuốc</header>
                <button id="panel-button" class="mdl-button mdl-js-button mdl-button--icon pull-right" data-upgraded=",MaterialButton">
                    <i class="material-icons">more_vert</i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('layouts.flash_message')
                </div>
                <form method="post" class="card-body row" style="margin: 20px" >
                    @csrf
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepName" name="name" placeholder="Tên nhà thuốc"  value="{{$data->name ? $data->name : ''}}" required>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepHead" name="pharmacist" placeholder="Dược sĩ phụ trách"  value="{{$data->pharmacist ? $data->pharmacist : ''}}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepHead" name="phone" placeholder="Điện thoại"  value="{{$data->phone ? $data->phone : ''}}" required>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <input type="text" class="mdl-textfield__input" name="username" placeholder="Tài khoản kết nối" value="{{$data->username ? $data->username : ''}}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <input type="text" class="mdl-textfield__input" name="password" placeholder="Mật khẩu" value="{{$data->password ? $data->password : ''}}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                             <input type="text" class="mdl-textfield__input" name="address" placeholder="Địa chỉ" value="{{$data->address ? $data->address : ''}}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                             <input type="text" class="mdl-textfield__input" name="base_code" placeholder="Mã nhà thuốc" value="{{$data->base_code ? $data->base_code : ''}}">
                        </div>
                    </div>

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                             <input type="text" class="mdl-textfield__input" name="reg_number" placeholder="Số đăng ký" value="{{$data->reg_number ? $data->reg_number : ''}}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <input type="text" class="mdl-textfield__input" name="business_license" placeholder="Giấy phép kinh doanh" value="{{$data->business_license ? $data->business_license : ''}}">
                        </div>
                    </div>

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Ngày bắt đầu sử dụng</label>
                             <input type="date" class="mdl-textfield__input" name="start_time" placeholder="Ngày bắt đầu sử dụng" value="{{$data->start_time ? date('Y-m-d',strtotime($data->start_time)) : ''}}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Ngày hết hạn</label>
                            <input type="date" class="mdl-textfield__input" name="end_time" placeholder="Ngày hết hạn" value="{{$data->end_time ? date('Y-m-d',strtotime($data->end_time)) : ''}}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="send-sms">
                            <input type="checkbox" name="is-send-sms" class="mdl-checkbox__input" id="send-sms"/>
                            <span class="mdl-checkbox__label">Gửi tin nhắn thông báo cho nhà thuốc</span>
                        </label>
                    </div>
                    <div class="col-lg-12 p-t-20 text-center">
                        <button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 m-r-20 btn-pink">Lưu</button>
                        <button type="reset" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 btn-default">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
