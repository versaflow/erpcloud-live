
@yield('styles')

<script
src="https://code.jquery.com/jquery-3.6.0.min.js"
integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
crossorigin="anonymous"></script>

<link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/19.2.56/material.css" />
<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Titillium+Web:wght@800&display=swap" rel="stylesheet">
 
<link href="https://cdn.jsdelivr.net/npm/busy-load/dist/app.min.css" rel="stylesheet">   
<script src="https://cdn.jsdelivr.net/npm/busy-load/dist/app.min.js"></script>
<!-- Custom CSS -->
<link href="{{ '/assets/_app/static_app.css'.'?v='.date('ymd') }}" rel="stylesheet">
<link href="{{ '/assets/_app/main.css'.'?v='.date('ymd') }}" rel="stylesheet">
<link href="{{ '/assets/_app/header.css'.'?v='.date('ymd') }}" rel="stylesheet">
<link href="{{ '/assets/_app/sidebar.css'.'?v='.date('ymd') }}" rel="stylesheet">
@if(is_dev())
<link href="{{ '/assets/_app/dev.css' }}" rel="stylesheet">
@endif

<link href="https://kendo.cdn.telerik.com/2021.2.616/styles/kendo.common.min.css" rel="stylesheet" />
<link href="https://kendo.cdn.telerik.com/2021.2.616/styles/kendo.default.min.css" rel="stylesheet" />


<link href="{{ '/assets/_app/kui.css' }}" rel="stylesheet">
<link href="{{ '/assets/_app/grid.css' }}" rel="stylesheet">
<link href="{{ '/assets/_app/calendar.css' }}" rel="stylesheet">



<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" integrity="sha512-1PKOgIY59xJ8Co8+NE6FZ+LOAZKjy+KY8iq0G4B3CyeY6wYHN3yt9PW0XpSriVlkMXe40PTKnXrLnZ9+fkDaog==" crossorigin="anonymous" />

<script src="https://cdn.syncfusion.com/ej2/19.2.56/dist/ej2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

<script>

    function showSpinner(){
        
        if ($(".e-dialog:visible")[0]){
            var spinnerel;
            var maxz; 
            $('.e-dialog:visible').each(function(){
                var z = parseInt($(this).css('z-index'), 10);
                if (!spinnerel || maxz<z) {
                spinnerel = this;
                maxz = z;
                }
            });
            $(spinnerel).busyLoad("show", {
                animation: "slide"
            });
        }else{
            $("html").busyLoad("show", {
                animation: "slide"
            });
        }
    }
    
    function hideSpinner(){
        if ($(".e-dialog:visible")[0]){
            var spinnerel;
            var maxz; 
            $('.e-dialog:visible').each(function(){
                var z = parseInt($(this).css('z-index'), 10);
                if (!spinnerel || maxz<z) {
                spinnerel = this;
                maxz = z;
                }
            });
        
            $(spinnerel).busyLoad("hide", {
                animation: "slide"
            });
        }else{
            $("html").busyLoad("hide", {
                animation: "slide"
            });
        }
    }

</script>
<style>
body{
    font-family: "Titillium Web", "Roboto", "-apple-system", "BlinkMacSystemFont" !important;
}
.e-toast{
    z-index: 5000 !important;
}
#user_dropdown-popup.e-dropdown-popup ul .e-item .e-menu-url {
     padding: 0;
}

.cl_primary {color: #2196F3 !important;}
.cl_primary_bg {background-color: #2196F3 !important;}

.cl_primarydark {color: #1976D2 !important;}
.cl_primarydark_bg {background-color: #1976D2 !important;}

.cl_accent {color: #0099CC !important;}
.cl_accent_bg {background-color: #0099CC !important;}

.cl_lightgray {color: #BEBEBE !important;}
.cl_lightgray_bg {background-color: #BEBEBE !important;}

.cl_gray {color: #515151 !important;}
.cl_gray_bg {background-color: #515151 !important;}

.cl_white {color: #ffffff !important;}
.cl_white_bg {background-color: #ffffff !important;}

.cl_custom1 {color: #C52800 !important;}
.cl_custom1_bg {background-color: #C52800 !important;}
.cl_custom2 {color: #9FC55C !important;}
.cl_custom2_bg {background-color: #9FC55 !important;}
.cl_custom3 {color: #3f91c7 !important;}
.cl_custom3_bg {background-color: #3f91c7 !important;}

</style>