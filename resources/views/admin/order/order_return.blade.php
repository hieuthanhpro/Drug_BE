@extends('layouts.admin')
@section('title')
    Trả hàng
@endsection
@section('css')
    <!-- data tables -->
    <link href="{{asset('admin')}}/css/return-order.css" rel="stylesheet" type="text/css" />
    <link href="{{asset('admin')}}/assets/plugins/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Trả hàng</header>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                    <form method="post" action="{{route('admin.order.order_return_update')}}" onsubmit="return onSubmitForm.call(this)" class="card-body row">
                    <input class="hidden" type="text" name="id" value="{{$data['order']->id}}">
                    <input class="hidden" type="text" name="order_code" value="{{$data['order']->order_code}}">
                    <input class="hidden" type="text" name="drug_store_id" value="{{$data['order']->drug_store_id}}">
                    @csrf
                    @if(!empty($data) && !empty($data["order_detail"]))
                        <div class="col-lg-9 p-t-20">
                                <div class="col-lg-12 p-t-10 p-b-10 return-order_drug_header">
                                        <span class="return-order_drug_image">Hình ảnh</span>
                                        <span class="return-order_drug_name">Tên Thuốc</span>
                                        <span class="return-order_input">Lô SX</span>
                                        <span class="return-order_input">Hạn SD</span>
                                        <span class="return-order_input-small">Đơn vị</span>
                                        <span class="return-order_input-small">Số lượng</span>
                                        <span class="return-order_input-small">Giá</span>
                                        <span class="return-order_input-small">VAT</span>
                                        <span class="return-order_input">Thành tiền</span>
                                </div>
                            @foreach($data["order_detail"] as $key => $item)
                                <div class="col-lg-12 return-order_drug_container">
                                    <img class="return-order_drug_image" src="{{asset('admin')}}/images/thumbnail.png">
                                    <span class="return-order_drug_name">{{$item->drug_name}}</span>
                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input">
                                        <input class="mdl-textfield__input" type="text" name="number[]" required>
                                        <label class="mdl-textfield__label">Lô SX</label>
                                    </div>
                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input">
                                        <input class="mdl-textfield__input" type="date" name="expiry_date[]" min="{{ date('Y-m-d', strtotime(date('Y-m-d') . ' + 1 days')) }}" required>
                                    </div>
                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input-small">
                                        <label class="mdl-textfield__label">Đơn Vị</label>
                                        <select class="mdl-textfield__input" value="" name="unit[]">
                                                @if(!empty($units[$key]))
                                                    @foreach($units[$key]['units'] as $unit)
                                                        @if($item->unit_id == $unit['id'])
                                                            <option class="mdl-menu__item" value="{{$unit['id']}}" selected>{{$unit['name']}}</option>
                                                        @else
                                                            <option class="mdl-menu__item" value="{{$unit['id']}}">{{$unit['name']}}</option>
                                                        @endif
                                                    @endforeach
                                                @endif
                                        </select>
                                    </div>
                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input-small">
                                        <input class="mdl-textfield__input" type="number" pattern="-?[0-9]*(\.[0-9]+)?" name="quantity[]" value="{{$item->quantity}}" min="1" required>
                                        <label class="mdl-textfield__label">Số Lượng</label>
                                    </div>
                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input-small">
                                        <input class="mdl-textfield__input input-format-number" name="cost[]" required>
                                        <label class="mdl-textfield__label">Giá</label>
                                    </div>
                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input-small">
                                        <input class="mdl-textfield__input" type="text" pattern="-?[0-9]*(\.[0-9]+)?" name="vat[]">
                                        <label class="mdl-textfield__label">VAT</label>
                                    </div>
                                    <span class="return-order_input return-order_final_cost"></span>
                                    <a class="return-order_delete glyphicon glyphicon-trash" onclick="handleClickDelete.call(this)"></a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="col-lg-3 p-t-20">
                        <div>
                            <span>Số lượng loại thuốc:  </span>
                            <span class="pull-right" id="quantity">{{ count($data["order_detail"]) }}</span>
                        </div>
                        <hr/>
                        <div>
                            <span>Tổng tiền (VNĐ)</span>
                            <span class="pull-right return-order_pay_amount">0</span>
                            <input class="hidden" type="text" name="amount" value="">
                            <input class="hidden" type="text" name="vat_amount" value="">
                            <input class="hidden" type="text" name="pay_amount" value="">
                        </div>
                        <hr/>
                        <div class="form-group">
                            <label class="control-label">Thời gian giao hàng</label>
                            <div class="input-group date form_datetime">
                                <input class="form-control" type="text" name="time" readonly>
                                <span class="input-group-addon"><span class="glyphicon glyphicon-remove"></span></span>
                                <span class="input-group-addon"><span class="glyphicon glyphicon-th"></span></span>
                            </div>
                        </div>
                        <hr/>
                        <div class="col-lg-12 p-t-20 text-center">
                            <button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect m-b-10 m-r-20 btn-pink">Gửi đơn xác nhận</button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{asset('admin')}}/assets/js/cleave.min.js"></script>
    <script src="{{asset('admin')}}/assets/plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js"></script>
    <script src="{{asset('admin')}}/assets/js/order.js"></script>
@endsection
