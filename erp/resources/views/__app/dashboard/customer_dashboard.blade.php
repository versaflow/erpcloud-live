@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif
@section('styles')
@parent
<link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
@endsection
@section('content')



<div class="row mb-4 mx-0 px-0">
    <div class="col-sm-3">
        <div class="card">
        <div class="card-body p-3 position-relative">
        <div class="row">
            <div class="col-7 text-start">
                <h4 class="mb-1 text-capitalize font-weight-bolder">{{currency_formatted($account->balance,$account->currency)}}</h4>
                <p class="text-sm font-weight-bold mb-0">
                Amount Due
                </p>
            </div>
            <div class="col-5 d-flex justify-content-end">
                <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                <i class="ni ni-money-coins text-lg opacity-10" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card">
        <div class="card-body p-3 position-relative">
        <div class="row">
            <div class="col-7 text-start">
                <h4 class="mb-1 text-capitalize font-weight-bolder">{{$account->subs_count}}</h4>
                <p class="text-sm font-weight-bold mb-0">
                Subscriptions
                </p>
            </div>
            <div class="col-5 d-flex justify-content-end">
                <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                <i class="ni ni-app text-lg opacity-10" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>
    <!--<div class="col-sm-3">
        <div class="card">
        <div class="card-body p-3 position-relative">
        <div class="row">
            <div class="col-7 text-start">
                <h4 class="mb-1 text-capitalize font-weight-bolder">{{$invoices_count}}</h4>
                <p class="text-sm font-weight-bold mb-0">
                Invoices
                </p>
            </div>
            <div class="col-5 d-flex justify-content-end">
                <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                <i class="ni ni-paper-diploma text-lg opacity-10" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card">
        <div class="card-body p-3 position-relative">
        <div class="row">
            <div class="col-7 text-start">
                <h4 class="mb-1 text-capitalize font-weight-bolder">{{$orders_count}}</h4>
                <p class="text-sm font-weight-bold mb-0">
                Orders
                </p>
            </div>
            <div class="col-5 d-flex justify-content-end">
                <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                <i class="ni ni-cart text-lg opacity-10" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>-->
    @if(!empty($pbx_domain))
    @if(in_array(session('original_role_level'),['Admin','Reseller']) || (session('original_role_level') == 'Customer' && session('parent_id')==1))
    <div class="col-sm-3">
        <div class="card">
        <div class="card-body p-3 position-relative">
        <div class="row">
            <div class="col-7 text-start">
                <h4 class="mb-1 text-capitalize font-weight-bolder">{{$pbx_domain->balance}}</h4>
                <p class="text-sm font-weight-bold mb-0">
                Airtime Balance
                </p>
            </div>
            <div class="col-5 d-flex justify-content-end">
                <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                <i class="fas fa-phone text-lg opacity-10" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card">
        <div class="card-body p-3 position-relative">
        <div class="row">
            <div class="col-7 text-start">
                <h4 class="mb-1 text-capitalize font-weight-bolder">{{$pbx_domain->monthly_usage}}</h4>
                <p class="text-sm font-weight-bold mb-0">
                Airtime Usage
                </p>
            </div>
            <div class="col-5 d-flex justify-content-end">
                <div class="icon icon-shape bg-primary shadow text-center border-radius-md">
                <i class="fas fa-server text-lg opacity-10" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>
    @endif
    @endif
</div>

<div class="row mt-4 mx-0 px-0">
    @if($account->type != 'reseller_user')
    <div class="col-sm-6">
      <div class="card h-100">
        <div class="card-header pb-0 p-3">
          <div class="row" style="height:60px">
            <div class="col-md-6">
              <h4 class="mb-0">Statement</h4>
            </div>
            <div class="col-md-6 d-flex justify-content-end align-items-center">
             <a type="button" class="btn btn-outline-secondary me-2" target="_blank" href="{{ url('statement_download/'.$account_id) }}">Download statement</a>
             <a type="button" class="btn btn-outline-success" href="javascript:void(0);" onClick="makePayment()">Pay Now</a>
            </div>
          </div>
        </div>
        <div class="card-body p-3 autosize-card" style="
    height: 400px;
    overflow-y: auto;
">      
        @if(!is_dev() || 1==1)
        <div class="table table-sm-responsive">
        <table class="table table-sm table-border min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-200">
                <tr>
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Transaction Date </th>
                    @if($account->type == 'reseller')
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Company </th>
                    @endif
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Reference </th>
                    <th class="whitespace-nowrap  py-2 text-end text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Total </th>
                    <th class="whitespace-nowrap  py-2 text-end text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Balance </th>
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider pointer-events-none text-end pl-0"> </th>
                    </tr>
            </thead>
            <tbody>
              
                    @if(count($statement_transactions) == 0)
                <tr class="bg-white">
                    <td colspan=4>You have transactions.</td>
                </tr>
                    @else
                    @foreach($statement_transactions as $trx)
                    <tr class="bg-white"> 
                   
                        <td class=" py-1 text-sm text-gray-500 ">{{date('d M Y',strtotime($trx->docdate))}}</td>
                        @if($account->type == 'reseller')
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$trx->service_company}}</span></td>
                        @endif
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">@if($trx->doctype=='Cashbook Customer Receipt')Payment: @endif{{$trx->reference}}</span></td>
                        <td class=" py-1 text-sm text-gray-500 text-end"><span style="font-family: sans-serif;">{{currency_formatted($trx->total,$trx->document_currency)}}</span></td>
                        <td class=" py-1 text-sm text-gray-500 text-end"><span style="font-family: sans-serif;">{{currency_formatted($trx->balance,$trx->document_currency)}}</span></td>
                        <td class=" text-sm text-gray-500 text-sm text-end pl-0">
                            @if($trx->doctype!='Cashbook Customer Receipt')
                            <a data-target="view_modal" href="{{ url($documents_url.'/view/'.$trx->id) }}" type="button" aria-haspopup="true" aria-expanded="false" class="btn btn-xs btn-icon btn-outline bg-light mb-0 p-2">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    @endif
            </tbody>
        </table>
        </div>
        @else
         <!-- @livewire('dashboard-statement-table',['account_id'=>$account->id]) -->
        @endif
        </div>
      </div>
    </div>
    @endif
    <div class="col-sm-6">
        @if(count($pbx_domains) > 0)
        <div class="card h-50 mb-4">
        <div class="card-header pb-0 p-3">
          <div class="row" style="height:60px">
            <div class="col-md-6">
              <h4 class="mb-0">Airtime Balances</h4>
            </div>
          </div>
        </div>
        <div class="card-body p-3" style="
    height: 50px;
    overflow-y: auto;
">
        <table class="table table-sm table-border min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-200">
                <tr>
    
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Company </th>
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Domain </th>
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Balance </th>
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Month Usage </th>
                   
                    </tr>
            </thead>
            <tbody>
              
                    @if(count($pbx_domains) == 0)
                <tr class="bg-white">
                    <td colspan=4>You have no pbx domains.</td>
                </tr>
                    @else
                    @foreach($pbx_domains as $sub)
                    <tr class="bg-white"> 
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$sub->company}}</span></td>
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$sub->domain_name}}</span></td>
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{currency_formatted($sub->balance,'ZAR')}}</span></td>
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{currency_formatted($sub->monthly_usage,'ZAR')}}</span></td>
                    </tr>
                    @endforeach
                    @endif
            </tbody>
        </table>
        </div>
      </div>
        @endif
        @if(count($activations) > 0)
        <div class="card h-50 mb-4">
        <div class="card-header pb-0 p-3">
          <div class="row" style="height:60px">
            <div class="col-md-6">
              <h4 class="mb-0">Activations</h4>
            </div>
           
          </div>
        </div>
        <div class="card-body p-3" style="
    height: 50px;
    overflow-y: scroll;
">
        <table class="table table-sm table-border min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-200">
                <tr>
                    @if($account->type == 'reseller')
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Company </th>
                    @endif
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Product </th>
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Reference </th>
                   
                    </tr>
            </thead>
            <tbody>
              
                    @if(count($activations) == 0)
                <tr class="bg-white">
                    <td colspan=4>You have activations.</td>
                </tr>
                    @else
                    @foreach($activations as $sub)
                    <tr class="bg-white"> 
                   
                        @if($account->type == 'reseller')
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$sub->company_name}}</span></td>
                        @endif
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$sub->product_code}} <br> {{$sub->product_name}}</span></td>
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$sub->detail}}</span></td>
                    </tr>
                    @endforeach
                    @endif
            </tbody>
        </table>
        </div>
      </div>
        @endif
      <div class="card h-100">
        <div class="card-header pb-0 p-3">
          <div class="row" style="height:60px">
            <div class="col-md-6">
              <h4 class="mb-0">Services</h4>
            </div>
            <div class="col-md-6 d-flex justify-content-end align-items-center">
             <a type="button" class="btn btn-outline-success" href="javascript:void(0);" onClick="placeOrder()">Place Order</a>
             <a type="button" class="btn btn-outline-success ms-2" href="javascript:void(0);" onClick="buyAirtime()">Buy Airtime</a>
            </div>
          </div>
        </div>
        <div class="card-body p-3 autosize-card" style="
    height: 400px;
    overflow-y: scroll;
">
        <table class="table table-sm table-border min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-200">
                <tr>
                    @if($account->type == 'reseller')
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Company </th>
                    @endif
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Product </th>
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider cursor-pointer">Reference </th>
                   
                    <th class="whitespace-nowrap  py-2 text-left text-xs font-medium text-gray-500 text-uppercase tracking-wider pointer-events-none text-end pl-0"> </th>
                    </tr>
            </thead>
            <tbody>
              
                    @if(count($subscriptions) == 0)
                <tr class="bg-white">
                    <td colspan=4>You have subscriptions.</td>
                </tr>
                    @else
                    @foreach($subscriptions as $sub)
                    @if($sub->product_id!=760 && $sub->product_id!=855)
                    <tr class="bg-white"> 
                   
                        @if($account->type == 'reseller')
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$sub->company_name}}</span></td>
                        @endif
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$sub->product_code}} <br> {{$sub->product_name}}</span></td>
                        <td class=" py-1 text-sm text-gray-500 "><span class="font-medium text-primary-500">{{$sub->detail}}</span></td>
                        <td class=" text-sm text-gray-500 text-sm  pl-0">
                           
                            <a target="_blank" href="{{ url('manage_service/'.$sub->id) }}" class="me-3" data-bs-toggle="tooltip" data-bs-original-title="Manage service">
                              <i class="fas fa-wrench text-secondary"></i>
                            </a>
                            <a href="{{ url('service_setup_email/'.$sub->id) }}" data-target="form_modal" class="me-3" data-bs-toggle="tooltip" data-bs-original-title="Setup instructions">
                              <i class="fas fa-envelope text-secondary"></i>
                            </a>
                           
                            <a href="{{ url($subscriptions_url.'/cancel/'.$sub->id) }}" class="me-3" data-target="ajaxconfirm" href="javascript:;" data-bs-toggle="tooltip" data-bs-original-title="Cancel subscription">
                              <i class="fas fa-trash text-secondary"></i>
                            </a>
                            @if($sub->status != 'Deleted' && in_array($sub->provision_type,['hosting' ,'airtime_contract'  ,'pbx_extension','sip_trunk']))
                            <a href="{{ url('subscription_migrate_form/'.$sub->id) }}" data-target="form_modal"  data-bs-toggle="tooltip" data-bs-original-title="Migrate subscription">
                              <i class="fas fa-box-open text-secondary"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endif
                    @endforeach
                    @endif
            </tbody>
        </table>
        </div>
      </div>
    </div>
  
</div>
@endsection

@push('page-styles')

<style>
.autosize-card {
  height: calc(100vh - 50px - 90px - 76px - 70px) !important;
  font-size:13px !important;
}
.autosize-card .font-medium {
  
  font-size:13px !important;
}
.autosize-card .text-sm {
  
  font-size:13px !important;
}
.custom-container {
  height: calc(100vh - 40px) !important;
}
.hide-link {
  opacity: 0;
  overflow: hidden;
  height: 0;
  width: 0;
  display: block;
}
</style>
@endpush

