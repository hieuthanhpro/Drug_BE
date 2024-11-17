@extends('layouts.admin')
@section('title')
    xóa nhà thuốc
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>xóa nhà thuốc</header>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('layouts.flash_message')
                </div>
                <form method="post" action="{{route('admin.drugstore.delete')}}" class="card-body row">
                    @csrf
                    <div class="col-lg-6 p-t-20">
                        <div class="form-group row">
                            <label class="col-lg-3 col-md-4 control-label">Chọn nhà thuốc: </label>
                            <div class="col-lg-9 col-md-8">
                                <select class="form-control selectpicker" name="drug_store">
                                    @if(!empty($drugStore))
                                        @foreach($drugStore as $value)
                                            <option value="{{$value->id}}" >{{$value->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 p-t-20 text-center">
                        <button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 m-r-20 btn-pink">Xóa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
