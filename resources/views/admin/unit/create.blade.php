@extends('layouts.admin')
@section('title')
    Thêm đơn vị
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Thêm đơn vị</header>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('layouts.flash_message')
                </div>
                <form method="post" action="{{route('admin.unit.store')}}" class="card-body row">
                    @csrf

                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepName" name="name" required>
                            <label class="mdl-textfield__label">Tên Đơn Vị</label>
                        </div>
                    </div>

                    <div class="col-lg-12 p-t-20 text-center">
                        <button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 m-r-20 btn-pink">Tạo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
