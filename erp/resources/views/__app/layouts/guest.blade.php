<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ (!empty($menu_name))?$menu_name:'CloudTools' }}</title>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge"> 
    <meta name="description" content="CloudTools" />
    <meta name="author" content="CloudTools" />
    <link rel="shortcut icon" href="{{ url('favicon.ico') }}" type="image/x-icon">

    {{-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries --}}
    <!--[if lt IE 9]>
        <script src="{{ '/assets/libraries/html5shiv/html5shiv.min.js' }}"></script>
        <script src="{{ '/assets/libraries/html5shiv/respond.min.js' }}"></script>
    <![endif]-->

@include('__app.layouts.assets')  
<?php
    $partner_settings = dbgetaccount(1);
    $default_logo = '';

    if ($partner_settings->whitelabel_domain != $_SERVER['HTTP_HOST']) {
        unset($partner_settings);
    }
?>
  
</head>
	
<body>
    <div id="container">
    @include('__app.components.dialog')
    @yield('dialog')
    <div id="auth" class="d-flex">
        <div id="content" class="justify-content-center align-self-center" style="width: 100%;">
            <div class="container ">
                <div class="row  justify-content-center align-items-center m-0 p-0">
                    <div  class="col-12 m-0 mt-4 p-0">
                        <!--
                        @if(!empty($partner_settings->logo) && file_exists(uploads_settings_path().$partner_settings->logo))
                        <div class="col-12 pt-1 text-center">
                            <a href="{{ url('/') }}" id="branding-logo"><img class="img-fluid" title="Logo" src="{{ settings_url().$partner_settings->logo }}" id="logo-img"/></a>
                        </div>
                        @elseif(!empty($reseller) && !empty($reseller->company))
                        <div class="col-12 pt-1 text-center">
                            <h3>{{ $reseller->company }}</h3>
                        </div>
                        @else
                        <div class="col-12 pt-1 text-center">
                            <a href="{{ url('/') }}" id="branding-logo"><img class="img-fluid" title="Logo" src="{{ settings_url().$default_logo }}" id="logo-img" /></a>
                        </div>
                        @endif
                        -->
                        @yield('content')
                </div>
            </div>
        </div>
    </div>	
    </div>	
</body>
  
    @yield('styles')
    @yield('scripts')
    @stack('page-scripts')
    @stack('page-styles')
<style>
#page-wrapper{
		background-color: #fff;
		padding: 0;
		margin:0;
}
body{
    background-image:none !important;
    background-color:#efefef !important;
}
.e-tab .e-tab-header .e-toolbar-item.e-active .e-tab-text, .e-tab .e-tab-header .e-toolbar-item.e-active .e-tab-icon {
    color: #00afef;
}

.e-tab .e-tab-header .e-toolbar-item:hover .e-tab-text, .e-tab .e-tab-header .e-toolbar-item:hover .e-tab-icon {
    color: #00afef !important;
}

.e-tab .e-tab-header .e-indicator {
    background: #00afef;
}
   
.e-tab .e-content {
     margin-top: -13px;
}


</style>

</html>