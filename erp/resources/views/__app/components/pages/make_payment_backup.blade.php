@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif
@section('content')

@php
// restructure payment gateways 
$payflex_enabled = false;

@endphp

<div id="page-wrapper" class="container mx-auto text-center mt-0 mb-0">
<div class="col mt-3">
   
	@if($logo)
		<img style="height:55px;" src="{{ $logo }}" class="img-fluid"/>
	@else
		<h2>{{ session('parent_company') }}</h2>
	@endif
</div>
<div id="payment_tabs"></div>
@if($customer->partner_id == 1)
<div id="debit_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
        <div class="card-body">
            <div class="col-md-6 offset-md-3">
            <div class="mb-4 text-center">
            <img src="{{ public_path().'/assets/img/netcash.png' }}" height="90px"/>
            </div>
            <p>Sign up for a monthly debit order.</p>
            <div style="text-center">
                <a href="{{ $debit_order_link }}" target="_blank" id="debitbtn">Debit Order Form</a>
            </div>
            </div>
        </div>
    </div>
</div>

@if($payflex_enabled)
<div id="payflex_tab" style="display:none; text-align: left;">
    <div class="card mt-3">
        <div class="card-body">
            <div class="col-md-6 offset-md-3">
                
        <div class="mb-4">
        <img src="{{ public_path().'/assets/img/payflex.png' }}" height="90px"/>
        </div>
<input id="payflex_company">
@if($customer->balance > 0)
<input id="payflex_balance">
@endif
<form role="form" class="form-horizontal text-left" id="payflex-form" >
<input name="payflex_amount" id="payflex_amount"/>
@php
$orders_total = get_orders_total($customer->id);
@endphp

<div class="mt-3 text-left row">
@if($customer->balance > 0)
<div class="col">
<span id="payflex_balance-payment" class="e-btn e-light">Set amount to outstanding balance</span>

@if($orders_total > 0)
<span id="payflex_orders-payment" class="e-btn e-light mt-2">Set amount to outstanding balance and pay orders</span>
@endif
</div>
@endif

<div class="col text-right">
<button type="submit" id="payflex-submitbtn">Submit</button>
</div>
</div>
</form>
</div>
        </div>
    </div>
</div>
@endif

@endif
@if(!empty($reseller->bank_details))
<div id="eft_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
        <div class="card-body">
            <div class="col-md-6 offset-md-3">
            @if($customer->partner_id == 1)   
            <div class="mb-4 text-center">
            <img src="{{ public_path().'/assets/img/fnb.png' }}" height="90px"/>
            </div>
            @endif
            @if(!empty($reseller->bank_details) )
            {!! nl2br($reseller->bank_details) !!}
            @else
            <p>No Bank Details set. Please contact support to get the EFT details.</p>
            @endif
        </div>
        </div>
    </div>
</div>
@endif

@php
if ($reseller->id==1 && $reseller->payfast_enabled && !empty($reseller->payfast_id) && !empty($reseller->payfast_key)  && !empty($reseller->payfast_pass_phrase)){
    if(!empty($reseller->payfast_key)){
        $payfast = new Payfast;
        if(is_dev()){
         $payfast->setDebug();
         $reseller->payfast_id = '10000100';
         $reseller->payfast_key = '46f0cd694581a';
         }
        $payfast->setPaymentID(session('account_id'));
        $payfast->setCredentials($reseller->payfast_id, $reseller->payfast_key,$reseller->payfast_pass_phrase);
        $payfast_form = $payfast->getForm();
    }
}
$payfast_enabled = false;
if ($reseller->id==1){
    if (!str_contains($payfast_form,'not set') && $reseller->payfast_enabled && !empty($reseller->payfast_id) && !empty($reseller->payfast_key)  && !empty($reseller->payfast_pass_phrase)){
        $payfast_enabled = true;
    }
}elseif($reseller->payfast_enabled && !empty($reseller->payfast_id)){
    $payfast_enabled = true;
}

@endphp

@if ($payfast_enabled)
<div id="payfast_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
        <div class="card-body">
            <div class="col-md-6 offset-md-3">
            <div class="mb-4 text-center">
            <img src="{{ public_path().'/assets/img/payfast.png' }}" height="90px"/>
            </div>
            @if($reseller->id==1 && $reseller->payfast_enabled && !empty($reseller->payfast_id) && !empty($reseller->payfast_key)  && !empty($reseller->payfast_pass_phrase))
            {!! $payfast_form; !!}
            @endif
            <form role="form" class="form-horizontal text-center" id="pf-form" >
            <input id="payfast_company">
            @if($customer->balance > 0)
            <input id="payfast_balance">
            @endif
            <input name="pf_amount" id="pf_amount"/>
            <div class="mt-3 text-left row">
            @if($customer->balance > 0)
            <div class="col">
            <span id="payfast_balance-payment" class="e-btn e-light">Set amount to outstanding balance</span>
            
            @if($orders_total > 0)
            <span id="payfast_orders-payment" class="e-btn e-light mt-2">Set amount to outstanding balance and pay orders</span>
            @endif
            </div>
            @endif
            </div>
            <div class="mt-2 text-center">
            <button type="submit" id="pf-submitbtn" ><img src="https://www.payfast.co.za/images/buttons/light-large-paynow.png" width="174" height="59" alt="Pay" title="Pay Now with PayFast" /></button>
            </div>
            </form>
            </div>
        </div>
    </div>
</div> 
@endif
    


</div>
@endsection
@push('page-scripts')

<script>
@if($customer->partner_id == 1)

@if($customer->balance > 0)
$("#balance-payment").click(function(){
    callpay_amount.value = '{{ currency($customer->balance) }}';
});
$("#payflex_balance-payment").click(function(){
    payflex_amount.value = '{{ currency($customer->balance) }}';
});

$("#payfast_balance-payment").click(function(){
    pf_amount.value = '{{ currency($customer->balance) }}';
});
@endif
@if($orders_total > 0)
$("#orders-payment").click(function(){
    callpay_amount.value = '{{ currency($customer->balance+$orders_total)  }}';
});
$("#payflex_orders-payment").click(function(){
   payflex_amount.value = '{{ currency($customer->balance+$orders_total)  }}';
});

$("#payfast_orders-payment").click(function(){
   pf_amount.value = '{{ currency($customer->balance+$orders_total)  }}';
});
@endif

company = new ej.inputs.TextBox({
	placeholder: "Company ",
    floatLabelType: 'Auto',
    readonly: true,
    value: '{!! $customer->company !!}'
});
company.appendTo("#company ");

balance = new ej.inputs.TextBox({
	placeholder: "Balance ",
    floatLabelType: 'Auto',
    readonly: true,
    enabled: false,
    value: 'R {{ $customer->balance }}', 
});
balance.appendTo("#balance");

callpay_amount = new ej.inputs.NumericTextBox({
	format: 'R ###########.##',
	placeholder: 'Amount',
    floatLabelType: 'Auto',
	showSpinButton: false,
	decimals: 2,
	value: "{{ ($amount > 10) ? $amount : 10 }}",
	min: 10
});

callpay_amount.appendTo("#callpay_amount");	


@if($payflex_enabled)
payflex_company = new ej.inputs.TextBox({
	placeholder: "Company ",
    floatLabelType: 'Auto',
    readonly: true,
    enabled: false,
    value: '{!! $customer->company !!}'
});
payflex_company.appendTo("#payflex_company ");
@if($customer->balance > 0)
payflex_balance = new ej.inputs.TextBox({
	placeholder: "Outstanding Balance ",
    floatLabelType: 'Auto',
    readonly: true,
    enabled: false,
    value: 'R {{ $customer->balance }}', 
});
payflex_balance.appendTo("#payflex_balance");
@endif

payflex_amount = new ej.inputs.NumericTextBox({
	format: 'R ###########.##',
	placeholder: 'Amount',
    floatLabelType: 'Auto',
	showSpinButton: false,
	decimals: 2,
	value: "{{ ($amount > 10) ? $amount : 10 }}",
	min: 10
});

payflex_amount.appendTo("#payflex_amount");	
@endif

@if($payfast_enabled)
payfast_company = new ej.inputs.TextBox({
	placeholder: "Company ",
    floatLabelType: 'Auto',
    readonly: true,
    enabled: false,
    value: '{!! $customer->company !!}'
});
payfast_company.appendTo("#payfast_company ");
@if($customer->balance > 0)
payfast_balance = new ej.inputs.TextBox({
	placeholder: "Outstanding Balance ",
    floatLabelType: 'Auto',
    readonly: true,
    enabled: false,
    value: 'R {{ $customer->balance }}', 
});
payfast_balance.appendTo("#payfast_balance");
@endif

@endif
@endif
    var payment_tabs = new ej.navigations.Tab({
        items: [
            @if($customer->partner_id == 1 && $payflex_enabled)
            { header: { 'text': 'Payflex - Instant Payment' }, content: '#payflex_tab'},
            @endif
            @if ($payfast_enabled)
            { header: { 'text': 'Payfast - Instant Payment' }, content: '#payfast_tab'},
            @endif
            @if(!empty($reseller->bank_details) )
            { header: { 'text': '48 Hour Payment' }, content: '#eft_tab'},
            @endif
            @if($customer->partner_id == 1)
            { header: { 'text': 'Debit Order' }, content: '#debit_tab'},
            @endif
        ],
		selectedItem: 0,
    });
    payment_tabs.appendTo('#payment_tabs');
    @if($customer->partner_id == 1)
$(document).ready(function() {
debitbtn = new ej.buttons.Button({ cssClass: `e-info`}, '#debitbtn');
submitbtn = new ej.buttons.Button({ cssClass: `e-info`}, '#callpay-submitbtn');
payflex_submitbtn = new ej.buttons.Button({ cssClass: `e-info`}, '#payflex-submitbtn');
});

$("#callpay-form").submit(function(e){
    e.preventDefault();
	
	if(callpay_amount.value < 100){
    	toastNotify('Minimum 100 Required', 'warning');
    	return false;
	}else{
    	submitbtn.disabled = true;
    	toastNotify('Redirecting to CallPay.','success');	
	}
  
   $.ajax({
        url: '/integrations/callpay_generate/'+{{$customer->id}}+'/'+callpay_amount.value,
        success: function(data){
            window.open(data);
        },
		error: function(jqXHR, textStatus, errorThrown) {
		},
    });
});

$("#payflex-form").submit(function(e){
    e.preventDefault();
	
	if(payflex_amount.value < 100){
    	toastNotify('Minimum 100 Required', 'warning');
    	return false;
	}else{
    	payflex_submitbtn.disabled = true;
    	toastNotify('Redirecting to PayFlex.','success');	
	}
  
   $.ajax({
        url: '/integrations/payflex_generate/'+{{$customer->id}}+'/'+payflex_amount.value,
        success: function(data){
            window.open(data);
        },
		error: function(jqXHR, textStatus, errorThrown) {
		},
    });
});
@endif

@if ($reseller->payfast_enabled && !empty($reseller->payfast_id))

pfsubmitbtn = new ej.buttons.Button({}, '#pf-submitbtn');
pf_amount = new ej.inputs.NumericTextBox({
	format: 'R ###########.##',
	placeholder: 'Amount',
    floatLabelType: 'Auto',
	showSpinButton: false,
	decimals: 2,
	value: "100",
	min: 100,
    @if(!empty($reseller->payfast_key) && $reseller->id==1)
    change: function(){
        $("#payfast_amount").val(pf_amount.value);
        $.ajax({
            url: '/integrations/payfast_get_signature/'+'/'+pf_amount.value,
            success: function(data){
               $("#signature").val(data);
            },
    		error: function(jqXHR, textStatus, errorThrown) {
    		},
        });    
    }
    @endif
	
});

pf_amount.appendTo("#pf_amount");	

$("#pf-form").submit(function(e){
    e.preventDefault();
	
	if(pf_amount.value < 100){
	toastNotify('Minimum 100 Required', 'warning');
	return false;
	}else{
	    pfsubmitbtn.disabled = true;
	    toastNotify('Redirecting to PayFast.','success');	
	}
    
    @if(!empty($reseller->payfast_key) && $reseller->id==1)
        if($("#signature").val() == ''){
             $.ajax({
                url: '/integrations/payfast_get_signature/'+pf_amount.value,
                success: function(data){
                    $("#signature").val(data);
                    $("#payfast_form").submit();
                },
        		error: function(jqXHR, textStatus, errorThrown) {
        		},
            });    
        }else{
            $("#payfast_form").submit();
        }
    @else
        $.ajax({
            url: '/integrations/payfast_button/'+{{$customer->id}}+'/'+pf_amount.value,
            success: function(data){
                window.open(data);
            },
    		error: function(jqXHR, textStatus, errorThrown) {
    		},
        });
    @endif
});
@endif
</script>
@endpush
@push('page-styles')

<style>
@if(!request()->ajax())
body{
background-image: url(/assets/img/000.jpg);    
}
@endif
#page-wrapper{
		background-color: #fbfbfb;
		padding: 2%;
	
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
		height: 100%;
}
#pf-submitbtn{
background: none !important;
border: none !important;
}
</style>
@endpush