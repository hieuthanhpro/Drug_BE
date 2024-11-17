@extends('layouts.admin')
@section('title')
    Tạo mới mẫu thông báo
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Tạo mới mẫu thông báo</header>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('layouts.flash_message')
                </div>
                <form method="post" class="card-body row">
                    @csrf
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepKey" name="key" required>
                            <label class="mdl-textfield__label" for="txtDepKey">Mã mẫu</label>
                        </div>
                    </div>
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepName" name="name" required>
                            <label class="mdl-textfield__label" for="txtDepName">Tên mẫu</label>
                        </div>
                    </div>
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepTitle" name="title">
                            <label class="mdl-textfield__label" for="txtDepTitle">Tiêu đề thông báo</label>
                        </div>
                    </div>
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="6" id="txtDepContent" name="content"></textarea>
                            <label class="mdl-textfield__label" for="txtDepContent">Nội dung thông báo</label>
                        </div>
                    </div>
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="2" id="txtDepContentSMS" name="content_sms"></textarea>
                            <label class="mdl-textfield__label" for="txtDepContentSMS">Nội dung SMS</label>
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
