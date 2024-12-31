{!! Form::open(array("url"=> "lte_simswop", "class"=>"form-horizontal","id"=> "lte_simswop")) !!}	
<input type="hidden" name="id" value="{{$id}}" />
<div class="row mt-3 p-2">
    <div class="col">
        <div class="card">
            <div class="card-header">
              LTE Sim Swop
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="current_lte"> Current LTE </label>
                    </div>
                    <div class="col-md-9">
                        <input name='current_lte' id='current_lte'/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="new_lte"> New LTE </label>
                    </div>
                    <div class="col-md-9">
                        <input name='new_lte' id='new_lte'/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="new_lte"> Confirmed date </label>
                    </div>
                    <div class="col-md-9">
                        <input name='confirmed_date' id='confirmed_date'/>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

<script type="text/javascript">

	current_lte = new ej.inputs.TextBox({
		placeholder: "Current LTE",
		value: '{!! $lte->msisdn !!}',
		floatLabel: 'auto',
		enabled: false, 
	});
	current_lte.appendTo("#current_lte");
	
	new_lte = new ej.inputs.TextBox({
		placeholder: "New LTE",
		floatLabel: 'auto'
	});
	new_lte.appendTo("#new_lte");
	
    confirmed_date =  new ej.calendars.DatePicker({
		format: 'yyyy-MM-dd',
	    placeholder: 'Confirm Date',
	});
    confirmed_date.appendTo('#confirmed_date');


$('#lte_simswop').on('submit', function(e) {
	e.preventDefault();
   formSubmit("lte_simswop");
   
});
</script>