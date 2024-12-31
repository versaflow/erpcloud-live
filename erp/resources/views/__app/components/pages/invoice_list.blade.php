@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif
@section('content')

@if($verify_auth)
<div id="page-wrapper" class="container mx-auto text-center">
<div class="col mt-3">
{!! Form::open(array("url"=> "accountant_access", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "invoiceListAuth")) !!}		
<div class="card mt-2" >
    <div class="card-header">Accountant Login</div>
    <div class="card-body">
    <input  name="password" id="password" />
</div>
</div>
{!! Form::close() !!}
</div>
</div>

@else
<div id="accordioncontainer" class="container mx-auto" style="display:none">
<div class="row mt-3">
    
    <div class="col">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col">
                        Supplier Documents
                    </div>
                    <div class="col text-right">
                        <a href="{{ url('accountant_access?logout=1') }}" class="text-right e-btn">Logout</a><br>
                    </div>
                </div>
            </div>
            <div class="card-body">
  
        <div id="supplieraccordion">
        @foreach($supplier_invoice_months as $key => $invoice_month)
        <div> 
            <div> 
            
            <div>{{ $invoice_month }}</div> 
            
            </div>
            
            <div> 
            <div> 
               
            <div class="table-responsive">
            <table class="table table-border table-bordered">
            <thead>
            <tr>
            <th>ID #</th>
            <th>Company</th>
            <th>Date</th>
            <th>Total</th>
            <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($supplier_invoices[$key] as $invoice)
            <tr>
            <td>{{$invoice->doctype}} #{{$invoice->id}}</td>
            <td>{{ $invoice->company }}</td>
            <td>{{date('Y-m-d',strtotime($invoice->docdate))}}</td>
            <td>{{ currency($invoice->total) }}</td>
            <td><a href="{{ url('download_invoice/supplier_documents/'.$invoice->id) }}" target="_blank">Download</a></td>
            </tr>
            @endforeach
            </tbody>
            </table>
            </div>
               
               
            </div>
            </div>
            </div>
        @endforeach
        </div>
              
                
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                General Journals
            </div>
            <div class="card-body">
                 <div id="ledgeraccordion">
        @foreach($ledger_invoice_months as $key => $invoice_month)
        <div> 
            <div> 
            
            <div>{{ $invoice_month }}</div> 
            
            </div>
            
            <div> 
            <div> 
               
            <div class="table-responsive">
            <table class="table table-border table-bordered">
            <thead>
            <tr>
            <th>ID #</th>
            <th>Date</th>
            <th>Dedit Account</th>
            <th>Credit Account</th>
            <th>Date</th>
            <th>Total</th>
            <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($ledger_invoices[$key] as $invoice)
            <tr>
            <td>{{$invoice->doctype}} #{{$invoice->id}}</td>
            <td>{{date('Y-m-d',strtotime($invoice->docdate))}}</td>
            <td>{{ $invoice->debit_account }}</td>
            <td>{{ $invoice->credit_account }}</td>
            <td>{{ currency($invoice->total) }}</td>
            <td><a href="{{ url('download_invoice/general_journal/'.$invoice->id) }}" target="_blank">Download</a></td>
            </tr>
            @endforeach
            </tbody>
            </table>
            </div>
               
               
            </div>
            </div>
            </div>
        @endforeach
              
                
            </div>
        </div>
    </div>
</div>

</div>
@endif

@endsection

@push('page-scripts')

@if($verify_auth)

<script type="text/javascript">

$(document).ready(function() {

password = new ej.inputs.TextBox({
	placeholder: "Password ",
    floatLabelType: 'Auto',
    type: 'password',
});
password.appendTo("#password");
});
$('#invoiceListAuth').on('submit', function(e) {
 	e.preventDefault();
    formSubmit("invoiceListAuth");
});
</script>

@else

<script type="text/javascript">
$(function(){
   $("#accordioncontainer").show(); 
});
    var supplieraccordion = new ej.navigations.Accordion({});
    //Render initialized Accordion component
    supplieraccordion.appendTo('#supplieraccordion');
    
    var ledgeraccordion = new ej.navigations.Accordion({});
    //Render initialized Accordion component
    ledgeraccordion.appendTo('#ledgeraccordion');
</script>

@endif
@endpush

@push('page-styles')


<style>
@if(!request()->ajax())
body{
background-color: #f7f7f7;
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