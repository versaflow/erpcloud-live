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
@if(!empty($load_syncfusion))
@include('__app.layouts.assets')  
@else
@include('__app.layouts.assets_api')  
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

</head>
@if(!empty($fullwidth_body))
<body class="w-100 p-0 m-0" style="background-color: #000;">
@else
<body class="p-0" style="background-color: #000;">
@endif
    <div id="container" >
    @yield('dialog')
    <div id="auth" class="d-flex">
        <div id="content" class="justify-content-center align-self-center p-0" style="width: 100%;">
            <div class="container-fluid p-0">
                <div class="justify-content-center align-items-center">
                    <div id="page-wrapper" class="col-12 p-0 m-0 m-md-0 ">
                        @if(!empty($logo))
                        <div class="col-12 text-center">
                            <a href="{{ url('/') }}" id="branding-logo"><img class="img-fluid" title="Logo" src="{{ settings_url().$logo }}" id="logo-img"/></a>
                        </div>
                        @endif
                        @yield('content')
                </div>
            </div>
        </div>
    </div>	
    </div>	
</body>
  
    @yield('styles')
    @yield('scripts')
    @yield('page-scripts')
    @yield('page-styles')
<style>
#page-wrapper{
	background-color: #fff;
	box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
}
</style>

</html>