<div class="sidebar-container">
    <div class="sidemenu-container navbar-collapse collapse fixed-menu">
        <div id="remove-scroll" class="left-sidemenu">
            <ul class="sidemenu  page-header-fixed slimscroll-style" data-keep-expanded="false" data-auto-scroll="true"
                data-slide-speed="200" style="padding-top: 20px">
                <li class="sidebar-toggler-wrapper hide">
                    <div class="sidebar-toggler">
                        <span></span>
                    </div>
                </li>
                <li class="sidebar-user-panel">
                    <div class="user-panel">
                        <div class="pull-left image">
                            <img src="{{asset('admin/images')}}/user.jpg" class="img-circle user-img-circle"
                                 alt="User Image"/>
                        </div>
                        <div class="pull-left info">
                            <p> {{Auth::user()->name}}</p>
                            <a href="#"><i class="fa fa-circle user-online"></i><span
                                    class="txtOnline"> Online</span></a>
                        </div>
                    </div>
                </li>
                @if(Auth::user()->role_id == 1)
                    <li class="nav-item">
                        <a href="event.html" class="nav-link nav-toggle"> <i class="material-icons">dashboard</i>
                            <span class="title">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link nav-toggle"><i class="material-icons">group</i>
                            <span class="title">Người dùng</span><span class="arrow"></span></a>
                        <ul class="sub-menu">
                            <li class="nav-item">
                                <a href="{{route('admin.user.index')}}" class="nav-link "> <span
                                        class="title">Danh sách</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{route('admin.user.create')}}" class="nav-link "> <span
                                        class="title">Tạo mới</span></a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link nav-toggle"> <i class="material-icons">business</i>
                            <span class="title">Nhà thuốc</span> <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item">
                                <a href="{{route('admin.drugstore.index')}}" class="nav-link "> <span class="title">Danh sách</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{route('admin.drugstore.create')}}" class="nav-link "> <span class="title">Tạo mới</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item  ">
                        <a href="#" class="nav-link nav-toggle"><i class="material-icons">assignment</i>
                            <span class="title">Thuốc</span><span class="arrow"></span></a>
                        <ul class="sub-menu">
                            <li class="nav-item">
                                <a href="{{route('admin.drug.index')}}" class="nav-link "> <span class="title">Danh sách</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link nav-toggle"> <i class="material-icons">business</i>
                            <span class="title">Đơn vị</span> <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item">
                                <a href="{{route('admin.unit.index')}}" class="nav-link "> <span
                                        class="title">Danh sách</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{route('admin.unit.create')}}" class="nav-link "> <span
                                        class="title">Tạo mới</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.drugstore.moveDrug')}}" class="nav-link "> <i class="material-icons">business</i>
                            <span class="title">Chuyển thuốc</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.connect.form')}}" class="nav-link "> <i
                                class="material-icons">business</i> <span class="title">Kiểm Tra Kết Nôi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link nav-toggle"> <i class="material-icons">business</i>
                            <span class="title">Quảng cáo</span> <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item">
                                <a href="{{route('admin.linkads.index')}}" class="nav-link "> <span
                                        class="title">Danh sách</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{route('admin.linkads.viewcreate')}}" class="nav-link "> <span
                                        class="title">Tạo mới</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item ">
                        <a href="#" class="nav-link nav-toggle"><i class="material-icons">assignment</i>
                            <span class="title">Đặt Hàng</span><span class="arrow"></span></a>
                        <ul class="sub-menu">
                            <li class="nav-item">
                                <a href="{{route('admin.order.index')}}" class="nav-link ">
                                    <span class="title">Danh sách đặt hàng</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{route('admin.order.orders_returned')}}" class="nav-link ">
                                    <span class="title">Danh sách đã trả hàng</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="javascript:;" class="nav-link nav-toggle"> <i class="material-icons">notifications_none</i>
                            <span class="title">Thông báo</span> <span class="arrow"></span>
                        </a>
                        <ul class="sub-menu">
                            <li class="nav-item">
                                <a href="{{route('admin.notification.template')}}" class="nav-link "> <span
                                        class="title">Mẫu thông báo</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{route('admin.notification.listNoti')}}" class="nav-link "> <span
                                        class="title">Danh sách thông báo</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{route('admin.notification.createNoti')}}" class="nav-link "> <span
                                        class="title">Tạo mới thông báo</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item  ">
                        <a href="#" class="nav-link nav-toggle"><i class="material-icons">assignment</i>
                            <span class="title">Thuốc</span><span class="arrow"></span></a>
                        <ul class="sub-menu">
                            <li class="nav-item">
                                <a href="{{route('admin.drug.index')}}" class="nav-link "> <span class="title">Danh sách</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
