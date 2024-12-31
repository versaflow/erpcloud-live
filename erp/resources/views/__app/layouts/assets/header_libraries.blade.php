
@php
$sf_version = 23; 

$sf_version = 26; 
@endphp

@if($sf_version == 26)
<link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/26.1.35/bootstrap5.css" />
@elseif($sf_version == 23)
<link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/23.1.36/bootstrap5.css" />
@else
<link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/20.4.49/bootstrap5.css" />

@endif

<link href="{{ '/assets/libraries/busy-load/app.min.css' }}" rel="stylesheet"> 

<!-- <script src="https://cdn.form.io/formiojs/formio.min.js"></script> -->
<!-- <script src="/assets/main/formio-bootstrap4.css"></script> -->

<link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">

<link href="{{ '/assets/libraries/smartwizard/dist/css/smart_wizard.css' }}" rel="stylesheet"> 
<link href="{{ '/assets/libraries/smartwizard/dist/css/smart_wizard_theme_arrows.css' }}" rel="stylesheet">
<style type="text/css">
#navbar_header .e-menu-icon{
font-size: 14px !important;
margin-right: 0px;
line-height: 28px !important;
}
.e-acrdn-header-content .flagged{
    color: red;
    font-weight: bold;
}
</style>