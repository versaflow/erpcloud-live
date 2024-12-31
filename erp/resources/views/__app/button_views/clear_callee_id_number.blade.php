{!! Form::open(array("url"=> "clear_callee_id_number", "class"=>"form-horizontal","id"=> "clear_callee_id_number")) !!}	
<div class="row mt-3 p-2">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="callee_id_number"> Callee ID Number </label>
                    </div>
                    <div class="col-md-9">
                        <input name='callee_id_number' id='callee_id_number'/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

<script type="text/javascript">

	callee_id_number = new ej.inputs.TextBox({
		placeholder: "Callee ID Number",
		floatLabel: 'auto',
		enabled: true, 
	});
	callee_id_number.appendTo("#callee_id_number");


$('#clear_callee_id_number').on('submit', function(e) {
    e.preventDefault();
    formSubmit("clear_callee_id_number");
});
</script>