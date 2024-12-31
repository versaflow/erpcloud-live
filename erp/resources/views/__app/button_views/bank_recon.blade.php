{!! Form::open(array("url"=> "bank_recon", "class"=>"form-horizontal","id"=> "bankRecon")) !!}	
<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Reconciliation 
            </div>
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="statement_balance"> Balance as per Bank Account</label>
                    </div>
                    <div class="col-md-9">
                        <input id="statement_balance" />
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
                    <label for="statement_file"> File </label>
                    </div>
                    <div class="col-md-9">
                        <input id="statement_file" />
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
    
    var statement_file = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: false,
    });
    statement_file.appendTo("#statement_file");
    
    statement_date =  new ej.calendars.DatePicker({
		format: 'yyyy-MM-dd',
	    placeholder: 'Statement Date',
	    value: '{{$docdate}}',
	});
    statement_date.appendTo('#statement_date');
    
    statement_balance = new ej.inputs.NumericTextBox({
        format: 'R ###########.##',
        showSpinButton: false,
        decimals: 2,
        value: 0,
	    placeholder: 'Statement Balance',
    });
    statement_balance.appendTo('#statement_balance');
});

$('#bankRecon').on('submit', function(e) {
    e.preventDefault();
    formSubmit("bankRecon");
});
</script>