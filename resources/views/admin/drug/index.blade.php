@extends('layouts.admin')
@section('title')
    Danh Sách Thuốc
@endsection
@section('css')

@endsection
@section('body')
    <div class="col-sm-12">
        <div class="card-box">
            <div class="card-head">
                <header>Danh Sách Thuốc</header>
                <button id="panel-button" class="mdl-button mdl-js-button mdl-button--icon pull-right" data-upgraded=",MaterialButton">
                    <i class="material-icons">more_vert</i>
                </button>
                <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" data-mdl-for="panel-button">
                    <li class="mdl-menu__item"><a href="{{route('admin.drugstore.create')}}"><i class="material-icons">exposure_plus_1</i>Tạo mới</a></li>
                </ul>
            </div>
            <div class="card-body ">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                </div>
                <div class="table-scrollable">
                    <table id="tableChildRow" class="display table table-bordered" style="width:100%;">
                        <thead>
                        <tr>
                            <th style="width: 5%">Ảnh thuốc</th>
                            <th>Tên thuốc</th>
                            <th>Mã thuốc</th>
                            <th>Công ty</th>
                            <th>Mã đăng ký</th>
                            <th>Tác Vụ</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div id="myModal" class="modal fade" role="dialog" style="opacity: unset">
        <div class="modal-dialog">
            <form id="img_upload" method="post" action="{{route('admin.drug.upload')}}" enctype="multipart/form-data">
                <!-- Modal content-->
                {{csrf_field()}}
                <input type="hidden" name="drug_id" value="" id="drug_id">
                <div class="modal-content">
                    <div class="modal-header" style="text-align: center">
                        <h4 class="modal-title" >Upload Ảnh Thuốc</h4>
                    </div>
                    <div class="modal-body">
                        <input type="file" name="image" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info">Upload</button>

                        <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('js')
    <!-- data tables -->
    <script>
        const urlTable = '{{route('admin.drug.get_list')}}';
    </script>
    <script src="{{asset('admin')}}/assets/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="{{asset('admin')}}/assets/plugins/datatables/plugins/bootstrap/dataTables.bootstrap4.min.js"></script>
    <script src="{{asset('admin')}}/js/drug/config.js"></script>

@endsection
