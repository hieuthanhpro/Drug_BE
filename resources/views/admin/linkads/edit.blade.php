@extends('layouts.admin')
@section('title')
    Danh sách quảng cáo
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Sửa quảng cáo</header>
            </div>
            <div class="card-body ">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                </div>
                <form method="post" class="card-body row" action="{{route('admin.linkads.update', $data->id)}}">
                    @csrf
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepName" name="text" placeholder="Nội dung"  value="{{$data->text ? $data->text : ''}}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepHead" name="link" placeholder="Đường dẫn"  value="{{$data->link ? $data->link : ''}}">
                        </div>
                    </div>

                    <div class="col-lg-12 p-t-20 text-center">
                        <button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 m-r-20 btn-pink">Lưu</button>
                        <a href="{{route('admin.linkads.index')}}" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 btn-default">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection