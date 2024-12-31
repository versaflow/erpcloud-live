@extends( '__app.layouts.api' )

@section('content')

<div class="card m-mt-0 mt-0">
    <div class="card-header pb-0">
        
        <div class="row">
            <div class="col"><h6>Local Routes</h6></div>
        </div>
       
    </div>
    <div class="card-body" style="font-size:14px">
       
       <table class="table">
           <thead><tr><th  width="35%">Country</th><th width="35%">Network</th><th width="30%" class="text-right">Cost per minute</th></tr></thead>
           <tbody>
               @foreach($local_rates as $rate)
               <tr><td>{{ ucwords(str_replace('_',' ',$rate->country)) }}</td><td>{{ ucwords(str_replace('_',' ',$rate->destination)) }}</td><td class="text-right"> {{ currency($rate->rate,2) }} </td></tr>
               @endforeach
           </tbody>
       </table>
    </div>
</div>

<div class="card m-mt-0 mt-0">
    <div class="card-header pb-0">
        
        <div class="row">
            <div class="col"><h6>Top Routes</h6></div>
        </div>
       
    </div>
    <div class="card-body" style="font-size:14px">
       
       <table class="table">
           <thead><tr><th  width="35%">Country</th><th width="35%">Network</th><th width="30%" class="text-right">Cost per minute</th></tr></thead>
           <tbody>
               @foreach($top_rates as $rate)
               <tr><td>{{ ucwords(str_replace('_',' ',$rate->country)) }}</td><td>{{ ucwords(str_replace('_',' ',$rate->destination)) }}</td><td class="text-right"> {{ currency($rate->rate,2) }} </td></tr>
               @endforeach
           </tbody>
       </table>
    </div>
</div>

<div class="card m-mt-0 mt-0">
    <div class="card-header pb-0">
        
        <div class="row">
            <div class="col"><h6>Search International Rates</h6></div>
        </div>
       
    </div>
    <div class="card-body" style="font-size:14px">
        <!--<div><a class="btn btn-primary mt-2" href="{{ url('/download_international_rates_retail') }}">Download International Rates</a></div>-->
            <div class="input-group mb-3">
            <input class="form-control" id="rate_search" type="text" placeholder="Network or dial code"/>
            <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" id="rate_search_btn">Search</button>
            </div>
            </div>
            
            <br>
            <div id="rate_search_result"></div>
        </div>
</div>

@endsection



@push('page-styles')

<script>
    $(document).off('click','#rate_search_btn').on('click','#rate_search_btn', function(){
        
        if($('#rate_search').val() > ''){
            
                $.ajax({
                    url:'/api/getratessearch',
                    data: {key:'{!! $key !!}',api_token:'{!! $api_token !!}',term: $('#rate_search').val()},
                    type:'post',
                    success:function(data){
                        $("#rate_search_result").html(data);
                    }
                });
        }else{
            $("#rate_search_result").html('');
        }
    });
</script>

<style>
.e-dialog .e-dlg-content {
    padding: 18px !important;    
    padding-top: 0px !important;
}
</style>
@endpush