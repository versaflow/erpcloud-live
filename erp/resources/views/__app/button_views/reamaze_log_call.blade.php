{!! Form::open(array("url"=> "reamaze_log_call", "class"=>"form-horizontal","id"=> "reamaze_log_call")) !!}	

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
               {{$form_title}}
            </div>
            <div class="card-body">
                
              <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="direction">  Direction </label>
                    </div>
                    <div class="col-md-9">
                        <input id="direction" />
                    </div>
                </div>
                
              <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="from_number">  From Number </label>
                    </div>
                    <div class="col-md-9">
                        <input id="from_number" />
                    </div>
                </div>
                
              <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="to_number">  To Number </label>
                    </div>
                    <div class="col-md-9">
                        <input id="to_number" />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="description">  Description </label>
                    </div>
                    <div class="col-md-9">
                    <textarea id="description" name="description" rows="5" class='e-input'></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{!! Form::close() !!}

<script type="text/javascript">

    to_number = new ej.inputs.TextBox({
    	placeholder: "To number",
    	floatLabel: 'auto'
    });
    to_number.appendTo("#to_number");
    
    from_number = new ej.inputs.TextBox({
    	placeholder: "From number",
    	floatLabel: 'auto'
    });
    from_number.appendTo("#from_number");
     

    direction = new ej.dropdowns.DropDownList({
        placeholder: 'Select direction',
        dataSource: {!! json_encode(['Inbound','Outbound']) !!}
    });
    direction.appendTo('#direction');
    
    description = new ej.inputs.TextBox({
		floatLabelType: 'Auto',
    });
    description.appendTo("#description");

$('#reamaze_log_call').on('submit', function(e) {
	e.preventDefault();
   formSubmit("reamaze_log_call");
   
});
</script>