{!! Form::open(array("url"=> "pbx_test_call", "class"=>"form-horizontal","id"=> "pbx_test_call")) !!}	

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
               PBX Test Call
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="outbound_caller_id">  Outbound Caller Id </label>
                    </div>
                    <div class="col-md-9">
                        <input id="outbound_caller_id" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="number_to_call">  Number to call </label>
                    </div>
                    <div class="col-md-9">
                        <input id="number_to_call" />
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>


{!! Form::close() !!}

<script type="text/javascript">

    outbound_caller_id = new ej.inputs.TextBox({
        enabled: false,
        value: '{{ $outbound_caller_id }}'
    });
    outbound_caller_id.appendTo('#outbound_caller_id');
    
    number_to_call = new ej.inputs.TextBox({
    });
    number_to_call.appendTo('#number_to_call');

$('#pbx_test_call').on('submit', function(e) {
    e.preventDefault();
    formSubmit("pbx_test_call");
});
</script>