@extends('layouts.admin')
@section('title')
    Danh Sách Nhà Thuốc
@endsection
@section('css')

@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Danh Sách Người Dùng</header>
                <button id="panel-button" class="mdl-button mdl-js-button mdl-button--icon pull-right" data-upgraded=",MaterialButton">
                    <i class="material-icons">more_vert</i>
                </button>
                <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" data-mdl-for="panel-button">
                    <li class="mdl-menu__item"><a href="{{route('admin.user.create')}}"><i class="material-icons">exposure_plus_1</i>Tạo mới</a></li>
                </ul>
            </div>
            <div class="card-body ">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                </div>
                <div class="table-scrollable">
                    <table id="dsUser" class="table table-striped table-bordered hover order-column" style="width:100%;">
                        <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Tên đăng nhập</th>
                            <th>Số điện thoại</th>
                            <th>Email</th>
                            <th>Trạng thái</th>
                            <th>Quyền</th>
                            <th>Tên nhà thuốc</th>
                            <th>Thao tác</th>
                        </tr>
                        </thead>
                        <tbody>
                            @if(!empty($data))
                                @foreach($data as $item)
                                    <tr>
                                        <td>{{$item->name}}</td>
                                        <td>{{$item->username}}</td>
                                        <td>{{$item->number_phone}}</td>
                                        <td>{{$item->email}}</td>
                                        <td>{{$item->active == 'yes' ? 'Hoạt động' : 'Khóa'}}</td>
                                        <td>{{$item->role_id == '1' ? 'Admin' : 'bán thuốc'}}</td>
                                        <td>{{$item->drung_store['name']}}</td>
                                        <td>
                                            <a href="{{route('admin.user.delete',$item->id)}}" class="btn btn-danger">Xóa</a>
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
        $(document).ready(function() {
            $('#dsUser').DataTable({

            });

        } );
    </script>

@endsection
