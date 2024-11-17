@extends('layouts.admin')
@section('title')
    chuyển thuốc
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>chuyển thuốc</header>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('layouts.flash_message')
                </div>
                <form method="post" action="{{route('admin.drugstore.sentDrugByDrugstore')}}" class="card-body row">
                    @csrf
                    <div class="col-lg-6 p-t-20">
                        <div class="form-group row">
                            <label class="col-lg-3 col-md-4 control-label">Nhà thuốc Nguồn: </label>
                            <div class="col-lg-9 col-md-8">
                                <select data-show-subtext="true" data-live-search="true" class="form-control selectpicker" name="store_send" >
                                    @if(!empty($drugStore))
                                        @foreach($drug_store as $value)
                                            <option value="{{$value->id}}" @if ($value->id == $defaultDrugStoreID) selected @endif>{{$value->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="form-group row">
                            <label class="col-lg-3 col-md-4 control-label">Nhà thuốc nhận: </label>
                            <div class="col-lg-9 col-md-8">
                                <select data-show-subtext="true" data-live-search="true" class="form-control selectpicker" name="store_give" >
                                    @if(!empty($drugStore))
                                        @foreach($drugStore as $value)
                                            <option value="{{$value->id}}">{{$value->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 p-t-20 text-center">
                        <button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 m-r-20 btn-pink">Chuyển</button>
                        <button type="reset" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 btn-default">Nhập lại</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <!-- data tables -->
    <script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.6.3/js/bootstrap-select.min.js"></script>
@endsection
