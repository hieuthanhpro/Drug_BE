@extends('layouts.admin')
@section('title')
    Danh sách thông báo chủ động
@endsection
@section('css')

@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Danh sách thông báo chủ động</header>
                <button id="panel-button" class="mdl-button mdl-js-button mdl-button--icon pull-right" data-upgraded=",MaterialButton">
                    <i class="material-icons">more_vert</i>
                </button>
                <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" data-mdl-for="panel-button">
                    <li class="mdl-menu__item"><a href="{{route('admin.notification.createNoti')}}"><i class="material-icons">exposure_plus_1</i>Tạo mới</a></li>
                </ul>
            </div>
            <div class="card-body ">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                </div>
                <div class="table-scrollable">
                    <table id="dsAdminNoti" class="table table-striped table-bordered hover order-column"
                           style="width:100%;">
                        <thead>
                        <tr>
                            <th>Tiêu đề thông báo</th>
                            <th>Kiểu thông báo</th>
                            <th>Gửi tới</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(!empty($data))
                            @foreach($data as $item)
                                <tr>
                                    <td><a href="{{route('admin.notification.detailNoti',$item->id)}}" style="margin-top: 5px;"
                                           title="Xem chi tiết">{{$item->title}}</a></td>
                                    <td>{{$item->type === 'news' ? 'Tin tức' : 'Khuyến mãi'}}</td>
                                    @switch($item->sent_type)
                                        @case('gdp')
                                        <td>Nhà thuốc GDP</td>
                                        @break
                                        @case('gpp')
                                        <td>Nhà thuốc GPP</td>
                                        @break
                                        @case('custom')
                                        <td>Nhà thuốc theo danh sách</td>
                                        @break
                                        @default
                                        <td>Tất cả nhà thuốc</td>
                                    @endswitch
                                    <td>{{date('Y-m-d H:i',strtotime($item->created_at))}}</td>
                                    <td>{{ $item->status === 'waiting' ? 'Chờ gửi' : 'Đã gửi' }}</td>
                                    <td></td>
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
{{--
@section('js')
    <!-- data tables -->
    <script src="{{asset('admin')}}/assets/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="{{asset('admin')}}/assets/plugins/datatables/plugins/bootstrap/dataTables.bootstrap4.min.js"></script>
    <script src="{{asset('admin')}}/assets/js/pages/table/table_data.js"></script>
@endsection
--}}

@section('js')
    <!-- data tables -->
    <script>
        $(document).ready(function () {
            $('#dsAdminNoti').DataTable({"order": [[ 3, "desc" ]]});
        });
    </script>
@endsection
