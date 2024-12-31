@php
$token_reload = false;
if(request()->root() =='http://reports.turnkeyerp.io' && !empty(request()->token)) {
$utoken = \Erp::decode(request()->token);
$connection = $utoken['token'];
$hostname = request()->root();
if($connection != session('instance')->db_connection){
$token_reload = true;
}
}
if($token_reload){
echo 'Reloading...';
header("Refresh:0");
}else{
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ (!empty($menu_name))?$menu_name:'Power ERP' }}</title>
   
    <meta charset="utf-8" />
    <meta name="description" content="CloudTools" />
    <meta name="author" content="CloudTools" /> 
    <link rel="shortcut icon" href="{{ (!empty($favicon)) ? $favicon : session('favicon') }}" type="image/x-icon">
    
    @include('__app.layouts.mailbox_assets')
    <script type="text/javascript" >
   
    @if (request()->root() == 'http://reports.turnkeyerp.io' && empty(session('instance')))
    location.reload();
    @endif 
    </script>
    @yield('styles')
    @yield('scripts')


    {{-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries --}}
    <!--[if lt IE 9]>
        <script src="{{ public_path().('/assets/libraries/html5shiv/html5shiv.min.js') }}"></script>
        <script src="{{ public_path().('/assets/libraries/html5shiv/respond.min.js') }}"></script>
    <![endif]-->
    <!-- https://flatuicolors.com/palette/us chart colors-->
    <!--Extending the scripts -->    
   
    <script>
    function menu_delete(menu_id){
        gridAjaxConfirm('/menu_manager/delete', 'Delete record?', {"id" : menu_id}, 'post');
    }  
    function menu_edit(menu_id){
        sidebarform('MenuEdit' , '/menu_manager/edit/'+menu_id, 'Menu Edit', '70%',  'auto');
    }  
    function menu_permissions(menu_id){
        sidebarform('MenuPermissions' , '/menu_permissions/'+menu_id, 'Menu Permissions', '70%',  'auto');
    }  
    
    function report_server_restart(){
    
        $.ajax({
            url: '/report_server_restart',
            beforeSend: function(){
                showSpinner();  
            },
            type: 'post',
            success: function(data){
                hideSpinner();  
                location.reload();
            
            }
        });
        
    }
    </script>
</head>
@php
$account = dbgetaccount(session('account_id'));
@endphp
<body>
@include('__app.layouts.alerts') 
    <div id="container">
        @yield('dialog')
        @if(empty($button_iframe))
        @yield('header')
        @endif
        <div id="content">
        @yield('content')
        </div>
    </div>
</body>
@yield('page-scripts')
@yield('page-styles')

<script>
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
        session_role_id = '{{ session("role_id") }}';
    @endif
    
</script>


@if(session('role_id') > 11 && session("parent_id") == 1 && is_main_instance() && \Route::currentRouteName() != 'dashboard' )
<script type="text/javascript">
var $zoho=$zoho || {};$zoho.salesiq = $zoho.salesiq || {widgetcode:"1cf7281c55cd609b181eab828e0b70ecb8311879901983d2eff585291f97dcd7cafb97bc75f389fbc95cf1d871b6190d", values:{},ready:function(){}};var d=document;s=d.createElement("script");s.type="text/javascript";s.id="zsiqscript";s.defer=true;s.src="https://salesiq.zoho.com/widget";t=d.getElementsByTagName("script")[0];t.parentNode.insertBefore(s,t);d.write("<div id='zsiqwidget'></div>");
</script>
@endif 
</html>
@php } @endphp