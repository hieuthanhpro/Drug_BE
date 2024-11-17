@extends('layouts.admin')
@section('title')
    Trang chủ
@endsection
@section('body')
    <div class="col-md-12 col-sm-12">
        <div class="card  card-box">
            <div class="card-head">
                <header>Danh sách người dùng mới</header>
                <div class="tools">
                    <a class="fa fa-repeat btn-color box-refresh" href="javascript:;"></a>
                    <a class="t-collapse btn-color fa fa-chevron-down" href="javascript:;"></a>
                    <a class="t-close btn-color fa fa-times" href="javascript:;"></a>
                </div>
            </div>
            <div class="card-body ">
                <div class="table-wrap">
                    <div class="table-responsive">
                        <table class="table display product-overview mb-30" id="support_table">
                            <thead>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Assigned Professor</th>
                                <th>Date Of Admit</th>
                                <th>Fees</th>
                                <th>Branch</th>
                                <th>Edit</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>1</td>
                                <td>Jens Brincker</td>
                                <td>Kenny Josh</td>
                                <td>27/05/2016</td>
                                <td>
                                    <span class="label label-sm label-success">paid</span>
                                </td>
                                <td>Mechanical</td>
                                <td><a href="javascript:void(0)" class="" data-toggle="tooltip" title="Edit"><i class="fa fa-check"></i></a>
                                    <a href="javascript:void(0)" class="text-inverse" title="Delete" data-toggle="tooltip"><i class="fa fa-trash"></i></a></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Mark Hay</td>
                                <td> Mark</td>
                                <td>26/05/2017</td>
                                <td>
                                    <span class="label label-sm label-warning">unpaid </span>
                                </td>
                                <td>Science</td>
                                <td><a href="javascript:void(0)" class="" data-toggle="tooltip" title="Edit"><i class="fa fa-check"></i></a>
                                    <a href="javascript:void(0)" class="text-inverse" title="Delete" data-toggle="tooltip"><i class="fa fa-trash"></i></a></td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Anthony Davie</td>
                                <td>Cinnabar</td>
                                <td>21/05/2016</td>
                                <td>
                                    <span class="label label-sm label-success ">paid</span>
                                </td>
                                <td>Commerce</td>
                                <td><a href="javascript:void(0)" class="" data-toggle="tooltip" title="Edit"><i class="fa fa-check"></i></a>
                                    <a href="javascript:void(0)" class="text-inverse" title="Delete" data-toggle="tooltip"><i class="fa fa-trash"></i></a></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>David Perry</td>
                                <td>Felix </td>
                                <td>20/04/2016</td>
                                <td>
                                    <span class="label label-sm label-danger">unpaid</span>
                                </td>
                                <td>Mechanical</td>
                                <td><a href="javascript:void(0)" class="" data-toggle="tooltip" title="Edit"><i class="fa fa-check"></i></a>
                                    <a href="javascript:void(0)" class="text-inverse" title="Delete" data-toggle="tooltip"><i class="fa fa-trash"></i></a></td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>Anthony Davie</td>
                                <td>Beryl</td>
                                <td>24/05/2016</td>
                                <td>
                                    <span class="label label-sm label-success ">paid</span>
                                </td>
                                <td>M.B.A.</td>
                                <td><a href="javascript:void(0)" class="" data-toggle="tooltip" title="Edit"><i class="fa fa-check"></i></a>
                                    <a href="javascript:void(0)" class="text-inverse" title="Delete" data-toggle="tooltip"><i class="fa fa-trash"></i></a></td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>Alan Gilchrist</td>
                                <td>Joshep</td>
                                <td>22/05/2016</td>
                                <td>
                                    <span class="label label-sm label-warning ">unpaid</span>
                                </td>
                                <td>Science</td>
                                <td><a href="javascript:void(0)" class="" data-toggle="tooltip" title="Edit"><i class="fa fa-check"></i></a>
                                    <a href="javascript:void(0)" class="text-inverse" title="Delete" data-toggle="tooltip"><i class="fa fa-trash"></i></a></td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td>Mark Hay</td>
                                <td>Jayesh</td>
                                <td>18/06/2016</td>
                                <td>
                                    <span class="label label-sm label-success ">paid</span>
                                </td>
                                <td>Commerce</td>
                                <td><a href="javascript:void(0)" class="" data-toggle="tooltip" title="Edit"><i class="fa fa-check"></i></a>
                                    <a href="javascript:void(0)" class="text-inverse" title="Delete" data-toggle="tooltip"><i class="fa fa-trash"></i></a></td>
                            </tr>
                            <tr>
                                <td>8</td>
                                <td>Sue Woodger</td>
                                <td>Sharma</td>
                                <td>17/05/2016</td>
                                <td>
                                    <span class="label label-sm label-danger">unpaid</span>
                                </td>
                                <td>Mechanical</td>
                                <td><a href="javascript:void(0)" class="" data-toggle="tooltip" title="Edit"><i class="fa fa-check"></i></a>
                                    <a href="javascript:void(0)" class="text-inverse" title="Delete" data-toggle="tooltip"><i class="fa fa-trash"></i></a></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
