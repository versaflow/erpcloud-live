@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')
@php

define('SB_PATH', '/home//versaflo/erp.versaflow.io/html/helpdesk');

require('/home/erpcloud-live/htdocs/html/helpdesk/admin.php');

//$active_user = sb_get_active_user();

@endphp
@endsection
@push('page-scripts')

<script type="text/javascript">
    @if(!empty($email) && !empty($password))
    $(document).ready(function(){
    
    
      if(!SB_ACTIVE_AGENT || $.inArray(SB_ACTIVE_AGENT.user_type,['agent','admin']) === -1 || SB_ACTIVE_AGENT.email != '{{$email}}'){
        //console.log('sbf login {{$email}} {{$password}}' );
        //console.log(window['SBF'].activeUser() );
        
        //console.log('login');
        window['SBF'].login('{{$email}}', '{{$password}}', "", "", () => {  location.reload(); });
      }
    });
   
    @endif
    
    
</script>
@endpush
@push('page-styles')

<style>


.sb-main, .sb-articles-page,
.sb-main input,
.sb-main textarea,
.sb-main select { 
    font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
}

.sb-admin,
.sb-admin input,
.sb-admin textarea,
.sb-admin select,
.sb-title,
.daterangepicker,
.ct__content {
   font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
}
   
.sb-header,.sb-main{
 top: 65px !important;   
}

.sb-header, .sb-rich-login{
 display: none !important;   
}
#envato-purchase-code, .sb-nav #tab-dialogflow,.sb-nav #tab-twitter,.sb-nav #tab-apps,.sb-nav #tab-gbm,.sb-nav #tab-telegram{
 display: none !important;   
    
}
.sb-main{
 width: 100% !important;    
 padding-left: 0px !important;   
}
</style>
@endpush