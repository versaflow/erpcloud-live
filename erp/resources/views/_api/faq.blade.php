@extends( '__app.layouts.api' )
@section('content')

<div class="card m-mt-0 mt-0">
   <iframe src="http://cloudtelecoms.tawk.help/" width="100%" frameborder="0px" height="800px" onerror="alert('Failed')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> 
</div>

@endsection



@push('page-styles')

<style>
.e-dialog .e-dlg-content {
    padding: 18px !important;    
    padding-top: 0px !important;
}
</style>
@endpush