@extends('layouts.admin')
@section('title')
    Danh sách quảng cáo
@endsection
@section('css')

@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Danh sách quảng cáo</header>
                <button id="panel-button" class="mdl-button mdl-js-button mdl-button--icon pull-right" data-upgraded=",MaterialButton">
                    <i class="material-icons">more_vert</i>
                </button>
                <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" data-mdl-for="panel-button">
                    <li class="mdl-menu__item"><a href="{{route('admin.linkads.viewcreate')}}"><i class="material-icons">exposure_plus_1</i>Tạo mới</a></li>
                </ul>
            </div>
            <div class="card-body ">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                </div>
                <div class="table-scrollable">
                    <table id="linkadsTable" class="display table table-bordered" style="width:100%;">
                        <thead>
                        <tr>
                            <th>Nội dung</th>
                            <th>Đường dẫn (Link)</th>
                            <th>Ngày cập nhật</th>
                            <th>Tác vụ</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(!empty($data))
                            @foreach($data as $item)
                                <tr>
                                    <td>{{$item->text}}</td>
                                    <td>
                                        <a href="{{$item->link}}" target="blank">{{$item->link}}</a>
                                    </td>
                                    <td>{{$item->updated_at}}</td>
                                    <td>
                                        <a href="{{route('admin.linkads.viewupdate',$item->id)}}" class="btn btn-danger">Sửa</a>
                                        <a href="{{route('admin.linkads.delete',$item->id)}}" class="btn btn-danger" onClick="return confirm('Bạn chắc chắn muốn xóa quảng cáo này?')">Xóa</a>
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
    <script src="{{asset('admin')}}/assets/js/pages/table/table_data.js"></script>
@endsection
