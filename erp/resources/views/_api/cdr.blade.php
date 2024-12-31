@extends( '__app.layouts.api' )


@section('content')

<div class="card m-mt-0 mt-0">
    <div class="card-header pb-0">
        
        <div class="row">
            <div class="col"><h6>Call Records</h6></div>
        </div>
       
    </div>
    <div class="card-body" style="font-size:14px">
       
        <div><a class="e-btn e-primary mt-2" href="{{ $export_url }}">Download CDR</a></div>
    </div>
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