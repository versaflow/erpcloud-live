<form class='form-horizontal' id ='migrateSubscription' action='/subscription_migrate' style="font-size: 14px;">
<input type="hidden" name="subscription_id" value="{{ $subscription_id }}">
<input type="hidden" id="package_amount" name="package_amount" value="{{ $package_amount }}">
<div class="card card-body bg-light mt-0">



    <div class="row mt-1 mb-1">
        <div class="col">
            <label for="new_product_id">Select qty</label>
            <input id="line_qty" name="line_qty"/>
        </div>
    </div>
    
    <div class="row mt-1 mb-1">
        <div class="col">
            <label>Last month usage (rand)</label>
            <span> {{$lastmonth_rands_total}} </span>
        </div>
        <div class="col">
            <label>Last month usage (minutes)</label>
            <span> {{$lastmonth_minutes_total}} </span>
        </div>
    </div>
    
    <div class="row mt-1 mb-1">
        <div class="col">
            <label>Current monthly airtime amount (rand)</label>
            <span> {{$subscription->usage_allocation}} </span>
        </div>
        <div class="col">
            <label>Current monthly airtime amount (minutes)</label>
            <span> {{$subscription->usage_allocation * 2}} </span>
        </div>
    </div>
    <div class="row mt-1 mb-1">
        <div class="col">
            <label for="new_product_id">New airtime amount (rand)</label>
            <span id="new_monthly_amount">{{$subscription->usage_allocation}}</span>
        </div>
        <div class="col">
            <label for="new_product_id">New airtime amount (minutes)</label>
            <span id="new_monthly_amount_minutes">{{$subscription->usage_allocation * 2}}}</span>
        </div>
    </div>
    
    <div class="row mt-1 mb-1" >
    <div class="col">
        Your new airtime package will be applied on the next invoice.
    
    </div>
</div>
</div>
</form>
<script>
    var line_qty = new ej.inputs.NumericTextBox({
        decimals: 0,
        format: "0",
        min:1,
        value: '{{$subscription->qty}}',
        change: function(){
             $("#new_monthly_amount").text('{{ $package_amount }}' * this.value); 
             $("#new_monthly_amount_minutes").text('{{ $package_amount }}' * this.value * 2);     
        }
    },"#line_qty");
    
    $('#migrateSubscription').on('submit', function(e) {
    	e.preventDefault();
    	formSubmit('migrateSubscription');
    });
</script>