@extends('layouts.admin')
@section('title')
    Danh Sách Mẫu Thông Báo
@endsection
@section('css')

@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Danh Sách Mẫu Thông Báo</header>
            </div>
            <div class="card-body ">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                </div>
                <div class="table-scrollable">
                    <table id="dsTemplate" class="table table-striped table-bordered hover order-column"
                           style="width:100%;">
                        <thead>
                        <tr>
                            <th style="width: 20%">Mã mẫu</th>
                            <th>Tên mẫu</th>
                            <th>Thao tác</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(!empty($data))
                            @foreach($data as $item)
                                <tr>
                                    <td><a href="{{route('admin.notification.editTemplate',$item->key)}}" style="margin-top: 5px;"
                                           title="Chỉnh sửa thông tin">{{$item->key}}</a></td>
                                    <td><a href="{{route('admin.notification.editTemplate',$item->key)}}" style="margin-top: 5px;"
                                           title="Chỉnh sửa thông tin">{{$item->name}}</a></td>
                                    <td>
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
        $(document).ready(function () {
            $('#dsTemplate').DataTable({});
        });
    </script>
@endsection
