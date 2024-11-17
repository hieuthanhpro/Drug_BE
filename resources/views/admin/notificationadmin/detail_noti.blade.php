@extends('layouts.admin')
@section('title')
    Chi tiết thông báo
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Chi tiết thông báo</header>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('layouts.flash_message')
                </div>
                <form method="post" class="card-body row">
                    @csrf
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Tiêu đề thông báo</label>
                            <input class="mdl-textfield__input" type="text" disabled readonly value="{{ $data->title }}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Link thông báo</label>
                            <input class="mdl-textfield__input" type="text" disabled readonly value="{{ $data->url }}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Loại thông báo</label>
                            <input class="mdl-textfield__input" type="text" disabled readonly value="{{ $data->type === 'news' ? 'Tin tức' : 'Khuyến mãi' }}">
                        </div>
                    </div>
                    <div class="col-lg-6 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Gửi tới nhà thuốc</label>
                            @switch($data->sent_type)
                                @case('gdp')
                                <input class="mdl-textfield__input" type="text" disabled readonly value="Nhà thuốc GDP">
                                @break
                                @case('gpp')
                                <input class="mdl-textfield__input" type="text" disabled readonly value="Nhà thuốc GPP">
                                @break
                                @case('custom')
                                <input class="mdl-textfield__input" type="text" disabled readonly value="{{ $data->sent_to_list }}">
                                @break
                                @default
                                <input class="mdl-textfield__input" type="text" disabled readonly value="Tất cả nhà thuốc">
                            @endswitch
                        </div>
                    </div>
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Nội dung thông báo</label>
                            <textarea class="mdl-textfield__input" rows="4" disabled readonly style="resize: none;">{{ $data->content }}</textarea>
                        </div>
                    </div>
                    <div class="col-lg-12 p-t-20">
                        <div class="mdl-textfield mdl-js-textfield txt-full-width">
                            <label>Nội dung SMS</label>
                            <textarea class="mdl-textfield__input" rows="4" disabled readonly style="resize: none;">{{ $data->content_sms }}</textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
