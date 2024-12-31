{!! Form::open(array("url"=> "import_facebook_leads", "class"=>"form-horizontal","id"=> "import_facebook_leads")) !!}	

<input name='account_id' id='account_id' type="hidden" value="{{$account_id}}"/> 

<div class="row mt-3 p-2">
    <div class="col">
        <div class="card">
            <div class="card-header">
              Facebook Leads Import
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="ip_address"> Facebook Form ID </label>
                    </div>
                    <div class="col-md-9">
                        <input name='form_id' id='form_id'/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



{!! Form::close() !!}

<script type="text/javascript">
    $("#regdiv").hide();
   
    form_id = new ej.inputs.TextBox({
    	placeholder: "Facebook Form ID",
    	floatLabel: 'auto'
    });
    form_id.appendTo("#form_id");

$('#import_facebook_leads').on('submit', function(e) {
	e.preventDefault();
   formSubmit("import_facebook_leads");
   
});
</script>