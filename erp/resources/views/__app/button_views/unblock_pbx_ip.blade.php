{!! Form::open(array("url"=> "check_fail2ban", "class"=>"form-horizontal","id"=> "check_fail2ban")) !!}	

<input name='account_id' id='account_id' type="hidden" value="{{$account_id}}"/> 

<div class="row mt-3 p-2">
    <div class="col">
        <div class="card">
            <div class="card-header">
              Check IP Status
              <p> Enter the IP address of the network used by your phone.</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="ip_address"> IP Address </label>
                    </div>
                    <div class="col-md-9">
                        <input name='ip_address' id='ip_address'/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3 p-2" id="regdiv">
    <div class="col">
        <div class="card">
            <div class="card-header">
              Registration Failures
            </div>
            <div class="card-body">
               
                <div class="row">
                    <div class="col-md-12 text-left align-self-center" id="shell_response">
                        Please verify that your extensions that are connecting on this ip has the correct username and password set.<br>
                        If you have confirmed that your extension details are correct click the below checkbox and submit the form to unblock your ip.
                    </div>
                </div>
                        
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="ip_address"> Extension details checked </label>
                    </div>
                    <div class="col-md-9">
                        	<label>Confirm that you checked the extensions username and passwords.</label><br>
			                <input name="registrations_checked" id="registrations_checked" type="checkbox" value="1" >
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

<script type="text/javascript">
    $("#regdiv").hide();
   
    ip_address = new ej.inputs.TextBox({
    	placeholder: "IP Address",
    	floatLabel: 'auto'
    });
    ip_address.appendTo("#ip_address");
    
    var checkbox = { label: 'Confirm that you checked the extensions username and passwords.' };
	var registrations_checked = new ej.buttons.Switch(checkbox);
	registrations_checked.appendTo("#registrations_checked");

function update_ip_form(data){
    if(data.blocked > ''){
        $("#regdiv").show();
      
    }else{
         $("#regdiv").hide();
    }
}

$('#check_fail2ban').on('submit', function(e) {
	e.preventDefault();
   formSubmit("check_fail2ban", update_ip_form);
   
});
</script>