@extends( '__app.layouts.api' )

@if(!request()->ajax())
	
@endif
@section('content')

<div id="ordersuccess" class="alert alert-success" role="alert" style="display:none">
  <h4 class="alert-heading">Order processed.</h4>
  <p>Thank you for your order.</p>
  <hr>
  <p class="mb-0">Your services has been automatically applied to your account.</p>
  <button onClick="window.location.reload();" class="btn btn-secondary btn-sm">New Order</button>
</div>
<div id="orderwarning" class="alert alert-warning" role="alert"  style="display:none">
  <h4 class="alert-heading">Order error.</h4>
  <hr>
  <p class="mb-0" id="orderwarning_msg"></p>
  <button onClick="window.location.reload();" class="btn btn-secondary btn-sm">Retry</button>
</div>
<div id="ordererror" class="alert alert-warning" role="alert"  style="display:none">
  <h4 class="alert-heading">Order error.</h4>
  <hr>
  <p class="mb-0" id="ordererror_msg"></p>
  <button onClick="window.location.reload();" class="btn btn-secondary btn-sm">Retry</button>
</div>

<div id="shop">
   <div id="product_select">
       
   </div>
</div>

<div class="card border-0" id="ordercard" style="display:none">

{!! Form::open(array("url"=> "api/postorder", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "apiOrder")) !!}	
<input type="hidden" name="api_token" value="{{ $api_token }}" />
<input type="hidden" name="key" value="{{ $app_key }}" />
<input type="hidden" id="account_balance" value="{{$account->balance}}" />
<input type="hidden" id="qty" name="qty" value="1" />
<input type="hidden" id="product_id" name="product_id" value="1" />

    <div class="card-body py-0" id="numberblock" style="display:none">
        <div class="row">
            @foreach($products as $product)
            @php
                $class = 'product_airtime'; 
                
                if($product->id == 127 || $product->id == 128){
                    $class = 'product_number'; 
                }elseif(!str_contains($product->title,'Prepaid')){
                    $class = 'product_package'; 
                }
            @endphp
            
             <div class="col-sm-12 col-md-12 col-lg-4 {{$class}}" style="display:none">
              <div class="custom-column">
                <div class="custom-column-header row d-flex flex-wrap align-items-center">
                    <div class="col-auto p-0"><img src="{{$product->image_url}}" class="cc-image" height="50px" /></div>
                    <div class="col cc-title"> {{$product->title}}</div>
                    <div class="col-auto p-0"><button type="button" href="#" onClick="javascript:void(0)" class="btn btn-light buybtn" data-attr-id="{{$product->id}}" data-attr-qty="{{$product->qty}}"><i class="fas fa-plus"></i></button></div>
                    </div>
                <div class="custom-column-content row">
                  {{$product->code}}
                </div>
                
              </div>
            </div>
            
            @endforeach
        </div>
    </div>
    
    
    <div class="card-body" >
       
      
        
        <div class="form-group" id="phone_number_port_div" style="display:none">
            <label for="phone_number_port">Phone Number to Port</label><br>
            <p class="text-muted">Please note number porting takes upto two weeks to complete.</p><br>
            <input name="phone_number_port" id="phone_number_port" style="height:50px;"/>
        </div>
        
        <div class="form-group" id="phone_number_div" style="display:none">
            <label for="phone_number">Phone Number</label><br>
            <input name="phone_number" id="phone_number" style="height:50px;"/>
        </div>
        
        <div class="form-group" id="extension_div" style="display:none">
            <label for="mobile_app_number">Link Mobile Number to App</label><br>
            <input name="mobile_app_number" id="mobile_app_number" style="height:50px;"/>
        </div>
        
        <div class="table-responsive price_div" style="display:none;" id="price_div">
        <table class="table table-sm ">
        <tbody>
        <tr>
        <th scope="row" style="width:50%"><strong>Name</strong></th>    
        <th style="width:50%" class="text-right pr-2"><span id="line_name"></span></th>
        </tr>
        <tr>
        <th scope="row"><strong>Qty</strong></th>
        <td class="text-right pl-1 pr-2 py-0"> <input id="line_qty" value="1"/></td>
        </tr>
        <tr>
        <th scope="row"><strong>Prorata Price</strong></th>
        <td class="text-right pr-2">R <span id="line_price"></span></td>
        </tr>
        <tr>
        <th scope="row"><strong>Prorata Price Incl</strong></th>
        <td class="text-right pr-2">R <span id="line_price_incl"></span></td>
        </tr>
       
        </tbody>
        <tfooter>
       
        @if($reseller->id == 1 && $account->balance > 0)  
        <tr>
        <th scope="row"><strong>Outstanding Balance</strong></th>
        <td class="text-right pr-2">R <span id="balance">{{ currency($account->balance) }}</span></td>
        </tr>
        @endif
        <tr>
        <th scope="row"><strong>Prorata Total</strong></th>
        <td class="text-right pr-2">R <span id="total"></span></td>
        </tr>
        
        @if($reseller->vat_enabled)
        <tr>
        <th scope="row"><strong>Prorata Total Incl</strong></th>
        <td class="text-right pr-2">R <span id="total_vat"></span></td>
        </tr>
		@endif
		
		
        <tr class="monthly_total">
        <th scope="row"><strong>Monthly Total Thereafter</strong></th>
        <td class="text-right pr-2">R <span id="monthly_total"></span></td>
        </tr>
        
        @if($reseller->id == 1 && $account->balance > 0)
        <tr>
        <th scope="row"><strong>Grand Total Incl</strong></th>
        <td class="text-right pr-2">R <span id="grand_total"></span></td>
        </tr>
		@endif
        
        </tfooter>
        </table>
        </div>
		
		
		<div class="alert alert-info text-center" role="alert" id="funds_alert" style="display:none">
		    @if($reseller->id == 1)
		    <p>Insufficient funds to process order.</p>
		    <a id="paynowlink" class="e-btn e-info" href="#">Paynow via Instant EFT</a>
		    @else
            Insufficient funds to process order. Please contact support to process your order <a href="mailto:{{ $reseller->email }}">{{ $reseller->company }}</a>
            @endif
        </div>
    </div>
    <div class="card-footer text-right orderfooter py-4" style=" display:none;">
        <span id="processing_wait">Processing. Please wait...</span>
        <button type="submit" id="submitbtn" style="float:right" class="e-btn e-btn-large e-info py-2 dialogSubmitBtn">Buy Now</button>
    </div>

{!! Form::close() !!}
</div>

@endsection

@push('page-scripts')

    <script>
    
    var product_template = '<div class="row py-1 px-1 e-text-content ${if(is_category===1)}e-icon-wrapper${/if}" > ' +
    '<div class="row e-list-text d-flex justify-content-between align-items-center">' +
    '${if(image_url!=="")} <div class="col-auto img-div">' +
    '<img class="img-fluid" src="${image_url}" /> </div>' +
    '${/if}' +
    '<div class="col">${text}' +
    '${if(is_category===1 && subtext > "")} ' +
    '<span class="subtext">${subtext} </span>' +
    '${/if} ' +
    ' </div>' +
    '${if(is_category===1)} ' +
    '<div class="col-auto"> <div class="e-icons e-icon-collapsible"></div></div>' +
    '${/if} ' +
    '</div> </div>';
    
    
	product_select = new ej.lists.ListView({
		dataSource: {!! json_encode($products_datasource) !!},
        headerTitle: 'Catalogue',
        template: product_template,
        showHeader: true,
		select: function(args){
		//	//console.log(args);
			if(args.data){
				if(args.data.is_category == 0){
					add_to_cart(args.data.id);	
				}
			}
		}
    }, '#product_select');
    
    $(document).on('click','.e-but-back',function(){
       $("#ordercard").hide();
    });
    </script>
    
  
@if($reseller->enable_client_invoice_creation)

<script type="text/javascript">

window.numbers127 = {!! json_encode($numbers[127]) !!};
window.numbers128 = {!! json_encode($numbers[128]) !!};
window.numbers176 = {!! json_encode($numbers[176]) !!};

</script>
<script type="text/javascript">
 var line_qty = new ej.inputs.NumericTextBox({
    decimals: 0,
    format: "0",
 	min:1,
 	enableRtl: true,
 	change: function(){
 	    add_to_cart(selected_product.id);
 	 $("#qty").val(this.value);
 	},
 },"#line_qty");


grand_total = 0;
outstanding_balance = parseFloat(0);
@if($reseller->id == 1)
balance = parseFloat("{{ currency($account->balance) }}").toFixed(2);
@else
balance = parseFloat("{{ currency($reseller->balance) }}").toFixed(2);
@endif
if(balance > 0){
outstanding_balance = balance;
}

currency_symbol = "R";
var button = new ej.buttons.Button({ cssClass: `e-info`});
button.appendTo('#submitbtn');
$('.card-footer').hide();
$('#submitbtn').prop('disabled', true);


    function add_to_cart(product_id){
      $("#ordercard").show();
     
        selected_product = false;
        $(products).each(function(i,product) {
          
            if(product.id == product_id){
                selected_product = product;
            }
        });
      //console.log(selected_product);
      if(selected_product > ''){
                selected_product.qty = line_qty.value;
                $("#line_name").text(selected_product.code_title);
                
                phone_number_input.value = null;
                if(selected_product.provision_type == 'pbx_extension'){
                    line_qty.enabled = true;
                    $("#extension_div").show();
                    $("#phone_number_port_div").hide();
                    $("#phone_number_div").hide();
                }else if(selected_product.id == 126){
                    line_qty.enabled = false;
                    $("#phone_number_port_div").show();
                    $("#phone_number_div").hide();
                    $("#extension_div").hide();
                }else if(selected_product.id == 127){
                    line_qty.enabled = false;
                    phone_number_input.dataSource = window.numbers127;
                    $("#phone_number_div").show();
                    $("#phone_number_port_div").hide();
                    $("#extension_div").hide();
                }else if(selected_product.id == 128){
                    line_qty.enabled = false;
                    phone_number_input.dataSource = window.numbers128;
                    $("#phone_number_div").show();
                    $("#phone_number_port_div").hide();
                    $("#extension_div").hide();
                }else if(selected_product.id == 176){
                    line_qty.enabled = false;
                    phone_number_input.dataSource = window.numbers176;
                    $("#phone_number_div").show();
                    $("#phone_number_port_div").hide();
                    $("#extension_div").hide();
                }else{
                    line_qty.enabled = true;
                    $("#phone_number_div").hide();
                    $("#phone_number_port_div").hide();
                    $("#extension_div").hide();
                }
                $("#product_id").val(selected_product.id);
                $("#qty").val(selected_product.qty);
                
                var total = parseFloat(selected_product.price * selected_product.qty).toFixed(2);
                $("#total").text(total);
                var total_vat = total;
                var price_incl = selected_product.price;
                @if($reseller->vat_enabled)
                var price_incl = selected_product.price_tax;
                var total_vat = parseFloat(selected_product.price_tax * selected_product.qty).toFixed(2);
                $("#total_vat").text(total_vat);
                @endif
                
                
                
            
                $("#line_price").text(selected_product.price);
                $("#line_price_incl").text(price_incl);
                
               
                   
                grand_total = parseFloat(total_vat)+parseFloat(outstanding_balance);
                   
                grand_total = parseFloat(grand_total).toFixed(2);
                   
                @if($reseller->id == 1 && $account->balance > 0)
                $("#grand_total").text( grand_total);
                @endif
                
                
                if(balance > 0 || (balance <= 0 && parseFloat(Math.abs(balance)) < parseFloat(grand_total))){
                    setPayNowLink(grand_total);
                    $("#funds_alert").show();
                    $('.card-footer').hide();
                    $('#submitbtn').prop('disabled', true);
                }else{
                    $("#funds_alert").hide();
                    $('.card-footer').show();
                    $('#submitbtn').prop('disabled', false);
                }
               
                $(".price_div").show();
                $([document.documentElement, document.body]).animate({
                scrollTop: $("#price_div").offset().top
                }, 1000);
            }else{
                $(".price_div").hide();
                $("#phone_number_div").hide();
                $("#phone_number_port_div").hide();
            }
            
            if(selected_product.frequency != 'once off'){
                
                var monthly_total = parseFloat(selected_product.full_price_tax * selected_product.qty).toFixed(2);
                $('.monthly_total').show();
                $("#monthly_total").text(monthly_total);
            }else{
                $('.monthly_total').hide();
                $("#monthly_total").text(0);
            }
    }
    
    
   
    
$(document).ready(function() {
	products = {!! json_encode($products) !!};
    
	phone_number_input = new ej.dropdowns.DropDownList({
		fields: {groupBy: 'prefix', text: 'number', value: 'number'},
		placeholder: 'Choose Phone Number',
		allowFiltering: false,
	
	});
	
    phone_number_input.appendTo("#phone_number");
    
    phone_number_port = new ej.inputs.TextBox({
		placeholder: "Enter Phone Number to Port",
	});
    phone_number_port.appendTo('#phone_number_port');
    
    mobile_app_number = new ej.inputs.TextBox({
		placeholder: "Mobile Number for App",
	});
    mobile_app_number.appendTo('#mobile_app_number');
    
});

$('#apiOrder').on('submit', function(e) {
 	e.preventDefault();
    apiformSubmit("apiOrder");
});

function apiformSubmit(form_id, callback_function = false) {

    $('#' + form_id + ' :disabled').each(function(e) {
        $(this).removeAttr('disabled');
    })
    var form = $('#' + form_id);
    var formData = new FormData(form[0]);

    $('input[type=file]').each(function() {
        if ($(this).val() > '') {
            var ins = $(this)[0].files.length;
            if (ins > 1) {
                for (var x = 0; x < ins; x++) {
                    formData.append($(this)[0].name + "[]", $(this)[0].files[x]);
                }
            }
            else {
                formData.append($(this)[0].name, $(this)[0].files[0]);
            }
        }
    });

    if ($("#signature").length > 0) {
        var signature_name = $("#signature").attr('name');
        var signature = signaturePad.toDataURL();
        formData.append(signature_name, signature);
    }
    //console.log(form.attr('action'));
    $.ajax({
        method: "post",
        url: form.attr('action'),
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function(e) {
            try {
                showSpinnerContent();
                $('.dialogSubmitBtn').each(function(e) {
                    $(this).prop('disabled', true);
                });
            }
            catch (e) {}
        },
        success: function(data) {
            
            try {
                hideSpinnerContent();
            }
            catch (e) {}
            if(callback_function != false){
                callback_function(data);
            }
            $("#shop").hide();
            $("#ordercard").hide();
            if(data.status == "success"){
                $("#ordererror").hide();
                $("#orderwarning").hide();
                $("#ordersuccess").show();
            }
            if(data.status == "warning"){
                $("#orderwarning_msg").text(data.message); 
                $("#orderwarning").show();
                $("#ordererror").hide();
                $("#ordersuccess").hide();
            }
            if(data.status == "error"){
                $("#ordererror_msg").text(data.message); 
                $("#ordererror").show();
                $("#orderwarning").hide();
                $("#ordersuccess").hide();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $("#shop").hide();
            $("#ordercard").hide();
            $("#ordersuccess").hide();
            $("#orderwarning").hide();
            $("#ordererror_msg").text('Something went wrong, please try again later.');
            $("#ordererror").show();
            
            try {
                hideSpinner();
            }
            catch (e) {}
            processAjaxError(jqXHR, textStatus, errorThrown);
            
        },
    });
}

function apiprocessAjaxSuccess(data) {
   
    toastNotify(data.message, data.status);
}
</script>
<script>

function setPayNowLink(grand_total){
    var form_id = 'apiOrder';
     var form = $('#' + form_id);
    
   $.ajax({
        method: "post",
        url: 'https://{{ session('instance')->domain_name }}/api/getpaynowlink',
        data: {account_id: {{ $account->id }}, amount: grand_total},
        dataType : "json",
        success: function(data) {
            $("#paynowlink").attr('href', data.redirect_url);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            
        },
    });
}
    function showSpinnerContent(){
        $("#content").busyLoad("show", {
            animation: "slide"
        });
        $("#processing_wait").show();
    }
    
    function hideSpinnerContent(){
        $("#content").busyLoad("hide", {
            animation: "slide"
        });
        $("#processing_wait").hide();
    }
</script>
@endif
@endpush



@push('page-styles')

   
    <style>
#processing_wait{
    display:none;
}
.e-dialog .e-dlg-content {
    padding: 18px !important;    
    padding-top: 0px !important;
}

.table td, .table th {
    padding: .25rem;
}


body {
  background-color: $primary-color;
}  

.custom-column {  
  background-color: $col-bg-color;
  border: 5px solid $col-bg-color;    
  padding: 10px;
  box-sizing: border-box;  
}

.custom-column-header {
 background-color: #b7e0ff;
    color: #000;
    padding: 5px;

 
}


.card-header-main{    font-size: 20px;
    background-color: #b7e0ff;
    color: #393e46;
    font-weight: 500;
}

.custom-column-content {
  background-color: #fff;
  border: 2px solid #ccc;  
  padding: 20px;  
  min-height:86px;
}

.custom-column-footer {
  background-color: $col-footer-bg-color;   
  padding-top: 20px;
  text-align: center;
}


.orderfooter{
	display: flex;
width: 100%;
flex-wrap: wrap;
justify-content: flex-end;
}
  .e-numeric .e-input-group-icon.e-spin-up:before {
    content: "\e823";
    color: rgba(0, 0, 0, 0.54);
  }
  .e-numeric .e-input-group-icon.e-spin-down:before {
    content: "\e934";
    color: rgba(0, 0, 0, 0.54);
  }
  body,.e-control, .e-css{
        font-family: "Segoe UI", Arial, Sans-serif !important;
        src: url("https://kendo.cdn.telerik.com/2021.3.914/styles/fonts/DejaVu/DejaVuSans.ttf") format("truetype");
      font-size:4vw !important;
      font-weight: 400 !important;
  }

.e-listview {
    font-size: 4vw !important;
}
.e-listview .e-list-header {
    font-size:5vw !important;
}
.e-listview .e-list-header {
    height: auto !important;
}
.e-numeric.e-control-wrapper.e-input-group .e-input-group-icon{
    font-size:3vw !important;
}
.e-listview .e-but-back {
    padding-right: 2%;
}
.e-input-group-icon.e-spin-down{
    margin-left: 4% !important;
}
.e-listview .e-list-text {
    white-space: initial !important;
    overflow: initial !important;
}
.e-list-header{
    min-height:80px;
}
.e-listview .e-icon-collapsible{
    font-size:4vw !important;
}
.e-listview:not(.e-list-template) .e-list-item {
    height: 100%;
    line-height: 100% !important;
    padding: 0;
    border: 1px solid #fff;
}
.e-listview .e-list-header {
   
    font-weight: 400;
}
.e-input-group input.e-input, .e-float-input.e-input-group input, .e-input-group.e-control-wrapper input.e-input, .e-float-input.e-input-group.e-control-wrapper input, .e-float-input input, .e-float-input.e-control-wrapper input {
    min-height: 5px !important;
}
.e-input-group .e-input-group-icon, .e-input-group.e-control-wrapper .e-input-group-icon {
    min-height:5px !important;
}

element.style {
}
.e-input-group:not(.e-success):not(.e-warning):not(.e-error):not(.e-float-icon-left), .e-input-group.e-float-icon-left:not(.e-success):not(.e-warning):not(.e-error) .e-input-in-wrap, .e-input-group.e-control-wrapper:not(.e-success):not(.e-warning):not(.e-error):not(.e-float-icon-left), .e-input-group.e-control-wrapper.e-float-icon-left:not(.e-success):not(.e-warning):not(.e-error) .e-input-in-wrap, .e-float-input.e-float-icon-left:not(.e-success):not(.e-warning):not(.e-error) .e-input-in-wrap, .e-float-input.e-control-wrapper.e-float-icon-left:not(.e-success):not(.e-warning):not(.e-error) .e-input-in-wrap {
    border-color: rgba(0,0,0,0.42);
}
.e-input-group:not(.e-float-icon-left), .e-input-group.e-success:not(.e-float-icon-left), .e-input-group.e-warning:not(.e-float-icon-left), .e-input-group.e-error:not(.e-float-icon-left), .e-input-group.e-control-wrapper:not(.e-float-icon-left), .e-input-group.e-control-wrapper.e-success:not(.e-float-icon-left), .e-input-group.e-control-wrapper.e-warning:not(.e-float-icon-left), .e-input-group.e-control-wrapper.e-error:not(.e-float-icon-left) {
    border: 1px solid;
    border-width: 0 0 1px 0;
}
.e-input-group:not(.e-float-icon-left), .e-input-group.e-control-wrapper:not(.e-float-icon-left) {
    border-bottom: 1px solid;
}
.e-input.e-rtl, .e-input-group.e-rtl, .e-input-group.e-control-wrapper.e-rtl {
    direction: rtl;
}
.e-input-group, .e-input-group.e-control-wrapper {
    border-bottom-color: rgba(0,0,0,0.42);
}
.e-input-group, .e-input-group.e-control-wrapper, .e-float-input, .e-float-input.e-input-group, .e-float-input.e-control-wrapper, .e-float-input.e-input-group.e-control-wrapper {
    background: transparent;
    color: rgba(0,0,0,0.87);
}
.e-input-group, .e-input-group.e-control-wrapper {
    position: relative;
    width: 100%;
}
.e-input-group, .e-input-group.e-control-wrapper {
    display: -ms-inline-flexbox;
    display: inline-flex;
    vertical-align: middle;
}
input.e-input, .e-input-group input.e-input, .e-input-group input, .e-input-group.e-control-wrapper input.e-input, .e-input-group.e-control-wrapper input, .e-float-input input, .e-float-input.e-input-group input, .e-float-input.e-control-wrapper input, .e-float-input.e-control-wrapper.e-input-group input, .e-input-group, .e-input-group.e-control-wrapper, .e-float-input, .e-float-input.e-control-wrapper {
    border-radius: 0;
}
input.e-input, textarea.e-input, .e-input-group, .e-input-group.e-control-wrapper {
    font-family: "Roboto","Segoe UI","GeezaPro","DejaVu Serif","sans-serif","-apple-system","BlinkMacSystemFont";
    font-size: 13px;
    font-weight: 400;
}
.e-input-group, .e-input-group.e-control-wrapper {

    margin-bottom: 0px;
}
#product_select .e-list-item{
    background-color: #333a42 !important;
    color: #fff !important;
}
#product_select .e-list-header{
    background-color: #16191C !important;
    color: #fff !important;
}

#container, #page-wrapper, #ordercard, #ordercard .table{
    background-color: #333a42 !important;
    color: #fff !important;
}
.e-listview .e-icons {
    color: #fff !important;
}

input {
    color: #fff !important;
}

#product_select .e-list-item .subtext{
    font-size:3vw !important;
    display:block;
    color:#6D7484;
}
.e-numeric .e-input-group-icon.e-spin-up:before,.e-numeric .e-input-group-icon.e-spin-down:before{
    color:#fff;
}
.img-fluid{
    max-height:200px;    
}

.img-div{
    width: 14rem;
    padding: 0.5rem 1rem;
    text-align: center;   
}
</style>
@endpush