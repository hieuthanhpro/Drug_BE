@extends('layouts.admin')
@section('title')
    Thông tin đơn đặt hàng
@endsection
@section('css')
    <!-- data tables -->
    <link href="{{asset('admin')}}/css/return-order.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Thông tin đơn đặt hàng {{ (!empty($data) && !empty($data["order"])) ? $data["order"]->order_code : "" }} </header>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                    <div class="card-body row">
                    @if(!empty($data) && !empty($data["order_detail"]))
                        <div class="col-lg-9 p-t-20">
                                <div class="col-lg-12 p-t-10 p-b-10 return-order_drug_header">
                                        <span class="return-order_drug_image">Hình ảnh</span>
                                        <span class="return-order_drug_name_preview">Tên Thuốc</span>
                                        <span class="return-order_input_preview">Số lượng</span>
                                        <span class="return-order_input_preview">Đơn vị</span>
                                        <span class="return-order_input_preview">Giá</span>
                                        <span class="return-order_input_preview">Thành tiền</span>
                                </div>
                            @foreach($data["order_detail"] as $key => $item)
                                <div class="col-lg-12 return-order_drug_container">
                                    <img class="return-order_drug_image" src="{{asset('admin')}}/images/thumbnail.png">
                                    <span class="return-order_drug_name_preview">{{$item->drug_name}}</span>
                                    <span class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input_preview">
                                        {{$item->quantity}}
                                    </span>
                                    <span class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input_preview">
                                        {{$item->unit_name}}
                                    </span>
                                    <span class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input_preview">
                                        {{ number_format($item->cost) }} (VNĐ)
                                    </span>
                                    <span class="return-order_input_preview return-order_final_cost">
                                        {{ number_format($item->quantity * $item->cost) }} (VNĐ)
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="col-lg-3 p-t-20">
                        @if(!empty($data) && !empty($data["order"]))
                        <div>
                            <span>Số lượng loại thuốc:  </span>
                            <span class="pull-right">{{ count($data["order_detail"]) }}</span>
                        </div>
                        <hr/>
                        <div>
                            <span>Tổng tiền (VNĐ)</span>
                            <span class="pull-right return-order_amount">{{ number_format($data["order"]->amount) }}</span>
                        </div>
                        <hr/>
                        <div class="col-lg-12 p-t-20 text-center">
                            <a href="{{route('admin.order.orders_returned')}}" class="btn btn-primary">
                                Quay lại
                            </a>
                            <a href="{{route('admin.order.order_detail', $data['order']->id)}}" class="btn btn-primary">
                                Xem đơn trả
                            </a>
                        </div>
                        @endif
                    </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{asset('admin')}}/assets/js/order.js"></script>
@endsection
