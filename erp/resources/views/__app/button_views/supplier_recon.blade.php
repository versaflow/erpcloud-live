{!! Form::open(array("url"=> "supplier_recon", "class"=>"form-horizontal","id"=> "supplier_recon")) !!}	
<div class="row mt-3">
    <input type="hidden" name="id" value="{{$id}}" />
    <div class="col">
        <div class="card">
            <div class="card-header">
                Statement 
            </div>
            <div class="card-body">
                
                
                <div class="row">
                    <div class="col-md-12">
                    <label for="statement_file"> Statement File </label>
                    </div>
                    <div class="col-md-12">
                        <input id="statement_file" />
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12 ">
                    <label for="statement_balance"> Statement Balance from statement file uploaded above</label>
                    </div>
                    <div class="col-md-12">
                        <input id="statement_balance" />
                    </div>
                </div>
                @if(check_access('1,31'))
                    <div class="row mt-3" >
                    <div class="col-md-12 ">
                        <label for="manager_override">Manager Override </label>
                    </div>
                        <div class="col-md-12">
                            <input name="manager_override" id="manager_override" type="checkbox" value="1" >
                        
                        </div>
                    </div>
                @endif
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
    
    var statement_file = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: false,
    });
    statement_file.appendTo("#statement_file");
    
    @if(check_access('1,31'))
    var checkbox = { label: 'Manager Override.' };
	var manager_override = new ej.buttons.Switch(checkbox);
	manager_override.appendTo("#manager_override");
    @endif
    
    
    statement_balance = new ej.inputs.NumericTextBox({
	  
        format: 'R ###########.##',
        showSpinButton: false,
        decimals: 2,
        value: 0,
	    placeholder: 'Statement Balance',
    });
    statement_balance.appendTo('#statement_balance');
});

$('#supplier_recon').on('submit', function(e) {
    e.preventDefault();
    formSubmit("supplier_recon");
});
</script>