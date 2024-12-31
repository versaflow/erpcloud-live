<!doctype html >
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="twocolumn" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="enable">

<head>
    <meta charset="utf-8" />
    <title>@yield('title')| Velzon - Admin & Dashboard Template</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesbrand" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ public_path().('/assets/velzon/images/favicon.ico')}}">
    @include('velzon.layouts.head-css')
    @include('velzon.layouts.head-scripts')
    
</head>

@section('body')
    @include('velzon.layouts.body')
@show
    <!-- Begin page -->
    <div id="layout-wrapper">
        @include('velzon.layouts.topbar')
        @include('velzon.layouts.sidebar')
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            @include('velzon.layouts.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

   


<!--preloader-->
<div id="preloader">
    <div id="status">
        <div class="spinner-border text-primary avatar-sm" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

    <!-- JAVASCRIPT -->
    @include('velzon.layouts.vendor-scripts')
    @yield('page-script')
</body>

</html>
