<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="index" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ public_path().('/assets/velzon/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ public_path().('/assets/velzon/images/logo-dark.png') }}" alt="" height="17">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="index" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ public_path().('/assets/velzon/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ public_path().('/assets/velzon/images/logo-light.png') }}" alt="" height="17">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
            
                <!--
                MENU SECTION TITLE
                <li class="menu-title"><span>@lang('translation.menu')</span></li>
                <li class="menu-title"><i class="ri-more-fill"></i> <span>@lang('translation.pages')</span></li>
                -->
        
                
                @if(!empty($main_menu_menu) && count($main_menu_menu) > 0)  
                    @foreach($main_menu_menu as $menu_item)
 
                        @if($menu_item->items > 0)
                        <!-- two level menu -->
                        <li class="nav-item">
                            <a  class="nav-link menu-link" href="#{{$menu_item->navlink}}" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="{{$menu_item->navlink}}">
                                <i class="{{$menu_item->velzon_icon}}"></i> <span>{{$menu_item->text}}</span>
                            </a>
                            <div class="collapse menu-dropdown" id="{{$menu_item->navlink}}">
                                
                                <ul class="nav nav-sm flex-column">
                                <li class="menu-subtitle"><span>{{$menu_item->text}}</span></li>
                                    @foreach($menu_item->items as $sub_item)
                                    <li class="nav-item">
                                        <a  href="{{$sub_item->url}}" @if($sub_item->data_target > '') data-target="{{$sub_item->data_target}}" @endif class="nav-link">{{$sub_item->text}}</a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li> <!-- end Dashboard Menu -->
                        @else
                        <li class="nav-item">
                            <a  href="{{$menu_item->url}}" @if($menu_item->data_target > '') data-target="{{$menu_item->data_target}}" @endif class="nav-link">
                                <i class="{{$menu_item->velzon_icon}}"></i> <span>{{$menu_item->text}}</span>
                            </a>
                        </li>
                        
                        @endif
                    @endforeach
                @endif
               

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
