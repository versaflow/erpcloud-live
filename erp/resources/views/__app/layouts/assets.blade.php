@php
// theme colors
// https://ej2.syncfusion.com/javascript/documentation/appearance/theme/#microsoft-offices-fabric

@endphp
@if(empty(request()->pivot_export))
    <!-- EJ 2  theme reference -->
    <!-- test stable 19.1.54  19.3.56 19.4.42-->
  
    @if(!empty($tailwind_css))
 
    <link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/20.4.49/tailwind.css" />
    @elseif(!empty($material_css))
   
    <link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/20.4.49/material.css" />
    @else
      @if(is_dev() && 1==0)
    <link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/22.1.34/fabric.css" />
      @else
    <link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/20.4.49/fabric.css" />
  @endif
    <!--<link href="{{ '/assets/libraries/syncfusion/19.3.56/fabric.css' }}" rel="stylesheet"> -->
    
    @endif

    
<link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

  

    <!--<link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/18.4.30/fabric.css" /> -->
    <!--<link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/material.min.css" />
    <link href="{{ '/assets/crg.syncfusion/styles/material.css' }}" rel="stylesheet"> 
    <link href="{{ '/assets/syncfusion/themestudio/material.css' }}" rel="stylesheet">-->
    <!-- CSS Frameworks -->

  
    <!-- CSS only -->
 
    <link rel="stylesheet" href="/assets/libaries/bootstrap/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
 
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lato&display=swap" rel="stylesheet">
    
    <link href="{{ '/assets/_app/sf_font.css'.'?v='.date('ymd') }}" rel="stylesheet">

    <style>
        input.e-input,textarea.e-input,.e-input-group,.e-input-group.e-control-wrapper,.e-input-group.e-disabled,.e-input-group.e-control-wrapper.e-disabled {
          
           font-family: "Roboto","Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji"
        }
        html, body, .e-control, .e-css, .ag-theme-alpine,.e-tab .e-tab-header .e-toolbar-item .e-tab-text  {
          /*  font-family: "San Francisco", Arial, Sans-serif !important; */
           
           font-family: "Roboto","Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji" 
        }
        .e-tab .e-tab-header .e-toolbar-item.e-active .e-tab-text {
        font-weight: 400;
        }
        
        .e-tab .e-tab-header .e-toolbar-item .e-tab-text {
        font-size: 13px;
        }
        .fa, .far, .fas {
            font-family: "Font Awesome 5 Free"  !important;
        }
        .h-100{
        height:100%;    
        }
        
        .custom-date-filter a {
  position: absolute;
  right: 20px;
  color: rgba(0, 0, 0, 0.54);
  cursor: pointer;
}
.custom-date-filter:after {
  position: absolute;
  content: '\f073';
  display: block;
  font-weight: 400;
  font-family: 'Font Awesome 5 Free';
  right: 5px;
  pointer-events: none;
  color: rgba(0, 0, 0, 0.54);
}
.left-dlg-btn{
float:left;    
}
    </style>
    
    <link href="{{ '/assets/libraries/busy-load/app.min.css' }}" rel="stylesheet"> 
    <!-- Custom CSS -->
    <link href="{{ '/assets/_app/static_app.css'.'?v=1'.date('ymd') }}" rel="stylesheet">
    <link href="{{ '/assets/_app/main.css'.'?v=2'.date('ymd') }}" rel="stylesheet">
    <link href="{{ '/assets/_app/header.css'.'?v=2'.date('ymd') }}" rel="stylesheet">
    <link href="{{ '/assets/_app/sidebar.css'.'?v=2'.date('ymd') }}" rel="stylesheet">
    <link href="{{ '/assets/_app/mobile.css'.'?v='.date('ymd') }}" rel="stylesheet">
    @if(is_dev())
    <link href="{{ '/assets/_app/dev.css' }}" rel="stylesheet">
    @endif
  
    <!--<link href="{{ '/assets/libraries/kendo/kendo.common.min.css' }}" rel="stylesheet">
    <link href="{{ '/assets/libraries/kendo/kendo.default.min.css' }}" rel="stylesheet">-->
    
    <link href="https://kendo.cdn.telerik.com/2021.2.616/styles/kendo.common.min.css" rel="stylesheet" />
    <link href="https://kendo.cdn.telerik.com/2021.2.616/styles/kendo.default.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/@fonticonpicker/fonticonpicker@3.1.1/dist/css/base/jquery.fonticonpicker.min.css">

    <link href="{{ '/assets/_app/kui.css' }}" rel="stylesheet">
    <link href="{{ '/assets/_app/grid.css' }}" rel="stylesheet">
    <link href="{{ '/assets/_app/calendar.css' }}" rel="stylesheet">
    <link href="{{ '/assets/libraries/smartwizard/dist/css/smart_wizard.css' }}" rel="stylesheet"> 
    <link href="{{ '/assets/libraries/smartwizard/dist/css/smart_wizard_theme_arrows.css' }}" rel="stylesheet">

    
    <script type='text/javascript' src='https://cdn.yodlee.com/fastlink/v4/initialize.js'></script>

@endif
    <!-- jQuery -->
    <script src="{{ '/assets/libraries/jquery/jquery-3.6.0.min.js' }}"></script>
@if(empty(request()->pivot_export)) 
    <!-- EJ 2  script references -->
    <!-- https://crg.syncfusion.com/ -->
    
    <!--<script src="https://cdn.syncfusion.com/ej2/dist/ej2.min.js"></script>-->
    
   
     <!--<script src="https://cdn.syncfusion.com/ej2/20.3.52/dist/ej2.min.js"></script>-->
    @if(is_dev() && 1==0)
    
    <script src="https://cdn.syncfusion.com/ej2/22.1.34/dist/ej2.min.js"></script>
      @else
    <script src="https://cdn.syncfusion.com/ej2/20.4.49/dist/ej2.min.js"></script>
  @endif
  
    @if(is_dev())
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <style>
    .tui{
        font-family:'Inter var';
    }
    </style>
    @endif
    <!--<script src="{{ '/assets/libraries/syncfusion/19.3.56/ej2.min.js' }}"></script>-->
 
    <script>
    var syncfusion_key = 'Mgo+DSMBaFt/QHRqVVhkX1pFdEBBXHxAd1p/VWJYdVt5flBPcDwsT3RfQF5jSH9RdkJgXXxecnBRQQ==;Mgo+DSMBPh8sVXJ0S0J+XE9AdVRDX3xKf0x/TGpQb19xflBPallYVBYiSV9jS31Td0RhWXhddHdVRGZfVg==;ORg4AjUWIQA/Gnt2VVhkQlFaclxJXGFWfVJpTGpQdk5xdV9DaVZUTWY/P1ZhSXxQdkRiW39Zc3BWRmJUUUM=;OTI3MTM4QDMyMzAyZTM0MmUzMEtmVWJ2TDM1UVZpalkvN2xoRVVqcjd1TjRaMWh1TnhJMzdoMzVnLzlza1U9;OTI3MTM5QDMyMzAyZTM0MmUzMGlsWTZXSTArbm1LMSs4M25NVE5mMWlLd2pIeVVJakp0WDkvU09kUnhPK0k9;NRAiBiAaIQQuGjN/V0Z+WE9EaFtBVmJLYVB3WmpQdldgdVRMZVVbQX9PIiBoS35RdUViWH1ed3dRRWBfWEBx;OTI3MTQxQDMyMzAyZTM0MmUzMGp3QTF5VCtOZUJBL0RlK29CWS8yNDlJRytESGI1eGhyTUtDSS9SajRIQ289;OTI3MTQyQDMyMzAyZTM0MmUzMEFlMTlabjNkaHREYkVOZTFtSGxGa0JUZEFYRE5ZKytpZGU3NDdVa3BhL0U9;Mgo+DSMBMAY9C3t2VVhkQlFaclxJXGFWfVJpTGpQdk5xdV9DaVZUTWY/P1ZhSXxQdkRiW39Zc3BWRmRVUkM=;OTI3MTQ0QDMyMzAyZTM0MmUzMElFUnBvQVpLZ3VMQTFXT1RYY1AvV3d2YjUrV1lrWXVwMnZFT2FFRjVqWFU9;OTI3MTQ1QDMyMzAyZTM0MmUzMEk0eWc5Q1NDTy9RQjlvZ29Rb2FaMDFyWDFsdGpta0hlZyt3bUlPVHYydWs9;OTI3MTQ2QDMyMzAyZTM0MmUzMGp3QTF5VCtOZUJBL0RlK29CWS8yNDlJRytESGI1eGhyTUtDSS9SajRIQ289';
    ej.base.registerLicense(syncfusion_key);
    </script>
     
    <!--<script src="https://cdn.syncfusion.com/ej2/18.4.30/dist/ej2.min.js"></script>-->
    <!--<script src="{{ '/assets/crg.syncfusion/scripts/ej2.js' }}"></script>  -->
    <!--<script src="https://cdn.syncfusion.com/ej2/17.4.46/dist/ej2.min.js"></script> -->
    <!-- v17.4.47 checkbox bug https://www.syncfusion.com/forums/151281/excel-like-filter-on-grid-in-ver-v17-4-47-not-working-any-more -->
 
    <!--<script src="{{ '/assets/libraries/syncfusion-query/ej2query.js' }}" ></script>-->
 
 
   
    <!-- local syncfusion js -->
    <!-- <script src="{{ '/assets/syncfusion/ej2.min.js' }}"></script>  -->
    <!-- JS Plugins-->
   <!-- JavaScript Bundle with Popper -->

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous"></script>

    <script src="{{ '/assets/libraries/signature_pad/signature_pad.min.js' }}"></script>
   
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.13.0/ace.js" integrity="sha512-btmS7t+mAyZXugYonCqUwCfOTw+8qUg9eO9AbFl5AT2zC1Q4we+KnCQAq2ZITQz1c9/axyUNYaNhGWqxfSpj7g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
 
    <script src="{{ '/assets/libraries/busy-load/app.min.js' }}"></script>

    <script src="https://cdn.tiny.cloud/1/r393xiac7oc37ggv1pogvslt7pmbnzxivf5ee5mkkxj7dfu7/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    
    
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise@28.1.1/dist/ag-grid-enterprise.min.js"></script>
   
    <!--<script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise@27.3.0/dist/ag-grid-enterprise.min.js"></script>-->
  
    



    <script>
        agGrid.LicenseManager.setLicenseKey("CompanyName=Cloud Telecoms,LicensedApplication=Turnkey ERP,LicenseType=SingleApplication,LicensedConcurrentDeveloperCount=1,LicensedProductionInstancesCount=0,AssetReference=AG-019616,ExpiryDate=28_September_2022_[v2]_MTY2NDMxOTYwMDAwMA==f5533e7bc5fcf06f9637e1a0a6a4543b");
    </script>
    <!-- Custom JS -->
    <script src="{{ '/assets/_app/static_app.js'.'?ver=1'.date('Ymd') }}"></script>
    <script src="{{ '/assets/libraries/smartwizard/dist/js/jquery.smartWizard.js' }}"></script>
    <script src="{{ '/assets/libraries/audiojs/audiojs/audio.min.js' }}"></script>
    <script src="{{ '/assets/libraries/jasonday/printThis.js') }}"></script>
    <script src="{{ '/assets/libraries/fonticonpicker/jquery.fonticonpicker.min.js' }}"></script>
   
    <link href="{{ '/assets/libraries/flatpickr/flatpickr.min.css' }}" rel="stylesheet"> 
    <link href="{{ '/assets/libraries/flatpickr/material_blue.css' }}" rel="stylesheet"> 
    <script src="{{ '/assets/libraries/flatpickr/flatpickr.min.js' }}"></script>
    
    <script src="{{ '/assets/flexmonster/flexmonster.js' }}" ></script>
    <script src="{{ '/assets/libraries/jqueryui/jquery-ui.min.js' }}"></script>
    <script src="{{ '/assets/libraries/moment/moment.js' }}"></script>
    <script type="text/javascript" src="{{ public_path().'/assets/formio/formio.full.min.js' }}"></script>
    <script type="text/javascript" src="{{ public_path().'/assets/formio/components/color.js' }}"></script>
    <script src="{{ '/assets/libraries/underscore/underscore-min.js' }}"></script>
    <script>
    audiojs.events.ready(function() {
    var as = audiojs.createAll();
    });
    </script>

    <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css" integrity="sha512-8bHTC73gkZ7rZ7vpqUQThUDhqcNFyYi2xgDgPDHc+GXVGHXq+xPjynxIopALmOPqzo9JZj0k6OqqewdGO3EsrQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />-->

    <!--<script src="{{ '/assets/formio/semantic.js' }}"></script>-->
   

<script>
   
    
    function showSpinner(reference = false){
        $(".sidebarbtn").attr("disabled","disabled");
      //  if(!reference && $('.sidebarformcontainer:visible:first').length > 0 ){
      //      reference  = "#"+$('.sidebarformcontainer:visible:first').attr('id');
      //  }
       
        if(reference){
            $(reference).busyLoad("show", {
                animation: "slide"
            });
        }else if ($(".e-dialog:visible")[0]){
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
           
            $(".gridcontainer").busyLoad("show", {
                animation: "slide"
            });
        }
    }
    
    function hideSpinner(reference = false){
        
                $(".sidebarbtn").removeAttr("disabled"); 
      // if(!reference && $('.sidebarformcontainer:visible:first').length > 0 ){
       //     reference  = "#"+$('.sidebarformcontainer:visible:first').attr('id');
      //  }
        if(reference){
            $(reference).busyLoad("hide", {
                animation: "slide"
            });
        }else if ($(".e-dialog:visible")[0]){
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
            $(".gridcontainer").busyLoad("hide", {
                animation: "slide"
            });
        }
    }

    function showSpinnerWindow(){
        try {
            
            $("html").busyLoad("show", {
                animation: "slide"
            });
        }
        catch (e) {}
    }
    
    function hideSpinnerWindow(){
        try {
            $("html").busyLoad("hide", {
                animation: "slide"
            });
        }
        catch (e) {}
    }
    
    function validate_session(){
        $.get('validate_session', function(data) {
            if(data == 'logout'){
                window.location.href = '{{ url("/") }}';
            }
        });
    }
    function isMobile() {
        try{ document.createEvent("TouchEvent"); return true; }
        catch(e){ return false; }
    }
</script>

@endif