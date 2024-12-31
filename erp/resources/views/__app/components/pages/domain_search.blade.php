<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ (!empty($menu_name))?$menu_name:'CloudTools' }}</title>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge"> 
    <meta name="description" content="CloudTools" />
    <meta name="author" content="CloudTools" />
    <link rel="shortcut icon" href="{{ url('favicon.ico') }}" type="image/x-icon">
    <script src="https://{{ session('instance')->domain_name }}/assets/iframeresizer/iframeResizer.contentWindow.js"></script>

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
	
<body class="" style="background-color:transparent">
    <div id="container" style="padding-bottom:50px">
    @yield('dialog')
    <div id="auth" class="d-flex">
        <div id="content" class="justify-content-center align-self-center" style="width: 100%;">
            <div class="container ">
                <div class="row  justify-content-center align-items-center">
                    <div id="page-wrapper" class="col-12 p-3">
 <div class="container" >
  <form class="form-inline justify-content-center" id="domain_search_form" style="margin-bottom:50px">
    <input type="text" name="domain_name" id="domain_name"  placeholder="domain name"/>

    <select id="tld" name="tld" >
        @foreach($tlds as $tld)
        <option>{{ $tld }}</option>
        @endforeach
    </select>

    <button id="domain_search" type="submit">Search</button>
    </form>
    
    <div id="response" class="mt-2">
        
    </div>
</div>
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
    <script>
     $(document).ready(function() {
  domain_name = new ej.inputs.TextBox({
  placeholder: "Domain Name",
  });
  domain_name.appendTo('#domain_name');
  
  
  tld_select = new ej.dropdowns.DropDownList({
     allowFiltering: true,
      placeholder: 'Select a tld',
  });
  tld_select.appendTo('#tld');
  
  
  domain_search = new ej.buttons.Button({ cssClass: `e-primary`}, '#domain_search');
     });
     
$('#domain_search_form').on('submit', function(e) {
	e.preventDefault();
   
	var form = $('#domain_search_form');
	
	var formData = new FormData(form[0]);

    $.ajax({
		method: "post", 
		url: '/domain_search',
		data: formData,
		contentType: false,
		processData: false,
        success: function(data){
            var alert_html = '<div class="alert alert-'+data.status+'" role="alert">'+data.message+' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';

            $("#response").html(alert_html);
        },
		error: function(jqXHR, textStatus, errorThrown) {
		},
    });
        
});
</script>
<style>
body{
  background: transparent !important;
}
</style>
<style>
#page-wrapper{
		background-color: #fff;
		padding: 2%;
		margin-top:3%;
		margin-bottom:3%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
}
</style>

</html>








