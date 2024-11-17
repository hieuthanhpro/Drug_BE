@extends('layouts.admin')
@section('title')
    Tạo mới thông báo chủ động
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
                <header>Tạo mới thông báo chủ động</header>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('layouts.flash_message')
                </div>
                <form method="post" class="card-body row">
                    @csrf
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepTitle" name="title" value="{{ old('title') }}" required>
                            <label class="mdl-textfield__label" for="txtDepTitle">Tiêu đề thông báo</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label txt-full-width">
                            <input class="mdl-textfield__input" type="text" id="txtDepUrl" name="url" value="{{ old('url') }}">
                            <label class="mdl-textfield__label" for="txtDepUrl">Link thông báo</label>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="form-group row">
                            <label class="col-lg-3 col-md-4 control-label">Loại thông báo: </label>
                            <div class="col-lg-9 col-md-8">
                                <select class="form-control select2-type" name="type" style="height: 50px">
                                    <option value="" hidden>Chọn loại thông báo</option>
                                    <option value="news" {{ (old("type") == 'news' ? "selected":"") }}>Tin tức</option>
                                    <option value="promotion" {{ (old("type") == 'promotion' ? "selected":"") }}>Tin khuyến mãi</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="form-group row">
                            <label class="col-lg-3 col-md-4 control-label">Gửi tới: </label>
                            <div class="col-lg-9 col-md-8">
                                <select class="form-control select2-sent-type" name="sent_type" style="height: 50px">
                                    <option value="" hidden>Chọn gửi tới</option>
                                    <option value="all" {{ (old("sent_type") == 'all' ? "selected":"") }}>Tất cả nhà thuốc</option>
                                    <option value="gdp" {{ (old("sent_type") == 'gdp' ? "selected":"") }}>Nhà thuốc GDP</option>
                                    <option value="gpp" {{ (old("sent_type") == 'gpp' ? "selected":"") }}>Nhà thuốc GPP</option>
                                    <option value="custom" {{ (old("sent_type") == 'custom' ? "selected":"") }}>Tùy chọn</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row {{ (old("sent_type") == 'custom' ? "":"hidden") }} sent-to-select">
                            <label class="col-lg-3 col-md-4 control-label">Chọn danh sách: </label>
                            <div class="col-lg-9 col-md-8">
                                <select class="form-control select2-sent-to" name="sent_to_array[]"  multiple="multiple" style="height: 50px">
                                    @if(!empty($drug_store))
                                        @foreach($drug_store as $value)
                                            <option value="{{$value->id}}">{{$value->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="4" id="txtDepContent" name="content">{{ old('content') }}</textarea>
                            <label class="mdl-textfield__label" for="txtDepContent">Nội dung thông báo</label>
                        </div>
                    </div>
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <textarea class="mdl-textfield__input" rows="2" id="txtDepContentSMS" name="content_sms">{{ old('content_sms') }}</textarea>
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

@section('js')
    <script src="{{asset('admin')}}/assets/plugins/select2/js/select2.js"></script>
    <script>
        $(document).ready(function () {
            $('#dsAdminNoti').DataTable({});
            $('[name="sent_type"]').on('change', function(){
                var $this = $(this);
                if($this.val() === 'custom'){
                    $('.sent-to-select').removeClass('hidden');
                }else{
                    $('.sent-to-select').addClass('hidden');
                }
            });
            $(".select2-sent-type, .select2-type").select2({
                height: 'resolve'
            });
            $(".select2-sent-to").select2({
                height: 'resolve',
                width: '100%',
                placeholder: "Chọn nhà thuốc",
            });
            var selectedValue = [];
            @if(!empty(old('sent_to_array')))
            @foreach(old('sent_to_array') as $sentto)
            selectedValue.push({{$sentto}})
            @endforeach
            @endif
            $(".select2-sent-to").val(selectedValue).change();
        });
    </script>
@endsection
