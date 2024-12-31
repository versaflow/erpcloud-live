@extends( '__app.layouts.api' )


@section('content')

<div class="card m-mt-0 mt-0">
    <div class="card-header pb-0">
        
        <div class="row">
            <div class="col"><h6>Statement</h6></div>
        </div>
       
    </div>
    <div class="card-body" style="font-size:14px">
        <div class="text-right"><a class="e-btn e-secondary mt-2" href="{{ url('statement_download/'.$account_id) }}">Download Statement</a></div>
       {!! $statement !!}
    </div>
</div>

@endsection



@push('page-styles')

<style>
.e-dialog .e-dlg-content {
    padding: 18px !important;    
    padding-top: 0px !important;
}
.spacing{
    height:20px;
}
</style>
@endpush