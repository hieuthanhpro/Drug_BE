@extends('layouts.admin')
@section('title')
    Danh sách đơn đặt hàng
@endsection
@section('css')

@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Danh sách đơn đặt hàng</header>
            </div>
            <div class="card-body ">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                </div>
                <div class="table-scrollable">
                    <table id="tableOrdering" class="display table table-bordered" style="width:100%;">
                        <thead>
                        <tr>
                            <th style="width: 5%">Số đơn hàng</th>
                            <th>Nhà Thuốc</th>
                            <th>Liên Hệ</th>
                            <th>Nhà cung cấp</th>
                            <th>Ngày tạo đơn</th>
                            <th>Ngày giao dự kiến</th>
                            <th>Tác Vụ</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(!empty($data))
                            @foreach($data as $item)
                                <tr>
                                    <td>{{$item->order_code}}</td>
                                    <td>{{$item->drugstore_name}}</td>
                                    <td>{{$item->drugstore_phone}}<br/>{{$item->drugstore_address}}</td>
                                    <td>{{$item->supplier_name}}</td>
                                    <td>{{ date('d/m/Y', strtotime($item->created_at))}}</td>
                                    <td>{{ date('d/m/Y', strtotime($item->delivery_date))}}</td>
                                    <td>
                                        <a href="{{route('admin.order.order_return', $item->id)}}" class="btn btn-primary">Gửi đơn xác nhận</a>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <!-- data tables -->
    <script src="{{asset('admin')}}/assets/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="{{asset('admin')}}/assets/plugins/datatables/plugins/bootstrap/dataTables.bootstrap4.min.js"></script>
    <script src="{{asset('admin')}}/assets/plugins/datatables/plugins/datetime-moment.js"></script>
    <script src="{{asset('admin')}}/assets/js/pages/table/table_data.js"></script>
@endsection
