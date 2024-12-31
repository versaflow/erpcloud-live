{!! Form::open(array("url"=> "cash_recon", "class"=>"form-horizontal","id"=> "bankRecon")) !!}	
<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Statement 
            </div>
            <div class="card-body">
                
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="statement_file"> File </label>
                    </div>
                    <div class="col-md-9">
                        <input id="statement_file" />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="statement_date"> Date </label>
                    </div>
                    <div class="col-md-9">
                        <input id="statement_date" />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="statement_balance"> Balance </label>
                    </div>
                    <div class="col-md-9">
                        <input id="statement_balance" />
                    </div>
                </div>
                
                @if(check_access('1,31'))
                    <div class="row mt-3" >
                        <label for="manager_override" class=" control-label col-md-3 text-left">Manager Override </label>
                        <div class="col-md-9">
                            <input name="manager_override" id="manager_override" type="checkbox" value="1" >
                        
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
    
    @if(check_access('1,31'))
    var checkbox = { label: 'Manager Override.' };
	var manager_override = new ej.buttons.Switch(checkbox);
	manager_override.appendTo("#manager_override");
    @endif
    var statement_file = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: false,
    });
    statement_file.appendTo("#statement_file");
    
    statement_date =  new ej.calendars.DatePicker({
		format: 'yyyy-MM-dd',
	    floatLabelType: 'Auto',
	    placeholder: 'Statement Date',
	    value: '{{$trx->docdate}}'
	});
    statement_date.appendTo('#statement_date');
    
    statement_balance = new ej.inputs.NumericTextBox({
	    floatLabelType: 'Auto',
        format: 'R ###########.##',
        showSpinButton: false,
        decimals: 2,
        value: 0,
	    placeholder: 'Statement Balance',
	    value: '{{$trx->balance}}'
    });
    statement_balance.appendTo('#statement_balance');
});

$('#bankRecon').on('submit', function(e) {
    e.preventDefault();
    formSubmit("bankRecon");
});
</script>