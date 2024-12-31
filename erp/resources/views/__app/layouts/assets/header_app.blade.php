<!-- Theme overwrites -->

<link href="{{ '/assets/libraries/fontawesomev5/css/all.css' }}" rel="stylesheet"> 
<link href="{{ '/assets/main/app.css' }}" rel="stylesheet"> 
<style>
.navbar-vertical .navbar-brand>img, .navbar-vertical .navbar-brand-img {
    max-width: 100%;
    max-height: 100%;
}



.g-sidenav-hidden .nav-item.text-center-collapsed {
    text-align: center !important;
 
}

.g-sidenav-show .nav-item.text-center-collapsed h6{
    padding-left: 1.5rem !important;
}
.g-sidenav-show.g-sidenav-hidden .nav-item.text-center-collapsed h6{
    padding-left: 0rem !important;
}

.sidenav-footer{
   
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}
.g-sidenav-hidden .sidenav-footer,.g-sidenav-hidden .sidenav-header{
    display: none;
}
/*
.g-sidenav-hidden #company_logins_dropdown_container{
    display: none;
}
*/

.g-sidenav-show .navbar-vertical .nav-item .collapse .nav .nav-item .nav-link {
    margin-left: 0;
}

/*
.g-sidenav-hidden .navbar-vertical:hover #company_logins_dropdown_container {
    transition: 0.3s ease;
    display: block;
  }
  */
.g-sidenav-hidden .navbar-vertical:hover .sidenav-header {
    transition: 0.3s ease;
    display: block;
  }
  .g-sidenav-hidden .navbar-vertical:hover .sidenav-footer {
    transition: 0.3s ease;
    display: flex;
  }


@if(session('role_level') == 'Admin')
/*
.g-sidenav-hidden .navbar-vertical.fixed-start~.main-content {
    margin-left: 0px !important;
}
.g-sidenav-hidden .sidenav.navbar {
    display: none !important;
}
*/
@endif

#main-container .nav.nav-pills{
    height: 50px;
    border-radius: 0;
}
.btn-group-xs > .btn, .btn-xs {
  padding: .25rem .4rem;
  font-size: .875rem;
  line-height: .5;
  border-radius: .2rem;
}
.e-sidebar-overlay {
    z-index: 1100;
}
.ql-container .ql-custom{
    display: none !important;
}

#navbar_header.e-toolbar, #navbar_header.e-toolbar .e-toolbar-items {
    background-color: #ffffff;
}
.nav.nav-pills .nav-link.active {
    background: #fff;
}

.b-radius-top{
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
}
/* left menu smaller */
@media (min-width: 1200px){
.g-sidenav-hidden .navbar-vertical.fixed-start~.main-content {
    margin-left: 5.5rem;
}
.g-sidenav-hidden .navbar-vertical {
    max-width: 4rem !important;
}
}
.navbar-vertical.navbar-expand-xs .navbar-nav .nav-link {
    margin: 0rem;
}

.sidebarbtn{
    color: #000 !important;
}
.sidebarbtn:hover{
    color: #000 !important;
}

.tox-dialog-wrap{
z-index:3000 !important;	
}
.tox-dialog-wrap{
z-index:3000 !important;	
}
.tox .tox-dialog {
z-index:3001 !important;
}
.tox-tinymce-aux{z-index:99999999999 !important;}

</style>


 <style id="erp_color_scheme">
    
 @if (isset($color_scheme) && isset($grid_id))
    #app_toolbar .k-button {
    background: {{ $color_scheme['first_row_buttons_color'] }} !important;
    }
    
    
    #gridheadertoolbar{{ $grid_id }} .k-button {
    background: {{ $color_scheme['second_row_buttons_color'] }} !important;
    }
    
    #adminheader, #app_toolbar, #app_toolbar .e-toolbar-items {
    background: {{ $color_scheme['first_row_color'] }};
    }
    #adminheader, #pbx_toolbar, #pbx_toolbar .e-toolbar-items {
    background: {{ $color_scheme['first_row_color'] }};
    }
    
    
    #gridheadertoolbar{{ $grid_id }}, #gridheadertoolbar{{ $grid_id }} .e-toolbar-items{
    background-color: {{ $color_scheme['second_row_color'] }};
    }
    #gridheadertoolbar{{ $grid_id }}, #gridheadertoolbar{{ $grid_id }} .e-toolbar-items{
    background-color: {{ $color_scheme['second_row_color'] }};
    }
    #main-container .nav.nav-pills{
       /* background-color: {{ $color_scheme['second_row_color'] }};*/
       background-color: #f8f8f8;
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
  
    #gridheadertoolbar{{ $grid_id }} #toolbar_template_grid_adminbtns{{ $grid_id }} .k-button {
    background: #ffffff !important;
    }
@endif

    span.global_search_input{
    border-top-left-radius: 0px !important;
    border-bottom-left-radius: 0px !important;    
    }
    .e-toast-container{
    z-index: 11000 !important    
    }
    </style>
    <style>

    .btn:hover:not(.btn-icon-only) {
        box-shadow: 0 3px 5px -1px rgba(0, 0, 0, 0.09), 0 2px 3px -1px rgba(0, 0, 0, 0.07);
        transform: none !important;
    }
    body.bg-gray-100{
        background-color: rgb(241 245 249) !important;
    }
  .mce-content-body p{
	margin: 0 !important;
    margin-block-start: 0 !important;
    margin-block-end: 0 !important;
}
.tinymce p{
	margin: 0 !important;
    margin-block-start: 0 !important;
    margin-block-end: 0 !important;
}
.g-sidenav-hidden .navbar-vertical {
    max-width: 1rem !important;
}
.g-sidenav-hidden .navbar-vertical.fixed-start~.main-content {
    margin-left: 1.5rem;
}
#global_search,#product_search,#customer_search,#panel_switcher{
font-weight: bold;
}
.table thead th {
    padding: 0.75rem 0rem;
}
.ag-theme-alpine-dark .ag-paging-panel, .ag-theme-alpine .ag-paging-panel,.ag-theme-alpine-dark .ag-status-bar, .ag-theme-alpine .ag-status-bar {
    
    background-color: ivory !important;
}

.ag-theme-alpine .ag-status-bar .eStatusBarLeft{
    font-size:12px;
}

#toolbar_template_services_menu .e-menu-wrapper{
    background-color: #00afef;
}
.e-btn:focus{
    box-shadow: none !important;
}

#navbar_header{
    overflow:visible !important;
}
</style>