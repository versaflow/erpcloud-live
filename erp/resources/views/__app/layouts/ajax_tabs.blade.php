@php 
if(empty($workspace_ids)){
$workspace_ids = [];
}
$enable_main_menu = (session('instance')->show_left_menu && empty($webform) && session('role_level') == 'Admin' && !empty($main_menu_menu) && count($main_menu_menu) > 0) ? true : false;

//$enable_main_menu = (is_superadmin()) ? true : false;
$show_grid_sidebar = false;

if(session('role_level') == 'Admin'){
    if(empty($hide_content_sidebar) && !empty($grid_id)){
    $show_grid_sidebar = true;
    }
}elseif(session('role_level') > ''){
    //if(!empty($app_id) && $app_id == 12){
    //    $show_grid_sidebar = true;
    //}
    
    if(empty($hide_content_sidebar) && !empty($grid_id)){
    $show_grid_sidebar = true;
    }
}

@endphp

<div class="container-fluid h-100 d-flex flex-column flex-grow-1 m-0 p-0 bg-red">
   <div class="container-fluid p-0" id="main-container">

        <div class="row mt-0 flex-grow-1 mx-0 mb-0 px-0" >
        <div class="p-0 @if(!$show_grid_sidebar) col-12 @else col-12 col-md-9 ps-0 @endif ">
           @yield('content')
        </div>
        <div class="col-12 col-md-3 col-sm-12 @if(!$show_grid_sidebar) d-none @endif px-0 ps-2">
            @if($show_grid_sidebar)
            @if(is_dev())
            @include('__app.layouts.partials.content_sidebar')
            @else
            @include('__app.layouts.partials.content_sidebar')
            @endif
            @endif
        </div>
        </div>
     
    </div>

</div>


@stack('page-scripts')
@stack('page-styles')

<style>



@if(!empty($remove_container))
.e-dialog .e-dlg-content {
    padding: 0px;
}

#modal_content{
display: flex;
flex-direction: column;
height: 100%;
}
@endif
</style>

<style id="erp_color_scheme">

#app_toolbar .k-button {
    background: {{ $color_scheme['first_row_buttons_color'] }} !important;
}


#gridheadertoolbar{{ $grid_id }} .k-button {
    background: {{ $color_scheme['second_row_buttons_color'] }} !important;
}

#adminheader, #app_toolbar, #app_toolbar .e-toolbar-items {
    background: {{ $color_scheme['first_row_color'] }};
}


#gridheadertoolbar{{ $grid_id }}, #gridheadertoolbar{{ $grid_id }} .e-toolbar-items{
    background-color: {{ $color_scheme['second_row_color'] }};
}

#topicon_menu,#topicon_menu li a,#topicon_menu li .e-menu-icon,#topicon_menu  .e-menu-item:hover,#topicon_menu .e-menu-item.e-selected {
    background-color: {{ $color_scheme['sidebar_color'] }};
    color: white; 
}
.sidebar-menu, .sidebar-menu ul {
    background: {{ $color_scheme['sidebar_color'] }} !important;
}
.sidebar-menu .dock-menu .e-menu-wrapper, .sidebar-menu .dock-menu.e-menu-wrapper, .sidebar-menu .dock-menu.e-menu-wrapper ul>*, .sidebar-menu .dock-menu .e-menu-wrapper ul>* {
    background: {{ $color_scheme['sidebar_color'] }} !important;
   
}
.sidebar-menu .e-menu-wrapper ul .e-menu-item .e-menu-url, .sidebar-menu .e-menu-container ul .e-menu-item .e-menu-url, .sidebar-menu .e-menu-wrapper ul .e-menu-item .e-menu-icon, .sidebar-menu .e-menu-container ul .e-menu-item .e-menu-icon, .sidebar-menu .e-menu-wrapper ul .e-menu-item .e-menu-icon::before, .sidebar-menu .e-menu-container ul .e-menu-item .e-menu-icon::before, .sidebar-menu .e-menu-wrapper ul .e-menu-item .e-caret, .sidebar-menu .e-menu-container ul .e-menu-item .e-caret {

    color: {{ $color_scheme['sidebar_text_color'] }} !important;
   
}
.dock-menu.e-menu-wrapper .e-ul .e-menu-item .e-menu-url, .dock-menu.e-menu-wrapper ul .e-menu-item .e-caret {
    color: {{ $color_scheme['sidebar_text_color'] }} !important;
}
#customer_toolbar, #customer_toolbar .e-toolbar-items{
    background: {{ $color_scheme['sidebar_color'] }};
}


.dock-menu .e-menu-wrapper,
.dock-menu.e-menu-wrapper,
.dock-menu.e-menu-wrapper ul>*,
.dock-menu .e-menu-wrapper ul>* {
background: {{ $color_scheme['sidebar_color'] }} !important;
}


#toolbar_template_title{{ $grid_id }}, #toolbar_template_title{{ $grid_id }} span{
    font-family: "Lato", Arial, Sans-serif !important;
    font-weight: bold;
    color: {{$color_scheme['second_row_text_color']}};
    user-select: text;
}

#toolbar_template_title{{ $grid_id }} ,#toolbar_template_title{{ $grid_id }}:hover,#toolbar_template_title{{ $grid_id }} h1,#toolbar_template_title{{ $grid_id }} h1:hover{

    user-select: text;
    cursor: text;
}

#gridheadertoolbar{{ $grid_id }} .k-button.e-disabled{display:none !important;}
.e-toolbar .e-toolbar-items .e-toolbar-item:not(.e-separator) {
    min-width: 0px;
}

span.global_search_input{
border-top-left-radius: 0px !important;
border-bottom-left-radius: 0px !important;    
}
.e-toast-container{
z-index: 11000 !important    
}
.e-toolbar-item.pinnedtab{border: none !important;}
</style>

    @section('ajax-styles')
    @section('ajax-scripts')