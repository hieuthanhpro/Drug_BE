@extends('layouts.admin')
@section('title')
    Thông tin chi tiết trả hàng
@endsection
@section('css')
    <!-- data tables -->
    <link href="{{asset('admin')}}/css/return-order.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Thông tin trả hàng đơn {{ (!empty($data) && !empty($data["order"])) ? $data["order"]->order_code : "" }}</header>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                    <div class="card-body row">
                    @if(!empty($data) && !empty($data["order_detail_admin"]))
                        <div class="col-lg-9 p-t-20">
                                <div class="col-lg-12 p-t-10 p-b-10 return-order_drug_header">
                                        <span class="return-order_drug_image">Hình ảnh</span>
                                        <span class="return-order_drug_name">Tên Thuốc</span>
                                        <span class="return-order_input">Lô SX</span>
                                        <span class="return-order_input">Hạn SD</span>
                                        <span class="return-order_input-small">Số lượng</span>
                                        <span class="return-order_input">Đơn vị</span>
                                        <span class="return-order_input">Giá</span>
                                        <span class="return-order_input-small">VAT</span>
                                        <span class="return-order_input">Thành tiền</span>
                                </div>
                            @foreach($data["order_detail_admin"] as $key => $item)
                                <div class="col-lg-12 return-order_drug_container">
                                    <img class="return-order_drug_image" src="{{asset('admin')}}/images/thumbnail.png">
                                    <span class="return-order_drug_name">{{$item->drug_name}}</span>
                                    <span class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input">
                                        {{$item->number}}
                                    </span>
                                    <span class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input">
                                        {{ date('d/m/Y', strtotime($item->expiry_date))}}
                                    </span>
                                    <span class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input-small">
                                        {{$item->quantity}}
                                    </span>
                                    <span class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input">
                                        {{$item->unit_name}}
                                    </span>
                                    <span class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input">
                                        {{ number_format($item->cost)}} (VNĐ)
                                    </span>
                                    <span class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label return-order_input-small">
                                        {{$item->vat}} %
                                    </span>
                                    <span class="return-order_input return-order_final_cost">
                                        {{ number_format($item->quantity * $item->cost * (1 + $item->vat / 100)) }} (VNĐ)
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="col-lg-3 p-t-20">
                        @if(!empty($data) && !empty($data["order"]))
                        <div>
                            <span>Số lượng loại thuốc:  </span>
                            <span class="pull-right">{{ count($data["order_detail_admin"]) }}</span>
                        </div>
                        <hr/>
                        <div>
                            <span>Tổng tiền (VNĐ)</span>
                            <span class="pull-right return-order_amount">{{ number_format($data["order"]->amount) }}</span>
                        </div>
                        <hr/>
                        <div>
                            <span>VAT (VNĐ)</span>
                            <span class="pull-right return-order_vat_amount">{{ number_format($data["order"]->vat_amount) }}</span>
                        </div>
                        <hr/>
                        <div>
                            <span>Thực trả (VNĐ)</span>
                            <span class="pull-right return-order_pay_amount">{{ number_format($data["order"]->pay_amount) }}</span>
                        </div>
                        <hr/>
                        <div class="col-lg-12 p-t-20 text-center">
                            <a href="{{route('admin.order.orders_returned')}}" class="btn btn-primary">
                                Quay lại
                            </a>
                            <a href="{{route('admin.order.order_detail_for_return', $data['order']->id)}}" class="btn btn-primary">
                                Xem đơn đặt
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
