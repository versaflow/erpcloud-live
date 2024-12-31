{!! Form::open(array("url"=> "process_billing", "class"=>"form-horizontal","id"=> "process_billing")) !!}	

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Monthly Billing
            </div>
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="billing_date">  Billing Date </label>
                    </div>
                    <div class="col-md-9">
                        <input id="billing_date" />
                    </div>
                </div>
                
                @if(!empty($service_balances))
                @foreach($service_balances as $k => $v)
              
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="{{$k}}">  {{ ucwords(str_replace('_',' ',$k)) }} </label>
                    </div>
                    <div class="col-md-9">
                        <input id="{{$k}}" />
                    </div>
                </div>
                @endforeach
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

  

    billing_date =  new ej.calendars.DatePicker({
		format: 'yyyy-MM-dd',
	    placeholder: 'Billing Date',
	    value: '{!! $docdate !!}'
	});
    billing_date.appendTo('#billing_date');
    
      @if(!empty($service_balances))
        @foreach($service_balances as $k => $v)
        {{$k}} = new ej.inputs.NumericTextBox({
            format: 'R ###########.##',
            showSpinButton: false,
            decimals: 2,
            value: {{ $v }}
        });
        {{$k}}.appendTo('#{{$k}}');
        @endforeach
    @endif

$('#process_billing').on('submit', function(e) {
   e.preventDefault();
   formSubmit("process_billing");
});
</script>