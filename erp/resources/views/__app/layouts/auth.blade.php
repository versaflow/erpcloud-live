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
  @stack('page-styles')
</head>

<body class="bg-gray-100">
  
@include('__app.components.dialog') 
@yield('dialog')
   <div id="body_container">   
@include('__app.layouts.alerts') 
  <!-- Navbar -->
 
  <!-- End Navbar -->
  <main class="main-content  mt-0">
    <div class="page-header align-items-start min-vh-40 pt-5 pb-11 m-3 mb-0 mt-0 border-radius-lg" style="background-image: url('/softui/assets/img/curved-images/curved-blue.jpg');">
      <span class="mask bg-gradient-dark opacity-6"></span>
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-5 text-center mx-auto">
            @if($logo)
                <div class="col-12 pt-1 text-center">
                   
                    <a href="{{ url('/') }}" id="branding-logo"><img class="img-fluid" title="Logo" src="{{ settings_url().$logo }}" id="logo-img" width="360px"/></a>
              
                </div>
                @endif
          
          </div>
        </div>
      </div>
    </div>
    @yield('content')
  </main>
  
  <!-- -------- START FOOTER 3 w/ COMPANY DESCRIPTION WITH LINKS & SOCIAL ICONS & COPYRIGHT ------- -->
  <!--<footer class="footer py-5">
    <div class="container">
    
      <div class="row">
        <div class="col-8 mx-auto text-center mt-1">
          <p class="mb-0 text-secondary">
            Copyright © <script>
              document.write(new Date().getFullYear())
            </script> Versaflow.
          </p>
        </div>
      </div>
    </div>
  </footer>-->
  
    @stack('footer_assets')
    <!-- APP SCRIPTS BEGIN-->
   </div> 
    @stack('page-scripts')
  
</body>

</html>