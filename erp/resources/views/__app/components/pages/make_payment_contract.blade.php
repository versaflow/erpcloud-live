@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.guest' ))

@if(!request()->ajax())
	
@endif
@section('content')

@php
// restructure payment gateways 

if($customer->id == 1 && is_main_instance()){
$customer = dbgetaccount(12);
}


$payfast_payment_option = get_payment_option('Payfast');
$bank_payment_option = get_payment_option('Bank Details');
$bank_usd_payment_option = get_payment_option('Bank Details USD');
$crypto_payment_option = get_payment_option('Crypto Details');
$netcash_payment_option = get_payment_option('Netcash');
$stripe_payment_option = get_payment_option('Stripe');
$paypal_payment_option = get_payment_option('Paypal');



@endphp

<div id="" class="container mx-auto text-center mt-0 mb-0" style="width:80%">
<div class="col mt-3 py-4">
   
	@if($logo)
		<img style="height:55px;" src="{{ $logo }}" class="img-fluid"/>
	@else
		<h2>{{ session('parent_company') }}</h2>
	@endif
</div>
<div id="payment_tabs"></div>

@if($customer->partner_id == 1)
@if($customer->partner_id == 1 && $customer->currency == $netcash_payment_option->customer_currency && $netcash_payment_option->enabled)
<div id="debit_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
        <div class="card-body">
            <div class="col-md-6 offset-md-3">
            <div class="mb-4 text-center">
            <img src="{{ public_path().'//assets/img/netcash.png' }}" height="90px"/>
            </div>
            <p>Sign up for a monthly debit order.</p>
            <div style="text-center">
                <a href="{{ $debit_order_link }}" target="_blank" id="debitbtn">Debit Order Form</a>
            </div>
            </div>
        </div>
    </div>
</div>
@endif
   
    
 
@endif

@if(!empty($bank_payment_option->payment_instructions) && $customer->currency == $bank_payment_option->customer_currency)
    <div id="eft_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
    <div class="card-body">
    <div class="col-md-6 offset-md-3">
    @if($customer->partner_id == 1)   
        <div class="mb-4 text-center">
        <img src="{{ public_path().'//assets/img/fnb.png' }}" height="90px"/>
        </div>
    @endif
    @if(!empty($bank_payment_option->payment_instructions) )
        {!! nl2br($bank_payment_option->payment_instructions) !!}
    @else
        <p>No Bank Details set. Please contact support to get the EFT details.</p>
    @endif
    </div>
    </div>
    </div>
    </div>
@endif
@if(!empty($bank_usd_payment_option->payment_instructions) && $customer->currency == $bank_usd_payment_option->customer_currency)
    <div id="eft_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
    <div class="card-body">
    <div class="col-md-6 offset-md-3">
    @if($customer->partner_id == 1)   
        <div class="mb-4 text-center">
        <img src="{{ public_path().'//assets/img/fnb.png' }}" height="90px"/>
        </div>
    @endif
    @if(!empty($bank_usd_payment_option->payment_instructions) )
        {!! nl2br($bank_usd_payment_option->payment_instructions) !!}
    @else
        <p>No Bank Details set. Please contact support to get the EFT details.</p>
    @endif
    </div>
    </div>
    </div>
    </div>
@endif

@if(!empty($paypal_payment_option->enabled) && !empty($paypal_payment_option->payment_instructions) && $customer->currency == 'USD')
    <div id="paypal_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
    <div class="card-body">
    <div class="col-md-6 offset-md-3">
    @if($customer->partner_id == 1)   
        <div class="mb-4 text-center">
        <img src="{{ public_path().'//assets/img/PayPal.png' }}" height="90px"/>
        </div>
    @endif
    @if(!empty($paypal_payment_option->payment_instructions) )
        Use the link below to make a payment via PayPal.
        <a href="https://{!! $paypal_payment_option->payment_instructions !!}">{{$paypal_payment_option->payment_instructions}}</a>
    @endif
    </div>
    </div>
    </div>
    </div>
@endif

@php

$payfast_subscriptions_active = $payfast_payment_option->enable_payfast_subscriptions;

if ($reseller->id==1 && $payfast_payment_option->enabled && !empty($payfast_payment_option->payfast_id) && !empty($payfast_payment_option->payfast_key)  && !empty($payfast_payment_option->payfast_pass_phrase)){
    if(!empty($payfast_payment_option->payfast_key)){
        $payfast = new Payfast;
     
        if(is_dev()){
        //$payfast->setDebug();
        $payfast->setCredentials($payfast_payment_option->payfast_id, $payfast_payment_option->payfast_key,$payfast_payment_option->payfast_pass_phrase);
        }else{
      
        $payfast->setCredentials($payfast_payment_option->payfast_id, $payfast_payment_option->payfast_key,$payfast_payment_option->payfast_pass_phrase);
        }
        $payfast->setPaymentID($customer->id);
        $payfast_form = $payfast->getForm();
    }

$payfast_enabled = false;
$payfast_subscription_enabled = false;




if ($reseller->id==1){
    if (!str_contains($payfast_form,'not set') && $payfast_payment_option->enabled && !empty($payfast_payment_option->payfast_id) && !empty($payfast_payment_option->payfast_key)  && !empty($payfast_payment_option->payfast_pass_phrase)){
        $payfast_enabled = true;
        if($payfast_subscriptions_active){
        $payfast_subscription_enabled = true;
        }
    }
}elseif($payfast_payment_option->enabled && !empty($payfast_payment_option->payfast_id)){
    $payfast_enabled = true;
}


$payfast_subscription_exists = \DB::table('acc_payfast_subscriptions')->where('account_id', $customer->id)->where('status', 'Enabled')->count();

if ($payfast_subscription_exists > 0) {
$payfast_subscription_enabled = false;
}
if(session('instance')->domain_name != 'iptv.versaflow.io'){
$payfast_subscription_enabled = false;
}
//if(session('instance')->domain_name == 'iptv.versaflow.io'){
//$payfast_enabled = false;
//}

if ($reseller->id==1 &&  $payfast_subscription_enabled && $customer->currency == 'ZAR'){

    $payfast_subscription = new PayfastSubscription();
    $payfast_subscription->setCredentials($payfast_payment_option->payfast_id, $payfast_payment_option->payfast_key,$payfast_payment_option->payfast_pass_phrase);
   
    if(is_dev()){
        //$payfast_subscription->setAccount(12);
        //$payfast_subscription->setPaymentID(12);
        $payfast_subscription->setAccount($customer->id);
        $payfast_subscription->setPaymentID($customer->id);
    }else{
        $payfast_subscription->setAccount($customer->id);
        $payfast_subscription->setPaymentID($customer->id);
    }
    
    $pending_total = get_orders_total($customer->id);
    $initial_amount = $pending_total + $customer->balance;
    
    if($initial_amount <= 0){
        $payfast_subscription_form = $payfast_subscription->getForm();
        $initial_amount = 0;
    }else{
        $payfast_subscription_form = $payfast_subscription->getForm($initial_amount);
    }
}
}
@endphp

@if ($payfast_enabled)
    <div id="pf_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
    <div class="card-body">
    <div class="col-md-6 offset-md-3">
    <div class="mb-4 text-center">
    <img src="{{ public_path().'/assets/img/payfast.png' }}" height="90px"/>
    </div>
    @if($reseller->id==1 && $payfast_payment_option->enabled && !empty($payfast_payment_option->payfast_id) && !empty($payfast_payment_option->payfast_key)  && !empty($payfast_payment_option->payfast_pass_phrase))
        {!! $payfast_form; !!}
    @endif
    <form role="form" class="form-horizontal text-start" id="pf-form" >
    <input id="pf_company">
    @if($customer->balance > 0)
        <input id="pf_balance">
    @endif
    <input name="pf_amount" id="pf_amount" required="required"/>
    <div class="mt-3 text-start row">
    @if($customer->balance > 0)
        <div class="col">
        <span id="pf_balance-payment" class="e-btn e-light">Set amount to outstanding balance</span>
        
        @if($orders_total > 0)
            <span id="pf_orders-payment" class="e-btn e-light mt-2">Set amount to outstanding balance and pay orders</span>
        @endif
        </div>
    @endif
    </div>
    <div class="mt-0 text-right">
    <button type="submit" id="pf-submitbtn" ><img src="https://www.payfast.co.za/images/buttons/light-large-paynow.png" width="118" height="40" alt="Pay" title="Pay Now with PayFast" /></button>
    </div>
    </form>
    </div>
    </div>
    </div>
    </div> 
@endif

@if ($crypto_payment_option->enabled && !empty($crypto_payment_option->payment_instructions) && $customer->currency == $crypto_payment_option->customer_currency)

    <div id="crypto_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
    <div class="card-body">
    <div class="col-md-6 offset-md-3">
  
    @if(!empty($crypto_payment_option->payment_instructions) )
        {!! nl2br($crypto_payment_option->payment_instructions) !!}
    @endif
    </div>
    </div>
    </div>
    </div>

@endif

@if ($stripe_payment_option->enabled && $customer->currency == 'USD')
    <div id="stripe_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
    <div class="card-body">
    <div class="col-md-6 offset-md-3">
    <div class="mb-4 text-center">
    <img src="{{ public_path().'/assets/img/stripe.png' }}" height="90px"/>
    </div>
   
    <form role="form" class="form-horizontal text-start" id="stripe-form" >
    <input id="stripe_company">
    @if($customer->balance > 0)
        <input id="stripe_balance">
    @endif
    <label> Payment Amount</label>
    <input name="stripe_amount" id="stripe_amount" required="required"/>
    <div class="mt-3 text-start row">
    @if($customer->balance > 0)
        <div class="col">
        <span id="stripe_balance-payment" class="e-btn e-light">Set amount to outstanding balance</span>
        
        @if($orders_total > 0)
            <span id="stripe_orders-payment" class="e-btn e-light mt-2">Set amount to outstanding balance and pay orders</span>
        @endif
        </div>
    @endif
    </div>
    <div class="mt-0 text-right">
    <button type="submit" id="stripe-submitbtn" class="btn btn-primary"> Pay Now</button>
    </div>
    </form>
    </div>
    </div>
    </div>
    </div> 
@endif

@if ($reseller->id==1 &&  $payfast_subscription_enabled && $customer->currency == 'ZAR')
    <div id="pfs_tab" style="display:none; text-align: center;">
    <div class="card mt-3">
    <div class="card-body">
    <div class="col-md-6 offset-md-3">
    <div class="mb-4 text-center">
    <img src="{{ public_path().'/assets/img/payfast.png' }}" height="90px"/>
    </div>
    
    
    
    <div class="text-start">
    <input id="pfs_company">
    @if($customer->balance > 0)
    <input id="pfs_balance">
    @endif
    @if($pending_total > 0)
    <input id="pfs_pending_invoices"> 
    @endif
    <input id="pfs_initial_amount">
    <input id="pfs_recurring_amount">
    </div>
    <div class="mt-3 text-start ">
    @if($initial_amount > 0)
        The subscription will charge your current account balance: <br>R {!! currency($initial_amount) !!}.
        <br><br>
        Your subscription amount will be charged for future payments: <br>R {!! currency($customer->subs_total+$pending_total) !!}.
    @else
        Your card will not be charged now, your subscription amount will be charged for future payments: <br>R  {!! currency($customer->subs_total+$pending_total) !!}.
    @endif
    </div>
      
    <div class="mt-3 text-start ">
    Payfast subscriptions will be billed on the 1st, which will deduct your subscription total. You will be notified if your subscription total changes.
    </div>
    
    
    <div class="mt-3 text-start ">
    <p><strong>Contract Period</strong></p><p>The agreement shall commence upon signing and extend for a period of twelve (12) months from the effective date, unless terminated earlier as per the terms herein.</p><p> </p><p><strong>Payment Terms</strong></p><p>I/We hereby authorise you to issue and deliver payment instructions to your Banker for collection against my/our above-mentioned account at my/our above-mentioned Bank (or any other bank or branch to which I/we may transfer my/our account) on condition that the sum of such payment instructions will never exceed my/our obligations as agreed to in the Agreement and commencing on the date specified above and continuing until this Authority and Mandate is terminated by me/us by giving you notice in writing of not less than 20 ordinary working days, and sent by prepaid registered post or delivered to your address as indicated above. </p><p> </p><p>The individual payment instructions so authorised to be issued must be issued and delivered monthly. In the event that the payment day falls on a Sunday, or recognised South African public holiday, the payment day will automatically be the preceding ordinary business day. Payment may be debited against my account on the first of every month. </p><p> </p><p>I / We understand that the withdrawals hereby authorized will be processed through a computerized system provided by the South African Banks and I also understand that details of each withdrawal will be printed on my bank statement. </p><p> </p><p><strong>Mandate</strong></p><p>I/We acknowledge that all payment instructions issued by you shall be treated by my/our above-mentioned Bank as if the instructions have been issued by me/us personally. </p><p> </p><p><strong>Cancellation</strong></p><p>I/We agree that although this Authority and Mandate may be cancelled by me/us, such cancellation will not cancel the Agreement. I/We shall not be entitled to any refund of amounts which you have withdrawn while this Authority was in force, if such amounts were legally owing to you.</p><p> </p><p><strong>Assignment</strong></p><p>I/We acknowledge that this Authority may be ceded or assigned to a third party if the Agreement is also ceded or assigned to that third party, but in the absence of such assignment of the Agreement, this Authority and Mandate cannot be assigned to any third party.</p>
    </div>
    
  
    <div class="mt-2 text-right">
    {!! $payfast_subscription_form !!}
    </div>
    </div>
    </div>
    </div>
    </div> 
@endif    


</div>
@endsection
@push('page-scripts')

<script>
@if ($stripe_payment_option->enabled && $customer->currency == 'USD')
    stripe_amount = new ej.inputs.NumericTextBox({
    format: '$ ###########.##',
    showSpinButton: false,
    decimals: 2,
    min: 10,
    },"#stripe_amount");
    
    stripe_company = new ej.inputs.TextBox({
    placeholder: "Company ",
    floatLabelType: 'Auto',
    readonly: true,
    enabled: false,
    value: "{{ str_replace(['"',"'"], "",$customer->company) }}"
    });
    stripe_company.appendTo("#stripe_company ");
    @if($customer->balance > 0)
    stripe_balance = new ej.inputs.TextBox({
    placeholder: "Outstanding Balance ",
    floatLabelType: 'Auto',
    readonly: true,
    enabled: false,
    value: '$ {{ currency($customer->balance) }}',
    });
    stripe_balance.appendTo("#stripe_balance");
    @endif
    
    @if($customer->balance > 0)
        $("#stripe_balance-payment").click(function(){
            stripe_amount.value = '{{ currency($customer->balance) }}';
        });
    @endif
    
    @if($orders_total > 0)
        $("#stripe_orders-payment").click(function(){
            stripe_amount.value = '{{ currency($customer->balance+$orders_total) }}';
            
        });
    @endif
    
    stripesubmitbtn = new ej.buttons.Button({}, '#stripe-submitbtn');
  	
    
    $("#stripe-form").submit(function(e){
    e.preventDefault();
    
    if(stripe_amount.value < 10){
        toastNotify('Minimum 10 Required', 'warning');
        return false;
    }else{
        stripesubmitbtn.disabled = true;
        toastNotify('Redirecting to Stripe.','success');	
    }
    
    window.location = '/stripe_payment/{{$customer->id}}/'+stripe_amount.value;
    });
@endif


@if($customer->partner_id == 1)
    
    @if($customer->balance > 0)
        $("#pf_balance-payment").click(function(){
            @if($customer->currency == 'USD')
            pf_amount.value = '{{ currency(convert_currency_usd_to_zar($customer->balance)) }}';
            @else
            pf_amount.value = '{{ currency($customer->balance) }}';
            @endif
        });
    @endif
    
    @if($orders_total > 0)
        $("#pf_orders-payment").click(function(){
            @if($customer->currency == 'USD')
            pf_amount.value = '{{ currency(convert_currency_usd_to_zar($customer->balance+$orders_total)) }}';
            @else
            pf_amount.value = '{{ currency($customer->balance+$orders_total) }}';
            @endif
        });
    @endif
    
    company = new ej.inputs.TextBox({
    placeholder: "Company ",
    floatLabelType: 'Auto',
    readonly: true,
    value: "{{ str_replace(['"',"'"], "",$customer->company) }}"
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
    
    @if($payfast_enabled)
        pf_company = new ej.inputs.TextBox({
        placeholder: "Company ",
        floatLabelType: 'Auto',
        readonly: true,
        enabled: false,
        value: "{{ str_replace(['"',"'"], "",$customer->company) }}"
        });
        pf_company.appendTo("#pf_company ");
        @if($customer->balance > 0)
            pf_balance = new ej.inputs.TextBox({
            placeholder: "Outstanding Balance ",
            floatLabelType: 'Auto',
            readonly: true,
            enabled: false,
            @if($customer->currency == 'USD')
            value: 'R {{ currency(convert_currency_usd_to_zar($customer->balance)) }}',
            @else
            value: 'R {{ currency($customer->balance) }}',
            @endif
            });
            pf_balance.appendTo("#pf_balance");
        @endif
    @endif
    
    @if($reseller->id==1 &&  $payfast_subscription_enabled )
        pfs_company = new ej.inputs.TextBox({
        placeholder: "Company ",
        floatLabelType: 'Auto',
        readonly: true,
        enabled: false,
        value: "{{ str_replace(['"',"'"], "",$customer->company) }}"
        });
        pfs_company.appendTo("#pfs_company ");
        @if($customer->balance > 0)
        pfs_balance = new ej.inputs.TextBox({
        placeholder: "Outstanding Balance ",
        floatLabelType: 'Auto',
        readonly: true,
        enabled: false,
        value: 'R {{ $customer->balance }}', 
        });
        pfs_balance.appendTo("#pfs_balance");
        @endif
        @if($pending_total > 0)
        pfs_pending_invoices = new ej.inputs.TextBox({
        placeholder: "Pending Invoices Total ",
        floatLabelType: 'Auto',
        readonly: true,
        enabled: false,
        value: 'R {{ $pending_total }}', 
        });
        pfs_pending_invoices.appendTo("#pfs_pending_invoices");
        @endif
      
        pfs_initial_amount = new ej.inputs.TextBox({
        placeholder: "Initial Amount ",
        floatLabelType: 'Auto',
        readonly: true,
        enabled: false,
        value: 'R {{ $initial_amount }}', 
        });
        pfs_initial_amount.appendTo("#pfs_initial_amount");
      
        pfs_recurring_amount = new ej.inputs.TextBox({
        placeholder: "Subscription Total ",
        floatLabelType: 'Auto',
        readonly: true,
        enabled: false,
        value: 'R {{ $customer->subs_total+$pending_total }}', 
        });
        pfs_recurring_amount.appendTo("#pfs_recurring_amount");
    @endif
@endif
    
var payment_tabs = new ej.navigations.Tab({
items: [
@if ($payfast_enabled && $customer->currency == 'ZAR')
    { header: { 'text': 'Instant Payment' }, content: '#pf_tab'},
@endif

@if ($reseller->id==1 &&  $payfast_subscription_enabled && $customer->currency == 'ZAR') 
    { header: { 'text': 'Recurring Payment' }, content: '#pfs_tab'},
@endif
@if ($stripe_payment_option->enabled && $customer->currency == 'USD')
    { header: { 'text': 'Stripe Payment' }, content: '#stripe_tab'},
@endif

@if(!empty($bank_payment_option->payment_instructions) )
    { header: { 'text': 'Standard Payment' }, content: '#eft_tab'},
@endif
@if ($crypto_payment_option->enabled &&  !empty($crypto_payment_option->payment_instructions) && $customer->currency == $crypto_payment_option->customer_currency)
    { header: { 'text': 'USDT ' }, content: '#crypto_tab'},
@endif 
@if($customer->partner_id == 1 && $customer->currency == 'ZAR' && $netcash_payment_option->enabled)
{ header: { 'text': 'Debit Order' }, content: '#debit_tab'},
@endif
@if(!empty($bank_usd_payment_option->payment_instructions) && $customer->currency == 'USD')
    { header: { 'text': 'PayPal' }, content: '#paypal_tab'},
@endif

],
selectedItem: 0,
});
payment_tabs.appendTo('#payment_tabs');


@if ($payfast_payment_option->enabled && !empty($payfast_payment_option->payfast_id))

    pfsubmitbtn = new ej.buttons.Button({}, '#pf-submitbtn');
    pf_amount = new ej.inputs.NumericTextBox({
    format: 'R ###########.##',
    placeholder: 'Amount',
    floatLabelType: 'Auto',
    showSpinButton: false,
    decimals: 2,
    min: 100,
    @if(!empty($payfast_payment_option->payfast_key) && $reseller->id==1)
        change: function(){
        $("#payfast_amount").val(pf_amount.value);
        $.ajax({
        url: '/integrations/payfast_get_signature/{{$customer->id}}/'+pf_amount.value,
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
    
    @if(!empty($payfast_payment_option->payfast_key) && $reseller->id==1)
        if($("#signature").val() == ''){
        $.ajax({
        url: '/integrations/payfast_get_signature/{{$customer->id}}/'+pf_amount.value,
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
padding: 0;
}

label, .e-float-text{
color: #1e1e1e !important;
font-weight: bold !important;
-webkit-text-fill-color: #1e1e1e !important;
}

input{
color: #1e1e1e !important;

-webkit-text-fill-color: #1e1e1e !important;
}
.e-tab .e-content{
    min-height:435px !important;    
}
.e-tab .e-content.e-progress {
    min-height:449px !important; 
}
</style>
@endpush