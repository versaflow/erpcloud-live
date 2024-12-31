{!! Form::open(array("url"=> "cashbook_allocate", "class"=>"form-horizontal","id"=> "linkPayments")) !!}	
<input type="hidden" name="bank_id" value="{{ $bank->id }}">

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Transaction Details
            </div>
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="trx_date"> Date </label>
                    </div>
                    <div class="col-md-9">
                        <input id="trx_date" />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="trx_total"> Total </label>
                    </div>
                    <div class="col-md-9">
                        <input id="trx_total" />
                    </div>
                </div>
               
                
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Reference
            </div>
            <div class="card-body">
               
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="trx_reference"> Transaction Reference </label>
                    </div>
                    <div class="col-md-9">
                        <input id="trx_reference" />
                    </div>
                </div>
               
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label> Saved Reference (Auto allocate future transactions)</label>
                    </div>
                    <div class="col-md-9">
                        <input id="reference_match" />
                        <input id="reference_match_id" type="hidden" value="{{$reference_match_id}}"/>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>


<div class="row mt-3" id="new_receipt">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Create new receipt
            </div>
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="account_id"> Customer Account </label>
                    </div>
                    <div class="col-md-9">
                        <input id="account_id" />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="supplier_id"> Supplier Account </label>
                    </div>
                    <div class="col-md-9">
                        <input id="supplier_id" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="ledger_account_id"> Ledger Account </label>
                    </div>
                    <div class="col-md-9">
                        <input id="ledger_account_id" />
                    </div>
                </div>
                
                <div class="row" id="control_account_div">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="control_account_id"> Control Account </label>
                    </div>
                    <div class="col-md-9">
                        <input id="control_account_id" />
                    </div>
                </div>
                @if($vat_expense)
                <div class="row" id="vat_invoice_div">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="vat_invoice"> Vat Invoice </label>
                    </div>
                    <div class="col-md-9">
			            <input name="vat_invoice" id="vat_invoice" type="checkbox" value="1" >
                    </div>
                </div>
                @endif
                
            </div>     
        </div>
    </div>
</div>

<div class="row mt-3" id="ledger_div">
    <div class="col">
        <div class="card">
            <div class="card-header">
              Ledger Account Invoice
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="invoice_file"> Invoice File </label>
                    </div>
                    <div class="col-md-9">
                        <input name='invoice_file' id='invoice_file'/>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
<div ref="component" class="field form-group has-feedback formio-component formio-component-button formio-component-submit float-right mr-2 form-group" >
<button lang="en" type="submit"  class="btn btn-primary float-right mr-2" ref="button">
Submit
</button>
</div>
{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
		
    
	ej.base.enableRipple(true);
    $("#ledger_div").hide();
    $("#control_account_div").hide();
    
    @if($vat_expense)
    $("#vat_invoice_div").hide();
    @endif
    
	
  
  
    @if($bank->total > 0)
        ledger_datasource =  {!! json_encode($ledgers) !!};
    @else
        ledger_datasource = {!! json_encode($ledgers_expenses) !!};
    @endif
    
	ej.base.enableRipple(true);
    
    trx_date = new ej.inputs.TextBox({
		placeholder: "Date",
        readonly: true,
        value: '{{ $bank->docdate }}',
	});
    trx_date.appendTo('#trx_date');
    
    trx_reference = new ej.inputs.TextBox({
		placeholder: "Reference",
        readonly: true,
        value: "{{ str_replace(PHP_EOL,'',$bank->reference) }}",
	});
    trx_reference.appendTo('#trx_reference');
    
    reference_match = new ej.inputs.TextBox({
		placeholder: "Reference Match",
		@if(!empty($reference_match))
        value: "{{ $reference_match }}",
		@else
        value: "",
        @endif
	});
    reference_match.appendTo('#reference_match');
    
    
    trx_total = new ej.inputs.NumericTextBox({
        format: 'R ###########.##',
        showSpinButton: false,
        decimals: 2,
        readonly: true,
        value: '{{ $bank->total }}',
    });
    trx_total.appendTo('#trx_total');
    
    var invoice_file = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: false,
    });
    invoice_file.appendTo("#invoice_file");
    
    accounts = new ej.dropdowns.DropDownList({
		fields: {groupBy: 'type', text: 'company', value: 'id', type: 'type'},
        placeholder: 'Select customer account',
        dataSource: {!! json_encode($accounts) !!},
        showClearButton: true,
        ignoreAccent: true,
        allowFiltering: true,
        popupHeight: '200px',
        filtering: function(e){
        if(e.text == ''){
        e.updateData(accounts.dataSource);
        }else{ 
        var query = new ej.data.Query().select(['company','id','type']);
        query = (e.text !== '') ? query.where('company', 'contains', e.text, true) : query;
        e.updateData(accounts.dataSource, query);
        }
        },
        created: function(e){
            @if(!empty($bank->account_id))
            setTimeout(function(){
            accounts.value= {{$bank->account_id}};
            accounts.dataBind();
            }, 500);
            @endif
        },
        change: function(){
            if(accounts.value > ''){
                suppliers.value = 0;
                suppliers.dataBind();
                ledgers.value = 0;
                ledgers.dataBind();
            }
        }
    });
    accounts.appendTo('#account_id');
    
    ledgers = new ej.dropdowns.DropDownList({
		fields: {groupBy: 'category', text: 'name', value: 'id'},
        placeholder: 'Select ledger account',
        dataSource: ledger_datasource,
        showClearButton: true,
        ignoreAccent: true,
        allowFiltering: true,
        popupHeight: '200px',
        filtering: function(e){
        if(e.text == ''){
        e.updateData(ledgers.dataSource);
        }else{ 
        var query = new ej.data.Query().select(['name','id','taxable','category']);
        query = (e.text !== '') ? query.where('name', 'contains', e.text, true) : query;
        e.updateData(ledgers.dataSource, query);
        }
        },
        created: function(e){
            @if(!empty($bank->ledger_account_id))
            setTimeout(function(){
            ledgers.value= {{$bank->ledger_account_id}};
            ledgers.dataBind();
            }, 500);
            @endif
        },
        change: function(e){
            //console.log(ledgers.value);
           
            if(ledgers.value > ''){
                
                @if($vat_expense)
                $("#vat_invoice_div").show();
                @endif
                $("#ledger_div").show();
                //accounts.enabled = false;
                //suppliers.enabled = false;
                suppliers.value = 0;
                suppliers.dataBind();
                accounts.value = 0;
                accounts.dataBind();
            }else{
                
                @if($vat_expense)
                $("#vat_invoice_div").hide();
                @endif
                $("#ledger_div").hide();
                //accounts.enabled = true;
                //suppliers.enabled = true;
            }
            if(ledgers.value == 57){
                $("#control_account_div").show();
            }else{
                $("#control_account_div").hide();
            }
            
        },
    });
    ledgers.appendTo('#ledger_account_id');
    
    
    
    control_accounts = new ej.dropdowns.DropDownList({
		fields: {groupBy: 'category', text: 'name', value: 'id'},
        placeholder: 'Select control account',
        dataSource: {!! json_encode($control_accounts) !!},
        showClearButton: true,
        ignoreAccent: true,
        allowFiltering: true,
        popupHeight: '200px',
        filtering: function(e){
        if(e.text == ''){
        e.updateData(control_accounts.dataSource);
        }else{ 
        var query = new ej.data.Query().select(['name','id','taxable','category']);
        query = (e.text !== '') ? query.where('name', 'contains', e.text, true) : query;
        e.updateData(control_accounts.dataSource, query);
        }
        },
        created: function(e){
            @if(!empty($bank->control_account_id))
            setTimeout(function(){
            control_accounts.value= {{$bank->control_account_id}};
            control_accounts.dataBind();
            }, 500);
            @endif
        },
        change: function(e){
            
        },
    });
    control_accounts.appendTo('#control_account_id');
    
    @if($vat_expense)
    var checkbox = { label: 'Vat Invoice',checked:true };
    var vat_invoice = new ej.buttons.Switch(checkbox);
    vat_invoice.appendTo("#vat_invoice");
    @endif
    
    suppliers = new ej.dropdowns.DropDownList({
		fields: {text: 'company', value: 'id'},
        placeholder: 'Select supplier account',
        dataSource: {!! json_encode($suppliers) !!},
        showClearButton: true,
        ignoreAccent: true,
        allowFiltering: true,
        popupHeight: '200px',
        filtering: function(e){
        if(e.text == ''){
        e.updateData(suppliers.dataSource);
        }else{ 
        var query = new ej.data.Query().select(['company','id']);
        query = (e.text !== '') ? query.where('company', 'contains', e.text, true) : query;
        e.updateData(suppliers.dataSource, query);
        }
        },
        created: function(e){
            @if(!empty($bank->supplier_id))
            setTimeout(function(){
            suppliers.value= {{$bank->supplier_id}};
            suppliers.dataBind();
            }, 500);
            @endif
        },
        change: function(){
            if(suppliers.value > ''){
                ledgers.value = 0;
                ledgers.dataBind();
                accounts.value = 0;
                accounts.dataBind();
            }
        }
    });
    suppliers.appendTo('#supplier_id');
    
    
});
$('#linkPayments').on('submit', function(e) {
	e.preventDefault();
   formSubmit("linkPayments");
   
});
</script>
<style>
  #trx_reference{
      background-color: #ccc !important;
  }
</style>