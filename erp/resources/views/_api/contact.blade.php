@extends( '__app.layouts.api' )


@section('content')

<div class="card m-mt-0 mt-0">
    <div class="card-header pb-2">
        <div class="row">
            <div class="col"><h6>Contact Information</h6></div>
        </div>
    </div>
    <div class="card-body" style="font-size:14px">
       <p><b>Company: </b>{{$reseller->company}}</p>
       <p><b>Telephone: </b>{{$reseller->phone}}</p>
       <p><b>Email: </b>{{$reseller->email}}</p>
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