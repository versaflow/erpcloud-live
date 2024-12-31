

@if(!empty($remove_container) || !empty($is_primary_tab))
@yield('content')
@else
<div id="modal_content" class="container-fluid py-1 h-100 d-flex flex-column flex-grow-1 m-0 p-0">
@yield('content')

</div>
@endif
@if(empty($is_primary_tab) )
@stack('page-scripts')
@stack('page-styles')
@endif
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
</style>

    @section('ajax-styles')
    @section('ajax-scripts')