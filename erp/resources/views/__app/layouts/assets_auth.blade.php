    <!-- EJ 2  theme reference -->
  
    <!-- <link rel="stylesheet" rel='nofollow' href="https://cdn.syncfusion.com/ej2/material.css" /> 
       -->
    <link href="https://cdn.syncfusion.com/ej2/material.min.css" rel="stylesheet"> 
    <!-- CSS Frameworks --> 
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" integrity="sha512-1PKOgIY59xJ8Co8+NE6FZ+LOAZKjy+KY8iq0G4B3CyeY6wYHN3yt9PW0XpSriVlkMXe40PTKnXrLnZ9+fkDaog==" crossorigin="anonymous" />
  
    <!-- Custom CSS -->
    <link href="{{ '/assets/app.min.css' }}" rel="stylesheet">
    @yield('styles')
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    
    <!-- EJ 2  script references -->
    <!-- https://crg.syncfusion.com/ -->
    <!--<script src="https://cdn.syncfusion.com/ej2/17.4.46/dist/ej2.min.js"></script> -->
    <!-- v17.4.47 checkbox bug https://www.syncfusion.com/forums/151281/excel-like-filter-on-grid-in-ver-v17-4-47-not-working-any-more -->
    <!--<script src="https://cdn.syncfusion.com/ej2/dist/ej2.min.js"></script> -->
    <!-- local syncfusion js -->
    <!-- <script src="{{ '/assets/syncfusion/ej2.min.js' }}"></script>  -->
    <!-- JS Plugins-->

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous"></script>
 
    <!-- Custom JS -->
    <script src="{{ '/assets/app.min.js' }}"></script>
 
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
.e-toast{
    z-index: 5000 !important;
}
#user_dropdown-popup.e-dropdown-popup ul .e-item .e-menu-url {
     padding: 0;
}
</style>
<style>
    @font-face {
        font-family: "AnavioSmallCapitalsW01-Bold"; 
        src: url("{{ public_path().'/assets/fonts/Anavio Small Capitals W01 Bold.ttf' }}"); 
    }
    .anavio-title{
        font-size: 36px;
        font-family: "AnavioSmallCapitalsW01-Bold", Arial, sans-serif;
    }
</style>