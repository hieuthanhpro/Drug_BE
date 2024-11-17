@if(Auth::user())
<!DOCTYPE html>
<html lang="en">
<!-- BEGIN HEAD -->


<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>@yield('title')</title>
    <!-- google font -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" rel="stylesheet" type="text/css" />
    <!-- icons -->
    <link href="{{asset('admin')}}/fonts/simple-line-icons/simple-line-icons.min.css" rel="stylesheet" type="text/css" />
    <link href="{{asset('admin')}}/fonts/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <link href="{{asset('admin')}}/fonts/material-design-icons/material-icon.css" rel="stylesheet" type="text/css" />
    <!--bootstrap -->
    <link href="{{asset('admin')}}/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- data tables -->
    <link href="{{asset('admin')}}/assets/plugins/datatables/plugins/bootstrap/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/css/bootstrap-select.css" />
    <!-- Material Design Lite CSS -->
    <link rel="stylesheet" href="{{asset('admin')}}/assets/plugins/material/material.min.css">
    <link rel="stylesheet" href="{{asset('admin')}}/assets/css/material_style.css">
    <!-- Theme Styles -->
    <link href="{{asset('admin')}}/assets/css/theme/light/theme_style.css" rel="stylesheet" id="rt_style_components" type="text/css" />
    <link href="{{asset('admin')}}/assets/css/theme/light/style.css" rel="stylesheet" type="text/css" />
    <link href="{{asset('admin')}}/assets/css/responsive.css" rel="stylesheet" type="text/css" />
    <link href="{{asset('admin')}}/assets/css/theme/light/theme-color.css" rel="stylesheet" type="text/css" />
    <!-- <link href="{{asset('admin')}}/assets/css/style.css" rel="stylesheet" type="text/css" /> -->
    <!-- <link href="{{asset('admin')}}/assets/css/theme/dark/style.css" rel="stylesheet" type="text/css" /> -->

    <!-- favicon -->
    <link rel="shortcut icon" href="{{asset('admin')}}/assets/img/favicon.ico"/>
    @yield('css')
</head>
<!-- END HEAD -->

<body class="page-header-fixed sidemenu-closed-hidelogo page-content-white page-md header-white white-sidebar-color logo-indigo">
<div class="page-wrapper">
    <!-- start header -->
    <div class="page-header navbar navbar-fixed-top">
        <div class="page-header-inner ">
            <!-- logo start -->
            <div class="page-logo">
                <a href="index-2.html">
                    <span class="logo-icon material-icons fa-rotate-45">school</span>
                    <span class="logo-default">Smart</span> </a>
            </div>
            <!-- logo end -->
            <ul class="nav navbar-nav navbar-left in">
                <li><a href="#" class="menu-toggler sidebar-toggler"><i class="icon-menu"></i></a></li>
            </ul>
            <form class="search-form-opened" action="#" method="GET">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search{{asset('admin')}}." name="query">
                    <span class="input-group-btn">
							<a href="javascript:;" class="btn submit">
								<i class="icon-magnifier"></i>
							</a>
						</span>
                </div>
            </form>
            <!-- start mobile menu -->
            <a href="javascript:;" class="menu-toggler responsive-toggler" data-toggle="collapse" data-target=".navbar-collapse">
                <span></span>
            </a>
            <!-- end mobile menu -->
            <!-- start header menu -->
            <div class="top-menu">
                <ul class="nav navbar-nav pull-right">
                    <li><a href="javascript:;" class="fullscreen-btn"><i class="fa fa-arrows-alt"></i></a></li>
                    <!-- start manage user dropdown -->
                    <li class="dropdown dropdown-user">
                        <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                            <img alt="" class="img-circle " src="{{asset('admin/images')}}/user.jpg" />
                            <span class="username username-hide-on-mobile"> {{Auth::user()->name}} </span>
                            <i class="fa fa-angle-down"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-default">
                            <li>
                                <a href="user_profile.html">
                                    <i class="icon-user"></i> Trang cá nhân </a>
                            </li>
                            <li>
                                <a  href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    <i class="icon-logout"></i> Đăng xuất
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- end header -->
    <!-- start page container -->
    <div class="page-container">
        <!-- start sidebar menu -->
        @include('layouts.include.sidebar')
        <!-- end sidebar menu -->
        <!-- start page content -->
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar">
                    <div class="page-title-breadcrumb">
                        <div class=" pull-left">
                            <div class="page-title">Dashboard</div>
                        </div>
                        <ol class="breadcrumb page-breadcrumb pull-right">
                            <li><i class="fa fa-home"></i>&nbsp;<a class="parent-item" href="index-2.html">Home</a>&nbsp;<i class="fa fa-angle-right"></i>
                            </li>
                            <li class="active">Dashboard</li>
                        </ol>
                    </div>
                </div>
                <div class="state-overview">
                    <div class="row">
                        @yield('body')
                    </div>
                </div>

            </div>
        </div>

        <!-- end page content -->

    </div>
    <!-- end page container -->
    <!-- start footer -->
    <div class="page-footer">
        <div class="page-footer-inner"> 2017 &copy; Smart University Theme By
            <a href="mailto:redstartheme@gmail.com" target="_top" class="makerCss">Redstar Theme</a>
        </div>
        <div class="scroll-to-top">
            <i class="icon-arrow-up"></i>
        </div>
    </div>
    <!-- end footer -->
</div>
<!-- start js include path -->
<script src="{{asset('admin')}}/assets/plugins/jquery/jquery.min.js"></script>
<script src="{{asset('admin')}}/assets/plugins/popper/popper.js"></script>
<script src="{{asset('admin')}}/assets/plugins/jquery-blockui/jquery.blockui.min.js"></script>
<script src="{{asset('admin')}}/assets/plugins/jquery-slimscroll/jquery.slimscroll.js"></script>
<!-- bootstrap -->
<script src="{{asset('admin')}}/assets/plugins/bootstrap/js/bootstrap.min.js"></script>
<!-- dataTables -->
<script src="{{asset('admin')}}/assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="{{asset('admin')}}/assets/plugins/datatables/plugins/bootstrap/dataTables.bootstrap4.min.js"></script>
<!-- Common js-->
<script src="{{asset('admin')}}/assets/js/app.js"></script>
<script src="{{asset('admin')}}/assets/js/layout.js"></script>
<!-- material -->
<script src="{{asset('admin')}}/assets/plugins/material/material.min.js"></script>
<script src="{{asset('admin')}}/assets/js/pages/material-select/getmdl-select.js"></script>
<script src="{{asset('admin')}}/assets/plugins/material-datetimepicker/moment-with-locales.min.js"></script>
<script src="{{asset('admin')}}/assets/plugins/material-datetimepicker/bootstrap-material-datetimepicker.js"></script>
<script src="{{asset('admin')}}/assets/plugins/material-datetimepicker/datetimepicker.js"></script>

<!-- end js include path -->
@yield('js')
</body>
</html>
@else
    <div style="text-align: center">
        <h1>Bạn không có quyền try cập vào trang này ! Vui lòng <a href="{{route('login')}}" style="color: red"> Đăng nhâp</a> để tiếp tục sử dụng !</h1>
    </div>
@endif
