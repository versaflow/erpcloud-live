@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif
@section('content')

<div id="page-wrapper" class="container mx-auto ">

<div id="doc_tabs"></div>
@if($show_delivery)
<div id="delivery_tab" style="display:none">
    <div class="card mt-3">
        <div class="card-body">
            <input id="delivery" name="delivery" />
            <input id="address" name="address" />
            <button type="button" id="deliverysave" >Save</button>
        </div>
    </div>
</div>
@endif

<div id="payment_tab" style="display:none">
    <div class="card mt-3">
        <div class="card-body">
            <button type="button" id="paynow" >Pay Now</button>
            <button type="button" id="paylater" >Pay Later</button>
        </div>
    </div>
</div>


</div>
@endsection
@push('page-scripts')
<script>

   $(document).off('click','#deliverysave').on('click','#deliverysave',function(){
        
            $.ajax({
               url: '/update_doc_delivery',
               data: {doc_id: '{{$doc->id}}', account_id: '{{$doc->account_id}}', address: address.value, delivery: delivery.value},
               type: 'post',
               success: function(data){
                   processAjaxSuccess(data);
               }
            });
   });
   $(document).off('click','#paylater').on('click','#paylater',function(){
         window.open("{{$documents_url}}", "_self");
   });
   $(document).off('click','#paynow').on('click','#paynow',function(){
        viewDialog('make_payment','/paynow/{{$paynow_link}}', 'Make a Payment','60%','80%','none');
   });
    
    
    var doc_tabs = new ej.navigations.Tab({
        items: [
            @if($show_delivery)
            { header: { 'text': 'Delivery Options' }, content: '#delivery_tab'},
            @endif
            { header: { 'text': 'Payment Options' }, content: '#payment_tab'}
        ],
    	selectedItem: 0,
    });
    doc_tabs.appendTo('#doc_tabs');
    
    $(function() {
        
    @if($show_delivery)   
    
        address = new ej.inputs.TextBox({
            placeholder: "Address ",
            floatLabelType: 'Auto',
            value: '{{$address}}',
        });
        address.appendTo("#address");
    
        delivery =  new ej.dropdowns.DropDownList({
            dataSource: {!! json_encode($delivery_options) !!},
            placeholder: "Delivery Type ",
            floatLabelType: 'Auto',
            value: '{{$doc->delivery}}',
        });
        delivery.appendTo("#delivery");
        
        var deliverysave = new ej.buttons.Button({
            cssClass: 'e-primary',
        });
        deliverysave.appendTo('#deliverysave');
        
        
    @endif
        
        var paynowbutton = new ej.buttons.Button({
            cssClass: 'e-primary',
        });
        paynowbutton.appendTo('#paynow');
        
        var paylaterbutton = new ej.buttons.Button({
            cssClass: 'e-info',
        });
        paylaterbutton.appendTo('#paylater');
    });
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
		margin-top:3%;
		margin-bottom:3%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
}
</style>
@endpush