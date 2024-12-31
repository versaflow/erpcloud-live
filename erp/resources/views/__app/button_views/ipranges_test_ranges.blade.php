{!! Form::open(array("url"=> "ipranges_test_ranges_update", "class"=>"form-horizontal","id"=> "ipranges_test_ranges_form")) !!}	
<input type="hidden" name="conn" value="{{ $conn }}">

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Update Test Ranges
            </div>
            <div class="card-body">
                
                <div class="row">
                  
                    <div class="col-md-12">
                        <input id="account_id" />
                    </div>
                </div>
                <div class="row">
                  
                    <div class="col-md-12">
                        <input id="gateway" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div ref="component" class="field form-group has-feedback formio-component formio-component-button formio-component-submit float-right mr-2 form-group" >
<button lang="en" type="submit"  class="btn btn-primary float-right mr-2 d-none" ref="button">
Submit
</button>
</div>
{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
	
 
    
    gateway = new ej.dropdowns.DropDownList({
        placeholder: 'Select gateway',
        fields: {value: 'value',text:'text'},
        dataSource: {!! json_encode($gateways) !!},
        allowFiltering: true,
        popupHeight: '200px',
        showClearButton: true,
    });
    gateway.appendTo('#gateway');
    
    account_id = new ej.dropdowns.DropDownList({
        placeholder: 'Select account_id',
        fields: {value: 'value',text:'text'},
        dataSource: {!! json_encode($account_ids) !!},
        allowFiltering: true,
        popupHeight: '200px',
        showClearButton: true,
    });
    account_id.appendTo('#account_id');
});   
   

$('#ipranges_test_ranges_form').on('submit', function(e) {
    e.preventDefault();
    formSubmit("ipranges_test_ranges_form");
   
});
</script>