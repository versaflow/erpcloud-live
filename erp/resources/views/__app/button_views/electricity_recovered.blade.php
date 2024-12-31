{!! Form::open(array("url"=> "electricity_recovered", "class"=>"form-horizontal","id"=> "electricity_recovered")) !!}	

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
               Electricity Recovered
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="electricity_balance">  Electricity Balance </label>
                    </div>
                    <div class="col-md-9">
                        <input id="electricity_balance" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="citiq_balance">  Citiq Balance </label>
                    </div>
                    <div class="col-md-9">
                        <input id="citiq_balance" />
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>


{!! Form::close() !!}

<script type="text/javascript">

    electricity_balance = new ej.inputs.NumericTextBox({
        format: 'R ###########.##',
        showSpinButton: false,
        decimals: 2,
        value: {{ $electricity_balance }}
    });
    electricity_balance.appendTo('#electricity_balance');
    
    citiq_balance = new ej.inputs.NumericTextBox({
        format: 'R ###########.##',
        showSpinButton: false,
        decimals: 2,
        value: {{ $citiq_balance }}
    });
    citiq_balance.appendTo('#citiq_balance');

$('#electricity_recovered').on('submit', function(e) {
    e.preventDefault();
    formSubmit("electricity_recovered");
});
</script>