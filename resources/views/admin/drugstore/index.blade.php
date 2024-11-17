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
                <header>Danh Sách Nhà Thuốc</header>
                <button id="panel-button" class="mdl-button mdl-js-button mdl-button--icon pull-right" data-upgraded=",MaterialButton">
                    <i class="material-icons">more_vert</i>
                </button>
                <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" data-mdl-for="panel-button">
                    <li class="mdl-menu__item">
                        <a href="{{route('admin.drugstore.create')}}">
                            <i class="material-icons">exposure_plus_1</i>
                            Tạo mới
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body ">
                <div class="row">
                    <div class="col-md-12">
                        @include('layouts.flash_message')
                    </div>
                </div>


                <div class="row" style="padding-top: 10px;">
                    <table  >
                        <tbody>
                            <tr>
                                <td><b>Ngày bắt đầu:</b></td>
                                <td><input type="text" id="fromStartDate" name="fromStartDate" class="inputSearch" placeholder="YYYY-MM-DD"></td>
                                <td>-</td>
                                <td colspan="2"><input type="text" id="toStartDate" name="toStartDate" class="inputSearch" placeholder="YYYY-MM-DD"></td>
                            </tr>

                            <tr>
                                <td><b>Ngày hết hạn:</b></td>
                                <td><input type="text" id="fromEndDate" name="fromEndDate" class="inputSearch" placeholder="YYYY-MM-DD"></td>
                                <td>-</td>
                                <td><input type="text" id="toEndDate" name="toEndDate" class="inputSearch" placeholder="YYYY-MM-DD"></td>
                                <td>
                                    <button type="button" class="btn btn-primary" id="searchBtn">Search</button>
                                </td>
                            </tr>


                        </tbody>
                    </table>
                </div>
                <div class="table-scrollable">
                    <table id="drugStoreTab" class="table table-striped table-bordered hover order-column" style="width:100%" class="minWHeader">
                        <thead>
                        <tr>
                            <th>Tên nhà thuốc</th>
                            {{--  <th>Tài khoản</th>
                            <th>Mật khẩu</th>  --}}
                            <th>Dược sĩ</th>
                            <th>Số đăng ký<br>Mã nhà thuốc</th>
                            <th>Giấy phép<br>kinh doanh</th>

                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Ngày bắt đầu</th>
                            <th>Ngày hết hạn</th>
                            <th>Trạng thái</th>
                            {{--  <th>Tác vụ</th>  --}}
                        </tr>
                        </thead>
                        <tbody>
                            @if(!empty($data))
                                @foreach($data as $item)
                                    <tr>
                                        <td>
                                            <a href="{{route('admin.drugstore.edit',$item->id)}}"  style="margin-top: 5px;" title="Chỉnh sửa thông tin">
                                                {{$item->name}}
                                            </a>
                                        </td>
                                        {{--  <td>{{$item->username}}</td>
                                        <td>{{$item->password}}</td>  --}}
                                        <td>{{$item->pharmacist}}</td>
                                        <td>{{$item->reg_number}}<br>{{$item->base_code}}</td>
                                        <td>{{$item->business_license}}</td>

                                        <td><?php
                                            echo str_replace('-','<br>',$item->phone);
                                            ?></td>
                                        <td>{{$item->address}}</td>
                                        <td>{{ !empty($item->start_time) ? date('Y-m-d',strtotime($item->start_time)) : '' }}</td>
                                        <td>{{ !empty($item->end_time) ? date('Y-m-d',strtotime($item->end_time)) : '' }}</td>
                                        <td>
                                            <div>
                                                @if($item->status == 1)
                                                    <span class="label label-primary label-mini">Hoạt động</span>
                                                @else
                                                    <span class="label label-danger label-mini">Đã khóa</span>
                                                @endif
                                            </div>

                                            <div class="btn-group" role="group" aria-label="">


                                                @if($item->status == 1)
                                                    <a href="{{route('admin.drugstore.lock',$item->id)}}" class="btn btn-tumblr waves-effect waves-light btn-sm" style="margin-top: 5px" title="Khóa"><i class="fa fa-lock"></i></a>
                                                @else
                                                    <a href="{{route('admin.drugstore.unlock',$item->id)}}" class="btn btn-tumblr waves-effect waves-light btn-sm" style="margin-top: 5px" title="Mở khóa"><i class="fa fa-unlock"></i></a>
                                                @endif
                                                {{--<a href="{{route('admin.drugstore.editpw',$item->id)}}" class="btn btn-warning btn-sm" style="margin-top: 5px;  margin-left: 3px;" title="Đổi mật khẩu"><i class="fa fa-cog"></i></a>--}}
                                                <a href="{{route('admin.drugstore.delete',$item->id)}}" class="btn btn-danger btn-sm" style="margin-top: 5px;  margin-left: 3px;" title="Xóa" onclick="return confirm('Bạn chắc chắn muốn xóa nhà thuốc {{$item->name}} ?')" ><i class="fa fa-trash-o "></i></a>
                                            </div>
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
    <script>
        /* Custom filtering function */
        function isEmptyVal(val) {
            return val == '';
        }

        function filterStartDate(startDate) {
            var min = $('#fromStartDate').val();
            var max = $('#toStartDate').val();

            if ( ( isEmptyVal( min ) && isEmptyVal( max ) ) ||
                ( isEmptyVal( min ) && startDate <= max ) ||
                ( min <= startDate  && isEmptyVal( max ) ) ||
                ( min <= startDate  && startDate <= max ) )
            {
                return true;
            }
            return false;
        }

        function filterEndDate(endDate) {
            var min = $('#fromEndDate').val();
            var max = $('#toEndDate').val();

            if ( ( isEmptyVal(min) && isEmptyVal(max)  ) ||
                ( isEmptyVal(min) && endDate <= max ) ||
                ( min <= endDate  && isEmptyVal( max ) ) ||
                ( min <= endDate  && endDate <= max ) )
            {
                return true;
            }
            return false;
        }

        $.fn.dataTable.ext.search.push(

            function( settings, searchData, index, rowData, counter ) {
                var retValue = true;
                retValue = filterStartDate(rowData[6]) && filterEndDate(rowData[7]);

                return retValue;
            }
        );



        $(document).ready(function() {
            var drugStoreTab = $('#drugStoreTab').DataTable({

            });

            $("#searchBtn").click(function() {
                drugStoreTab.draw();
            });

        } );
    </script>

@endsection
