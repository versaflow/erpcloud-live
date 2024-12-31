<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="/softui/assets/img/apple-icon.png">
    @if(!empty($favicon))
    <link rel="shortcut icon" href="{{ $favicon }}" type="image/x-icon">
    @else
    <link rel="shortcut icon" href="{{ session('favicon') }}" type="image/x-icon">
    @endif
  <title>
   {{$menu_name}}
  </title>
  @include('__app.layouts.assets.main')
  @stack('header_assets')
    <!-- @if(is_main_instance() && session('role_level') == 'Admin')  -->
    <!-- && session('partner_id') == 1) -->
    <!-- <script id="sbinit" src="https://helpdesk.telecloud.co.za/js/main.js?mode=tickets"></script> -->
      <!-- <script src="https://helpdesk.telecloud.co.za/js/min/jquery.min.js"></script>
      <script id="sbinit" src="https://helpdesk.telecloud.co.za/js/main.js"></script> -->
    <!-- @endif -->
    <!-- <script>SB_TICKETS = true;</script> -->
</head>

<body class="g-sidenav-show  g-sidenav-hidden  bg-gray-100">
@include('__app.components.dialog') 
@yield('dialog')
   <div id="body_container">   
@include('__app.layouts.alerts') 

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

@if($enable_main_menu)

  <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-1 fixed-start ms-1 bg-white" id="sidenav-main">
    <div class="sidenav-header">
      <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand m-0"  target="_blank">
        @if(!empty($branding_logo))
        <img src="{{ url($branding_logo) }}" class="navbar-brand-img h-100" alt="main_logo">
        @endif
      </a>
    </div>
    <hr class="horizontal dark mt-2 mb-1">
    @include('__app.layouts.partials.main_menu')
    @if(!empty($panel_logo))
    <div class="sidenav-footer d-none mt-3">
      <div class="card card-background shadow-none card-background-mask-secondary" id="sidenavCard">
        <div class="full-background" style="background-image: url('/softui/assets/img/curved-images/white-curved.jpg')"></div>
        <div class="card-body text-start p-1 w-100 my-2">
             <div id="panel-logo" class="p-1 text-center w-100"><img title="Logo" src="{{ url($panel_logo) }}" style="max-height:60px"/></div>  
        </div>
      </div>
    </div>
    @endif
  </aside>
  @endif

@include('__app.layouts.sidebars') 


    
    @yield('left_sidebar')
    @yield('right_sidebar')
    
  <main class="main-div border-radius-lg">
   @if(empty($webform))

    
            @if(is_dev())
    @include('__app.layouts.partials.navbar')
            @else
    @include('__app.layouts.partials.navbar')
            @endif
  @endif
    <div class="container-fluid p-0 d-none" id="main-container">

        <div class="row mt-1 flex-grow-1 mx-0 mb-2 px-0" >
        <div class="p-0 @if(!$show_grid_sidebar) col-12 @else col-12 col-md-9 ps-2 @endif ">
           @yield('content')
        </div>
        <div class="col-12 col-md-3 col-sm-12 @if(!$show_grid_sidebar) d-none @endif px-2">
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
       <div class="container-fluid p-1 h-100 d-flex flex-column flex-grow-1" id="main-loading-container">
        <div class="row mt-2 flex-grow-1">
        <div class="col-9">
          
<div class="ph-item">
    <div class="ph-col-12">
        <div class="ph-picture"></div>
        <div class="ph-row">
            <div class="ph-col-6 big"></div>
            <div class="ph-col-4 big"></div>
            <div class="ph-col-2 big"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12"></div>
        </div>
    </div>
</div>
        </div>
        <div class="col-3">
           <div class="ph-item">
    <div class="ph-col-12">
        <div class="ph-picture"></div>
        <div class="ph-row">
            <div class="ph-col-12 big"></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12"></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12 "></div>
            <div class="ph-col-12"></div>
        </div>
    </div>
</div>
        </div>
        </div>
     
    
    </div>
   
  </main>
  
    @stack('footer_assets')
    <!-- APP SCRIPTS BEGIN-->
   </div> 
    
    <script>
    
    function render_task_listview(task_id,task_container){
    $.get('project_tasks_render/'+task_id, function(data) {
    $('#'+task_container).html(data);
    })
    //console.log($('#'+task_container));
    }
    
    /*
    @if(session('is_api_session') || is_dev())
    $(document).ready(function() {
    
    var consoleLogDiv = $('#console-log'); // Specify the ID or selector of your target div element
    
    // Save references to the original console functions
    var originalConsoleLog = //console.log;
    var originalConsoleError = console.error;
    
    //console.log = function(message) {
    // Append the log message to the target div
    consoleLogDiv.append('<p class="console-log">' + message + '</p>');
    
    // Call the original //console.log function with the same arguments
    originalConsoleLog.apply(console, arguments);
    };
    
    console.error = function(message) {
    // Append the error message to the target div
    consoleLogDiv.append('<p class="console-error">' + message + '</p>');
    
    // Call the original console.error function with the same arguments
    originalConsoleError.apply(console, arguments);
    };
    
    });
    @endif
    */
    
    ej.base.enableRipple(true);
    
    function enable_js_debugger(){
    setTimeout(function(){debugger;}, 5000);    
    }
    
    
    
    
    
    
    $(document).on('click','#showrightsidebar', function(e){
    $("#showrightsidebar").addClass('d-none');
    sidebarformcontainer.show();
    });
    </script>
    <script data-turbo-permanent id="lw_noti">
    /*
    const noti_button = document.getElementById('notificationButton');
    const noti_dropdown = document.getElementById('notificationDropdown');
    if(noti_button){
    let isDropdownOpen = false;
    
    noti_button.addEventListener('mouseenter', () => {
    isDropdownOpen = true;
    noti_dropdown.classList.remove('invisible');
    noti_dropdown.classList.add('visible');
    });
    
    noti_dropdown.addEventListener('mouseleave', () => {
    // Delay the closing to allow moving the mouse to the dropdown
    setTimeout(() => {
    if (!isDropdownOpen) {
    noti_dropdown.classList.remove('visible');
    noti_dropdown.classList.add('invisible');
    }
    }, 200); // Adjust the delay as needed
    });
    
    noti_dropdown.addEventListener('mouseenter', () => {
    isDropdownOpen = true;
    });
    
    noti_dropdown.addEventListener('mouseleave', () => {
    isDropdownOpen = false;
    noti_dropdown.classList.remove('visible');
    noti_dropdown.classList.add('invisible');
    });
    }
    */
    </script>
    
    
    
    
    
    
    <script>
    function copyToClipboard(text) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();
    }
    
    $.fn.hasAttr = function(name) {  
    return this.attr(name) !== undefined;
    };
    
    @if(session('role_level') == 'Admin')
    // global search
   
    $(document).on('click', function(e)
    {
    var jqTarget = $(e.target);
    if ( !jqTarget.closest('#search').length ) 
    {
    $("#search").hide();
    }
    });
    
    @endif
    
    $(document).on('click',function(e){
    @if(session('role_level') == 'Admin')
    try{
    var close_popup = true;
    if(e && e.target && $(e.target).hasClass('e-clear-icon')){
    
    close_popup=true;    
    }else if(e && e.target && $(e.target).hasClass('external_link')){
    close_popup=false;    
    
    }else if(e && e.target && $(e.target).parents('.global_search_row').length){
    
    close_popup=false;   
    }else if(e && e.target && $(e.target).parents('.searchinputgroup').length){
    
    close_popup=false;   
    }
    
    if(close_popup){
    global_search.hidePopup();
    $("#global_search_popup").hide();
    }
    }catch(e){
    
    }
    @endif
    })
    
    function makeSubPayment(){
    
    viewDialog('make_payment','payfast_sub', 'Make a Payment', '60%');
    }
    
    function makePayment(){
    
    viewDialog('make_payment','paynowlink', 'Make a Payment', '60%');
    }
    
    function placeOrder(){
    ////console.log('placeOrder');
    ////console.log('/{{$documents_url}}/edit');
    transactionDialog('place_order','/{{$documents_url}}/edit', 'Create Quote', '80%');
    }
    function buyAirtime(){
    
    transactionDialog('place_order','/{{$documents_url}}/edit?doctype=invoice&buy_product_id=913&account_id='+session_account_id, 'Create Quote', '80%');
    }
    function placeAirtimeOrder(){
    
    quickBuyDialog('place_airtime_order','/airtime_form', 'Buy Airtime', '60%');
    }
    
    function countChars(obj){
    
    var currentLength = $(obj).val().length;
    $('#'+$(obj).attr('id')+'count').text(currentLength+' characters');
    }
    
    function isMobile() {
    try{ document.createEvent("TouchEvent"); return true; }
    catch(e){ return false; }
    }
    
    session_is_dev = '{{ is_dev() }}';
    
    @if($module_id)
    module_id = {{$module_id}};
    @else
    module_id = null;
    @endif
    
    @if(check_access('21') && $account->pabx_domain > '')
    session_pabx_customer = 1;
    @else
    session_pabx_customer = 0;
    @endif
    
    @if(session("account_id"))
    session_currency_symbol = 'R';
    session_parent_id = '{{session("parent_id")}}';
    session_partner_id = '{{session("partner_id")}}';
  
    session_user_id = '{{session("user_id")}}';
    session_account_id = '{{session("account_id")}}'; 
    session_account_active = '{!! is_account_active(); !!}'; 
    session_group_id = '{{ session("role_id") }}';
    session_instance_id = '{{ session("instance")->id }}';
    session_role_id = '{{ session("role_id") }}';
    session_role_level = '{{ session("role_level") }}';
    session_superadmin = '{{ is_superadmin() }}';
    @endif
    
    
    
    
    
    @if(!empty($pbx_admin_menu_menu) && count($pbx_admin_menu_menu) > 0)
    window['pbx_toolbar'] = new ej.navigations.Toolbar({
    overflowMode: 'Scrollable',
    height: '50px',
    items: [
    { template: "#toolbar_template_pbx_admin_menu{{ $grid_id }}", align: 'left' },
    ]
    });
    window['pbx_toolbar'].appendTo('#pbx_toolbar');
    @endif
    
   
    
    
    
  
    </script>
    
    
    <style>
    
    .e-control, .e-css {
    font-size: 13px !important;
    }

    #inline-grid .e-gridheader{
    display: none !important;
    }
    .workboard_checklist{
    height:300px;
    overflow-y:scroll;
    background-color: #fff;
    }
    .workboard_checklist.e-listview .e-list-item{
    height: 26px !important;
    line-height: 23px !important;
    }
    #app_sidebar_tabs ol, #app_sidebar_tabs ul{
    list-style: inside;
    }
    
    p,ol{
    margin-bottom: 0 !Important;    
    }
    .row.bg-red{
    background:red !important;    
    }
    .ag-row.bg-lightred{
    background:indianred !important;    
    }
    #topicon_menu,#topicon_menu li a,#topicon_menu li .e-menu-icon,#topicon_menu  .e-menu-item:hover,#topicon_menu .e-menu-item.e-selected {
    background-color: #19457e;
    color: white; 
    }
    #topicon_menu li .e-menu-icon{
    font-size: 16px;    
    }
    #globalsearch{
    border-top-right-radius: 0px;
    border-bottom-right-radius: 0px;
    }
    .top-menu .e-menu-icon, .top-menu .e-anchor-wrap{
      margin-bottom: 0 !important;
      height: 34px !important;
      line-height: 34px !important;
    }
    
    
    .grid-toolbar .e-menu-wrapper:not(.e-popup):not(.e-vertical),
    .grid-toolbar  .e-menu-wrapper:not(.e-popup):not(.e-vertical) ul,
    .grid-toolbar  .e-menu-wrapper:not(.e-popup):not(.e-vertical) ul .e-menu-item,
    .grid-toolbar .e-menu-wrapper:not(.e-popup):not(.e-vertical) ul .e-menu-item .e-caret {
    line-height: 34px;
    height: 34px;
}
    
    .e-menu-wrapper.k-button-group .e-menu-item.k-button{
    height: 34px !important;
    line-height: 34px;
    min-height: 34px !important;
    }
    .e-menu-wrapper.k-button-group ul .e-menu-item .e-caret, .e-menu-container ul .e-menu-item.k-button .e-caret {
    line-height: 34px;
    }
    .e-menu-item.k-button{
    font-size:12px;
    border-radius: 0px !important;
    border-color: #bbb !important;
    color: #2e2e2e !important;
    background-color: #e9e9e9 !important;
    background-position: 50% 50% !important;
    background-image: url(https://kendo.cdn.telerik.com/2021.2.616/styles/textures/highlight.png) !important;
    background-image: none,linear-gradient(to bottom,rgba(255,255,255,.25) 0,rgba(255,255,255,0) 100%) !important;
    }
    .e-menu-item.k-button.layout_active{
    background-color: #ccc !important;
    background-position: 50% 50% !important;
    background-image: url(https://kendo.cdn.telerik.com/2021.2.616/styles/textures/highlight.png) !important;
    background-image: none,linear-gradient(to bottom,rgba(255,255,255,.25) 0,rgba(255,255,255,0) 100%) !important;
    }
    
    .e-menu-item.e-disabled.k-button, .e-menu-item.e-disabled.k-button a{
    color: #c8c8c8 !important;
    }
    .k-button-group.top-menu:not(.e-menu-popup) .e-menu-item.k-button:first-child {
    border-top-left-radius: 4px !important;
    border-bottom-left-radius: 4px !important;
    }
    .k-button-group.top-menu:not(.e-menu-popup) .e-menu-item.k-button:last-child {
    border-top-right-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
    }
    .k-button-group.top-menu.e-menu-popup .e-menu-item.k-button:first-child {
    border-top-left-radius: 0px !important;
    border-bottom-left-radius: 0px !important;
    }
    .k-button-group.top-menu.e-menu-popup .e-menu-item.k-button:last-child {
    border-top-right-radius: 0px !important;
    border-bottom-right-radius: 0px !important;
    }
    
    .k-button-group .k-button+.k-button {
    margin-left: -1px !important;
    }
    
    
    #header .e-menu-item.k-button .e-caret {
    line-height: 26px !important;
    }
    
    #header .dock-menu .e-menu-wrapper.k-button-group, #header .dock-menu.e-menu-wrapper.k-button-group, #header .dock-menu.e-menu-wrapper.k-button-group ul>*, #header .dock-menu .e-menu-wrapper.k-button-group ul>* {
    
    font-size:12px;
    border-radius: 0px !important;
    border-color: #bbb !important;
    color: #2e2e2e !important;
    background-color: #e9e9e9 !important;
    background-position: 50% 50% !important;
    background-image: url(https://kendo.cdn.telerik.com/2021.2.616/styles/textures/highlight.png) !important;
    background-image: none,linear-gradient(to bottom,rgba(255,255,255,.25) 0,rgba(255,255,255,0) 100%) !important;
    }
    #header .e-menu-wrapper.k-button-group ul .e-menu-item .e-menu-url, #header .e-menu-container ul .e-menu-item .e-menu-url, #header .e-menu-wrapper.k-button-group ul .e-menu-item .e-menu-icon, #header .e-menu-container ul .e-menu-item .e-menu-icon, #header .e-menu-wrapper.k-button-group ul .e-menu-item .e-caret, #header .e-menu-container ul .e-menu-item .e-caret {
    
    color: #2e2e2e !important;
    }
    
    .dock-menu .e-menu-wrapper.k-button-group, .dock-menu.e-menu-wrapper.k-button-group, .dock-menu.e-menu-wrapper.k-button-group ul>*, .dock-menu .e-menu-wrapper.k-button-group ul>* {
    font-size:12px;
    border-radius: 0px !important;
    border-color: #bbb !important;
    color: #2e2e2e !important;
    background-color: #e9e9e9 !important;
    background-position: 50% 50% !important;
    background-image: url(https://kendo.cdn.telerik.com/2021.2.616/styles/textures/highlight.png) !important;
    background-image: none,linear-gradient(to bottom,rgba(255,255,255,.25) 0,rgba(255,255,255,0) 100%) !important;
    }
    
    .dock-menu.e-menu-wrapper.k-button-group .e-ul .e-menu-item .e-menu-url, .dock-menu.e-menu-wrapper.k-button-group ul .e-menu-item .e-caret {
    color: #2e2e2e !important;
    }
    
    .dock-menu.e-menu-wrapper.k-button-group ul .e-menu-item.e-focused, .dock-menu .e-menu-wrapper.k-button-group ul .e-menu-item:hover {
    color: #2e2e2e !important;
    border-color: #b6b6b6 !important;
    background-color: #a99f9a !important;
    background-image: url(https://kendo.cdn.telerik.com/2021.2.616/styles/textures/highlight.png) !important;
    background-image: none,linear-gradient(to bottom,rgba(255,255,255,.25) 0,rgba(255,255,255,0) 100%) !important;
    }
    
    
    .e-menu-item.k-button.k-state-hover, .e-menu-item.k-button:hover {
    color: #2e2e2e !important;
    border-color: #b6b6b6 !important;
    background-color: #a99f9a !important;
    background-image: url(https://kendo.cdn.telerik.com/2021.2.616/styles/textures/highlight.png) !important;
    background-image: none,linear-gradient(to bottom,rgba(255,255,255,.25) 0,rgba(255,255,255,0) 100%) !important;
    }
    
    #header .e-menu-item.k-button.k-state-hover, #header .e-menu-item.k-button:hover, #header .e-menu-wrapper.k-button-group ul .e-menu-item:hover {
    color: #2e2e2e !important;
    border-color: #b6b6b6 !important;
    background-color: #a99f9a !important;
    background-image: url(https://kendo.cdn.telerik.com/2021.2.616/styles/textures/highlight.png) !important;
    background-image: none,linear-gradient(to bottom,rgba(255,255,255,.25) 0,rgba(255,255,255,0) 100%) !important;
    }
    .e-menu-wrapper.top-menu .e-ul .e-menu-item .e-menu-icon {
    font-size: 14px;
    line-height: 20px;
    }
    #admin_policies .e-caret{
    line-height: 26px;
    height: 26px;
    margin-top: 0px;
    color: #333;
    }
    .external_link{
    text-indent: 0;    
    }
    
    
    
    #logo-img {
    max-height: 36px;
    max-width: 250px;
    }
    
    .maxZindex{
    z-index:10000 !important;    
    }
    .maxZindexLeft{
    z-index:9000 !important;    
    }
    
    
    .sidebarview .sidebarformbtn{
    display:none;
    }
    
    .global_search_row{
    font-size:12px;    
    }
    .global_search_icon{
    font-size:18px;    
    }
    .search_icon_col{
    min-width:50px;    
    }
    
    #js-licensing{display:none !important;}
    .grid-tooltip{
    font-size: 16px;
    }
    .e-tooltip-wrap.description-tooltip .e-tip-content {
    
    font-size: 16px;
    line-height: 20px;
    
    }
    
    .e-dropdownbase .e-list-item {
    line-height: 26px;
    min-height: 26px;
    }
    .module_card .badge{
    font-size:16px;    
    }
    .module_card a {
    color: #242424;
    text-decoration: none;
    }
    .module_card a:hover {
    color: inherit;
    text-decoration: none;
    }
    .tabtext-bold .e-tab-text{
    font-weight:bold !important;    
    }
    .dock-menu.e-menu-wrapper .e-ul .e-menu-item.e-disabled .e-menu-url, .dock-menu.e-menu-wrapper ul .e-menu-item.e-disabled .e-caret{
    color: #ccc !important;
    }
    /*
    #toolbar_template_pbx_menu{{ $grid_id }} .e-disabled{
    display:none !important;    
    }
    */
    
    .global_search_input .badge{
    text-indent: 0;
    height: fit-content;
    min-height: 0px !important;
    padding: 4px !important;
    /* 
    position: absolute;
    right: 5px;
    top: 4px;  
    */
    }
    .global_search_input .k-button[disabled]{
    display:none !Important;    
    }
    .global_search_row ,.global_search_row p{
    font-size: 12px !important;
    }
    
    #app_toolbar{
    height: 40px !important;
    }
    
    @if(!empty($iframe))
    #app_toolbar{
    display:none !important;
    }
    @endif
    
    .pbx_admin_menu.e-menu-popup .e-menu-item{
    font-size: 12px;
    }
    .related_items_menumenu.e-menu-popup .e-menu-item{
    font-size: 12px;
    }

   
    #gridheadertoolbar{{ $grid_id }} .k-button.customer-access{
    background-color: #f7f7f7 !important;        
    }
    #navbar_header .e-toolbar-items{
        border-radius: 1rem;
    }
    
body, html {
    margin: 0;
    padding: 0;
    height: 100%;
}

.main-div {
    display: flex;
    flex-direction: column;
    height: 100vh;
}

#nav-container {
    /* Other styles */
    flex: 0 0 auto; /* Let it grow and shrink based on content */
    overflow-y: auto; /* Add scrollbar if content exceeds height */
}

#main-container {
    /* Other styles */
    flex: 1 1 auto; /* Fill remaining space */
    overflow-y: auto; /* Add scrollbar if content exceeds height */
    display: flex; /* Added */
    flex-direction: column; /* Added */
}
  
    </style>
    
    
    
    <script>
    function ajax_refresh_color_scheme(){
    $.get('refresh_color_scheme/{{$grid_id}}',function(data){
    ////console.log('ajax_refresh_color_scheme/{{$grid_id}}',data);
    $("#erp_color_scheme").html(data);
    }) 
    }
    
    </script>
    
    
    <!--<script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.3.0/dist/turbo.es2017-umd.js"></script>
    <!--<script defer src="https://cdn.jsdelivr.net/gh/livewire/turbolinks@v0.1.x/dist/livewire-turbolinks.js" data-turbolinks-eval="false" data-turbo-eval="false"></script>-->

    <!-- APP SCRIPTS END-->
    
    @stack('sidebar-scripts')
    @stack('page-scripts')
    @stack('page-styles')
    @section('ajax-styles')
    @section('ajax-scripts')
    
    @if(is_main_instance() && session('role_level')!='Admin' && session('partner_id') == 1)
    <!--<script src="https://helpdesk.cloudtelecoms.co.za/assets/chat/chat.min.js"></script>
    <script> $(function() { new ZammadChat({ background: '#e4e6e7', fontSize: '12px', chatId: 1 }); }); </script>-->
    @endif

    <style>
.zammad-chat {
  left: 30px;
  right: 0;
}

</style>
</body>

</html>