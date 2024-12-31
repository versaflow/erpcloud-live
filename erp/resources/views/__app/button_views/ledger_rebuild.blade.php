{!! Form::open(array("url"=> "ledger_rebuild", "class"=>"form-horizontal","id"=> "ledger_rebuild")) !!}	
<input type="hidden" name="id" value="{{$id}}"/>
<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Ledger Rebuild
            </div>
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="ledger_date"> Date </label>
                    </div>
                    <div class="col-md-9">
                        <input id="ledger_date" />
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
    
    ledger_date = new ej.dropdowns.DropDownList({
        dataSource: {!! json_encode($months) !!},
        showClearButton: true,
        ignoreAccent: true,
        popupHeight: '200px',
     
    });
    ledger_date.appendTo('#ledger_date');
});

$('#ledger_rebuild').on('submit', function(e) {
    e.preventDefault();
    formSubmit("ledger_rebuild");
});
</script>