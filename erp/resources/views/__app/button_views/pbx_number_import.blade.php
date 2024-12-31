{!! Form::open(array("url"=> "pbx_number_import", "class"=>"form-horizontal","id"=> "pbx_number_import")) !!}	
<input type="hidden" name="conn" value="{{ $conn }}">

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Import Phone Numbers
            </div>
            <div class="card-body">
                
                <div class="row">
                  
                    <div class="col-md-12">
                        <input id="number_from" />
                    </div>
                </div>
                <div class="row">
                  
                    <div class="col-md-12">
                        <input id="number_to" />
                    </div>
                </div>
                <div class="row">
                  
                    <div class="col-md-12">
                        <input id="provider" />
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
	
    number_from = new ej.inputs.TextBox({
    	placeholder: "Number range start",
    	floatLabel: 'auto'
    });
    number_from.appendTo("#number_from");
    
    number_to = new ej.inputs.TextBox({
    	placeholder: "Number range end",
    	floatLabel: 'auto'
    });
    number_to.appendTo("#number_to");
    
    provider = new ej.dropdowns.DropDownList({
        placeholder: 'Select supplier',
        fields: {value: 'gateway_uuid',text:'gateway'},
        dataSource: {!! json_encode($gateways) !!}
    });
    provider.appendTo('#provider');
});   
   

$('#pbx_number_import').on('submit', function(e) {
	e.preventDefault();
   formSubmit("pbx_number_import");
   
});
</script>