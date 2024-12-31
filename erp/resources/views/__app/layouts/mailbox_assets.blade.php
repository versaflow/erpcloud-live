@if(empty(request()->pivot_export))
    <!-- EJ 2  theme reference -->
    
    <!--<link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/material.min.css" /> -->
     
    <link href="{{ '/assets/crg.syncfusion/styles/material.css' }}" rel="stylesheet"> 
    <!-- CSS Frameworks -->
   
  
    
    <!--<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" type="text/css" />-->
      <link rel="stylesheet" href="/assets/libaries/bootstrap/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" integrity="sha512-1PKOgIY59xJ8Co8+NE6FZ+LOAZKjy+KY8iq0G4B3CyeY6wYHN3yt9PW0XpSriVlkMXe40PTKnXrLnZ9+fkDaog==" crossorigin="anonymous" />
  
   
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Titillium+Web:wght@800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Quicksand&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Alfa+Slab+One&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/busy-load/dist/app.min.css" rel="stylesheet">  
    <!-- Custom CSS -->
    <link href="{{ '/assets/static_app.css' }}" rel="stylesheet">
      <link href="{{ '/assets/libraries/smartwizard/dist/css/smart_wizard.css' }}" rel="stylesheet"> 
    <link href="{{ '/assets/libraries/smartwizard/dist/css/smart_wizard_theme_arrows.css' }}" rel="stylesheet">
    
    <script type='text/javascript' src='https://cdn.yodlee.com/fastlink/v4/initialize.js'></script>

@endif
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
@if(empty(request()->pivot_export)) 
    <!-- EJ 2  script references -->
    <!-- https://crg.syncfusion.com/ -->
    <script src="https://cdn.syncfusion.com/ej2/dist/ej2.min.js"></script>
     <!--<script src="https://cdn.syncfusion.com/ej2/18.4.30/dist/ej2.min.js"></script>-->
    <!--<script src="{{ '/assets/crg.syncfusion/scripts/ej2.js' }}"></script>  -->
    <!--<script src="https://cdn.syncfusion.com/ej2/17.4.46/dist/ej2.min.js"></script> -->
    <!-- v17.4.47 checkbox bug https://www.syncfusion.com/forums/151281/excel-like-filter-on-grid-in-ver-v17-4-47-not-working-any-more -->
 
    <!--<script src="{{ '/assets/libraries/syncfusion-query/ej2query.js' }}" ></script>-->
 
  
   
    <!-- local syncfusion js -->
    <!-- <script src="{{ '/assets/syncfusion/ej2.min.js' }}"></script>  -->
    <!-- JS Plugins-->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous"></script>
   
    
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@2.3.2/dist/signature_pad.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.6.2/tinymce.min.js" integrity="sha512-sOO7yng64iQzv/uLE8sCEhca7yet+D6vPGDEdXCqit1elBUAJD1jYIYqz0ov9HMd/k30e4UVFAovmSG92E995A==" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js" integrity="sha512-GZ1RIgZaSc8rnco/8CXfRdCpDxRCphenIiZ2ztLy3XQfCbQUSCuk8IudvNHxkRA3oUg6q0qejgN/qqyG1duv5Q==" crossorigin="anonymous"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/busy-load/dist/app.min.js"></script>
    
    <script src="https://unpkg.com/jquery-mousewheel@3.1.3/jquery.mousewheel.js"></script>
    <!-- Custom JS -->
    <script src="{{ '/assets/staic_app.js' }}"></script>
    <script src="{{ '/assets/libraries/smartwizard/dist/js/jquery.smartWizard.js' }}"></script>
    <script src="{{ '/assets/libraries/audiojs/audiojs/audio.min.js' }}"></script>
    
    <script src="{{ '/assets/flexmonster/flexmonster.js' }}" ></script>
     <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" ></script>
    
    <script>
    audiojs.events.ready(function() {
    var as = audiojs.createAll();
    });
    </script>
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

    function showSpinnerWindow(){
        $("html").busyLoad("show", {
            animation: "slide"
        });
    }
    
    function hideSpinnerWindow(){
        $("html").busyLoad("hide", {
            animation: "slide"
        });
    }
    
    function validate_session(){
        $.get('validate_session', function(data) {
            if(data == 'logout'){
                window.location.href = '{{ url("/") }}';
            }
        });
    }
</script>

<style>


.dlg-no-header .e-dlg-header-content{
    display:none !important;
}

.e-toast{
    z-index: 5000 !important;
}
#user_dropdown-popup.e-dropdown-popup ul .e-item .e-menu-url {
     padding: 0;
}
.sf-menu-icon{
    color: #ccc;
    font-size: 11px;
}
#pricelist_dropdown_popup .e-dropdownbase .e-list-item {
   
    line-height: 24px;
    min-height: 24px;
}
.e-dialog .e-footer-content .e-btn {
    text-transform: uppercase !important;
    font-weight: 600;
}
.form-group .e-error {
    text-align: right !important;
}
#logobtn{
    background-color:#fff;
    border-radius:0px;
    padding:3px 10px;
}
#maintoolbar .e-menu-wrapper:not(.e-hamburger):not(.e-menu-popup),#maintoolbar .e-menu-wrapper:not(.e-hamburger):not(.e-menu-popup) .e-menu {
    height: 50px;
    min-height: 50px;
}
#maintoolbar .e-menu-wrapper:not(.e-hamburger):not(.e-menu-popup) ul .e-menu-item {
    height: 50px;
    line-height: 50px;
}
#maintoolbar .e-menu-wrapper:not(.e-hamburger):not(.e-menu-popup) ul .e-menu-item .e-caret {
    height: 50px;
    line-height: 50px;
}



</style>
@endif