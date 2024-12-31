@extends( '__app.layouts.api' )


@section('content')

<div class="card mt-0">
    <div class="card-header pb-0">
        
        <div class="row">
            <div class="col"><h6>Subscriptions</h6></div>
        </div>
       
    </div>
    <div class="card-body" style="font-size:14px">
       
       <table class="table">
           <thead><tr><th  width="35%">Product</th><th width="35%">Detail</th><th width="30%" class="text-right">Status</th></tr></thead>
           <tbody>
               @foreach($subscriptions as $sub)
               <tr><td>{{ ucwords(str_replace('_',' ',$sub->code)) }}</td><td>{{ $sub->detail }}</td><td class="text-right"> {{ $sub->status }} </td></tr>
               @endforeach
           </tbody>
       </table>
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